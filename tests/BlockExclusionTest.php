<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\TestCase;

final class BlockExclusionTest extends TestCase
{
    private Block $rows;

    protected function setUp(): void
    {
        $this->rows = Block::make([
            "active" => ["status" => "active", "metadata" => ["code" => 0]],
            "pending" => ["status" => "pending", "metadata" => ["code" => "0"]],
            "archived" => ["status" => "archived", "metadata" => ["code" => false]],
            "null" => ["status" => null],
            "missing" => ["name" => "Missing status"],
            "scalar",
        ]);
    }

    public function testWhereNotInExcludesListedValuesAndPreservesKeys(): void
    {
        $result = $this->rows->whereNotIn(
            "status",
            ["pending", "archived"],
        );

        $this->assertSame(["active", "null"], $result->keys());
        $this->assertSame("active", $result->get("active.status"));
        $this->assertNull($result->get("null.status"));
    }

    public function testWhereNotInUsesStrictComparisonByDefault(): void
    {
        $this->assertSame(
            ["pending", "archived"],
            $this->rows->whereNotIn("metadata.code", [0])->keys(),
        );
        $this->assertSame(
            [],
            $this->rows->whereNotIn(
                "metadata.code",
                [0],
                strict: false,
            )->keys(),
        );
    }

    public function testWhereNotInSupportsNestedFieldsAndCanReindexResults(): void
    {
        $result = $this->rows->whereNotIn(
            "metadata.code",
            [false],
            preserveKeys: false,
        );

        $this->assertSame([0, 1], $result->keys());
        $this->assertSame("active", $result->get("0.status"));
        $this->assertSame("pending", $result->get("1.status"));
    }

    public function testWhereNotInSkipsMissingFieldsAndHandlesNullExplicitly(): void
    {
        $this->assertSame(
            ["active", "pending", "archived", "null"],
            $this->rows->whereNotIn("status", ["deleted"])->keys(),
        );
        $this->assertSame(
            ["active", "pending", "archived"],
            $this->rows->whereNotIn("status", [null])->keys(),
        );
    }

    public function testWhereNotInWithAnEmptyListReturnsRowsContainingTheField(): void
    {
        $this->assertSame(
            ["active", "pending", "archived", "null"],
            $this->rows->whereNotIn("status", [])->keys(),
        );
    }

    public function testRejectReturnsItemsThatDoNotMatchTheCallback(): void
    {
        $result = $this->rows->reject(
            fn(mixed $row): bool => $row instanceof Block
                && $row->get("status") === "archived",
        );

        $this->assertSame(
            ["active", "pending", "null", "missing", 0],
            $result->keys(),
        );
        $this->assertFalse($result->hasKey("archived"));
    }

    public function testRejectReceivesEveryItemAndItsKeyOnce(): void
    {
        $visited = [];

        $result = $this->rows->reject(
            function (mixed $row, int|string $key) use (&$visited): bool {
                $visited[] = $key;

                return $key === "pending";
            },
        );

        $this->assertSame(
            ["active", "pending", "archived", "null", "missing", 0],
            $visited,
        );
        $this->assertFalse($result->hasKey("pending"));
    }

    public function testRejectRequiresABooleanCallbackResult(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("reject callback must return a boolean");

        $this->rows->reject(fn(): int => 1);
    }

    public function testRejectRespectsNativeArrayIterationMode(): void
    {
        $rows = Block::make([
            "first" => ["active" => true],
            "second" => ["active" => false],
        ], false);

        $result = $rows->reject(fn(array $row): bool => !$row["active"]);

        $this->assertSame(["first"], $result->keys());
        $this->assertIsArray($result->first());
    }

    public function testExclusionHelpersSupportEmptyBlocks(): void
    {
        $empty = Block::make([]);

        $this->assertSame([], $empty->whereNotIn("status", ["archived"])->toArray());
        $this->assertSame([], $empty->reject(fn(): bool => true)->toArray());
    }

    public function testExclusionHelpersDoNotModifyTheSourceBlock(): void
    {
        $original = $this->rows->toArray();

        $this->rows->whereNotIn("status", ["archived"]);
        $this->rows->reject(fn(): bool => true);

        $this->assertSame($original, $this->rows->toArray());
    }
}
