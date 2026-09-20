<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\TestCase;

final class BlockNavigationTest extends TestCase
{
    private Block $rows;

    protected function setUp(): void
    {
        $this->rows = Block::make([
            "desk" => ["name" => "Desk", "price" => 200],
            "chair" => ["name" => "Chair", "price" => 100],
            "lamp" => ["name" => "Lamp", "price" => 50],
            "door" => ["name" => "Door", "price" => 300],
        ]);
    }

    public function testTakeReturnsTheRequestedNumberOfItemsAndPreservesKeys(): void
    {
        $result = $this->rows->take(2);

        $this->assertSame(["desk", "chair"], $result->keys());
        $this->assertCount(4, $this->rows);
    }

    public function testNegativeTakeReturnsItemsFromTheEnd(): void
    {
        $this->assertSame(
            ["lamp", "door"],
            $this->rows->take(-2)->keys(),
        );
    }

    public function testTakeZeroReturnsAnEmptyBlock(): void
    {
        $this->assertSame([], $this->rows->take(0)->toArray());
    }

    public function testSkipPreservesTheRemainingKeys(): void
    {
        $this->assertSame(
            ["lamp", "door"],
            $this->rows->skip(2)->keys(),
        );
        $this->assertSame([], $this->rows->skip(10)->toArray());
    }

    public function testSkipRejectsNegativeCounts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("zero or greater");

        $this->rows->skip(-1);
    }

    public function testSliceUsesPhpOffsetAndLengthSemantics(): void
    {
        $this->assertSame(
            ["chair", "lamp"],
            $this->rows->slice(1, 2)->keys(),
        );
        $this->assertSame(
            ["lamp"],
            $this->rows->slice(-2, 1)->keys(),
        );
        $this->assertSame(
            ["desk", "chair"],
            $this->rows->slice(0, -2)->keys(),
        );
    }

    public function testValuesCanExplicitlyReindexNavigationResults(): void
    {
        $this->assertSame([0, 1], $this->rows->take(2)->values()->keys());
    }

    public function testFirstAndLastReturnBlocksForArrayItemsByDefault(): void
    {
        $first = $this->rows->first();
        $last = $this->rows->last();

        $this->assertInstanceOf(Block::class, $first);
        $this->assertInstanceOf(Block::class, $last);
        $this->assertSame("Desk", $first->get("name"));
        $this->assertSame("Door", $last->get("name"));
    }

    public function testFirstAndLastRespectNativeArrayIterationMode(): void
    {
        $rows = $this->rows->iterateBlock(false);

        $this->assertSame(
            ["name" => "Desk", "price" => 200],
            $rows->first(),
        );
        $this->assertSame(
            ["name" => "Door", "price" => 300],
            $rows->last(),
        );
    }

    public function testFirstAndLastReturnScalarValuesUnchanged(): void
    {
        $values = Block::make(["first" => 10, "last" => "twenty"]);

        $this->assertSame(10, $values->first());
        $this->assertSame("twenty", $values->last());
    }

    public function testNullableAccessorsReturnNullForAnEmptyBlock(): void
    {
        $empty = Block::make([]);

        $this->assertNull($empty->firstOrNull());
        $this->assertNull($empty->lastOrNull());
    }

    public function testFirstThrowsForAnEmptyBlock(): void
    {
        $this->expectException(UnderflowException::class);
        $this->expectExceptionMessage("first item");

        Block::make([])->first();
    }

    public function testLastThrowsForAnEmptyBlock(): void
    {
        $this->expectException(UnderflowException::class);
        $this->expectExceptionMessage("last item");

        Block::make([])->last();
    }

    public function testNavigationCanBeUsedInAFluentQuery(): void
    {
        $result = $this->rows
            ->orderBy("price", "desc")
            ->take(2)
            ->select("name", "price");

        $this->assertSame([
            ["name" => "Door", "price" => 300],
            ["name" => "Desk", "price" => 200],
        ], $result->toArray());
    }
}
