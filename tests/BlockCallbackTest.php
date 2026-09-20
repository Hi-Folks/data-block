<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\TestCase;

final class BlockCallbackTest extends TestCase
{
    private Block $rows;

    protected function setUp(): void
    {
        $this->rows = Block::make([
            "first" => ["name" => "Desk", "price" => 200, "active" => true],
            "second" => ["name" => "Chair", "price" => 100, "active" => false],
            "third" => ["name" => "Lamp", "price" => null, "active" => true],
        ]);
    }

    public function testMapTransformsItemsAndProvidesTheirKeys(): void
    {
        $mapped = $this->rows->map(
            fn(Block $row, string $key): string => $key . ":" . $row->getStringStrict("name"),
        );

        $this->assertSame([
            "first" => "first:Desk",
            "second" => "second:Chair",
            "third" => "third:Lamp",
        ], $mapped->toArray());
        $this->assertSame("Desk", $this->rows->get("first.name"));
    }

    public function testMapKeepsNullResults(): void
    {
        $mapped = $this->rows->map(fn(): null => null);

        $this->assertSame([
            "first" => null,
            "second" => null,
            "third" => null,
        ], $mapped->toArray());
    }

    public function testFilterSupportsMultipleConditionsAndPreservesKeys(): void
    {
        $filtered = $this->rows->filter(
            fn(Block $row): bool => $row->getBooleanStrict("active")
                && $row->getIntStrict("price") > 0,
        );

        $this->assertSame(["first"], $filtered->keys());
        $this->assertSame("Desk", $filtered->get("first.name"));
    }

    public function testFilterRequiresABooleanResult(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("filter callback must return a boolean");

        $this->rows->filter(fn(): int => 1);
    }

    public function testPartitionSplitsItemsAndPreservesKeys(): void
    {
        [$active, $inactive] = $this->rows->partition(
            fn(Block $row): bool => $row->getBooleanStrict("active"),
        );

        $this->assertSame(["first", "third"], $active->keys());
        $this->assertSame(["second"], $inactive->keys());
        $this->assertSame("Desk", $active->get("first.name"));
        $this->assertSame("Chair", $inactive->get("second.name"));
    }

    public function testPartitionVisitsEachItemOnceAndProvidesItsKey(): void
    {
        $visited = [];

        [$matching, $nonMatching] = $this->rows->partition(
            function (Block $row, string $key) use (&$visited): bool {
                $visited[] = $key;

                return $row->getStringStrict("name") === "Desk";
            },
        );

        $this->assertSame(["first", "second", "third"], $visited);
        $this->assertSame(["first"], $matching->keys());
        $this->assertSame(["second", "third"], $nonMatching->keys());
    }

    public function testPartitionRequiresABooleanResult(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            "partition callback must return a boolean",
        );

        $this->rows->partition(fn(): int => 1);
    }

    public function testValuesExplicitlyReindexesAResult(): void
    {
        $filtered = $this->rows
            ->filter(fn(Block $row): bool => $row->getBooleanStrict("active"))
            ->values();

        $this->assertSame([0, 1], $filtered->keys());
        $this->assertSame("Desk", $filtered->get("0.name"));
        $this->assertSame("Lamp", $filtered->get("1.name"));
    }

    public function testForEachPerformsSideEffectsAndReturnsTheOriginalBlock(): void
    {
        $visited = [];

        $returned = $this->rows->forEach(
            function (Block $row, string $key) use (&$visited): void {
                $visited[$key] = $row->getStringStrict("name");
            },
        );

        $this->assertSame($this->rows, $returned);
        $this->assertSame([
            "first" => "Desk",
            "second" => "Chair",
            "third" => "Lamp",
        ], $visited);
        $this->assertSame("Desk", $returned->get("first.name"));
    }

    public function testCallbacksRespectNativeArrayIterationMode(): void
    {
        $rows = Block::make([
            ["category" => "furniture", "name" => "Desk"],
            ["category" => "lighting", "name" => "Lamp"],
        ], false);

        $mapped = $rows->map(fn(array $row): string => $row["name"]);
        $filtered = $rows->filter(fn(array $row): bool => $row["name"] === "Desk");
        $grouped = $rows->groupByFunction(
            fn(array $row): string => $row["category"],
        );
        [$furniture, $other] = $rows->partition(
            fn(array $row): bool => $row["category"] === "furniture",
        );

        $this->assertSame(["Desk", "Lamp"], $mapped->toArray());
        $this->assertSame([["category" => "furniture", "name" => "Desk"]], $filtered->toArray());
        $this->assertCount(1, $grouped->getBlock("furniture"));
        $this->assertCount(1, $grouped->getBlock("lighting"));
        $this->assertIsArray($furniture->first());
        $this->assertIsArray($other->first());
        $this->assertSame("Desk", $furniture->first()["name"]);
        $this->assertSame("Lamp", $other->first()["name"]);
    }

    public function testGroupByFunctionReceivesBlocksAndKeysByDefault(): void
    {
        $grouped = $this->rows->groupByFunction(
            fn(Block $row, string $key): string => $row->getBooleanStrict("active")
                ? "active-" . $key
                : "inactive",
        );

        $this->assertCount(1, $grouped->getBlock("active-first"));
        $this->assertCount(1, $grouped->getBlock("inactive"));
        $this->assertCount(1, $grouped->getBlock("active-third"));
    }

    public function testCallbackOperationsHandleEmptyBlocks(): void
    {
        $empty = Block::make([]);

        $this->assertSame([], $empty->map(fn(mixed $item): mixed => $item)->toArray());
        $this->assertSame([], $empty->filter(fn(): bool => true)->toArray());
        [$matching, $nonMatching] = $empty->partition(fn(): bool => true);
        $this->assertSame([], $matching->toArray());
        $this->assertSame([], $nonMatching->toArray());
        $this->assertSame($empty, $empty->forEach(fn(): null => null));
        $this->assertSame([], $empty->values()->toArray());
    }

    public function testPartitionDoesNotMutateTheOriginalBlock(): void
    {
        $original = $this->rows->toArray();

        [$active, $inactive] = $this->rows->partition(
            fn(Block $row): bool => $row->getBooleanStrict("active"),
        );

        $this->assertNotSame($this->rows, $active);
        $this->assertNotSame($this->rows, $inactive);
        $this->assertSame($original, $this->rows->toArray());
    }
}
