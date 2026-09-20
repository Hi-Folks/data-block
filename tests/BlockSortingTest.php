<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use HiFolks\DataType\Enums\SortDirection;
use HiFolks\DataType\SortCriterion;
use PHPUnit\Framework\TestCase;

final class BlockSortingTest extends TestCase
{
    public function testOrderByManyUsesNestedFieldsAndMixedDirections(): void
    {
        $rows = Block::make([
            "third" => ["team" => "blue", "totals" => ["amount" => 10]],
            "first" => ["team" => "amber", "totals" => ["amount" => 20]],
            "second" => ["team" => "amber", "totals" => ["amount" => 30]],
            "fourth" => ["team" => "amber", "totals" => ["amount" => 20]],
        ]);

        $sorted = $rows->orderByMany([
            SortCriterion::asc("team"),
            SortCriterion::desc("totals.amount"),
        ]);

        $this->assertSame(
            ["second", "first", "fourth", "third"],
            array_keys($sorted->toArray()),
        );
        $this->assertSame($rows->toArray(), [
            "third" => ["team" => "blue", "totals" => ["amount" => 10]],
            "first" => ["team" => "amber", "totals" => ["amount" => 20]],
            "second" => ["team" => "amber", "totals" => ["amount" => 30]],
            "fourth" => ["team" => "amber", "totals" => ["amount" => 20]],
        ]);
    }

    public function testOrderByPreservesKeysAndValuesReindexes(): void
    {
        $rows = Block::make([
            10 => ["amount" => 30],
            20 => ["amount" => 10],
        ]);

        $sorted = $rows->orderBy("amount");

        $this->assertSame([20, 10], array_keys($sorted->toArray()));
        $this->assertSame([0, 1], array_keys($sorted->values()->toArray()));
    }

    public function testOrderByAcceptsSortDirectionEnum(): void
    {
        $rows = Block::make([
            "low" => ["amount" => 10],
            "high" => ["amount" => 30],
        ]);

        $this->assertSame(
            ["high", "low"],
            array_keys(
                $rows->orderBy("amount", SortDirection::DESC)->toArray(),
            ),
        );
    }

    public function testOrderByAcceptsSortCriterion(): void
    {
        $rows = Block::make([
            "low" => ["amount" => 10],
            "high" => ["amount" => 30],
        ]);

        $this->assertSame(
            ["high", "low"],
            array_keys(
                $rows->orderBy(SortCriterion::desc("amount"))->toArray(),
            ),
        );
    }

    public function testSortCriterionFactoriesExposeFieldAndDirection(): void
    {
        $ascending = SortCriterion::asc(0);
        $descending = SortCriterion::desc("totals.amount");

        $this->assertSame(0, $ascending->field);
        $this->assertSame(SortDirection::ASC, $ascending->direction);
        $this->assertSame("totals.amount", $descending->field);
        $this->assertSame(SortDirection::DESC, $descending->direction);
    }

    public function testOrderBySupportsAnIntegerFieldCriterion(): void
    {
        $rows = Block::make([
            "second" => [20, "second"],
            "first" => [10, "first"],
        ]);

        $this->assertSame(
            ["first", "second"],
            array_keys($rows->orderBy(SortCriterion::asc(0))->toArray()),
        );
    }

    public function testMissingNullAndNonScalarValuesAreAlwaysLast(): void
    {
        $rows = Block::make([
            "missing" => ["name" => "missing"],
            "array" => ["name" => "array", "score" => [1]],
            "high" => ["name" => "high", "score" => 20],
            "null" => ["name" => "null", "score" => null],
            "low" => ["name" => "low", "score" => 10],
            "object" => ["name" => "object", "score" => new stdClass()],
        ]);

        $this->assertSame(
            ["low", "high", "missing", "array", "null", "object"],
            array_keys($rows->orderBy("score", "asc")->toArray()),
        );
        $this->assertSame(
            ["high", "low", "missing", "array", "null", "object"],
            array_keys($rows->orderBy("score", "DESC")->toArray()),
        );
    }

    public function testMissingFieldsAreSafeForStoredBlocks(): void
    {
        $missing = Block::make(["name" => "missing"])->throwOnMissingKey();
        $present = Block::make(["name" => "present", "score" => 10]);

        $sorted = Block::make([$missing, $present])->orderBy("score");

        $this->assertSame([1, 0], array_keys($sorted->toArray()));
        $this->assertSame($present, $sorted->get(1));
        $this->assertSame($missing, $sorted->get(0));
    }

    public function testOrderByManyWithNoCriteriaReturnsAnUnchangedCopy(): void
    {
        $rows = Block::make(["b" => ["value" => 2], "a" => ["value" => 1]]);

        $sorted = $rows->orderByMany([]);

        $this->assertNotSame($rows, $sorted);
        $this->assertSame($rows->toArray(), $sorted->toArray());
    }

    public function testLegacyOrderByAcceptsCaseInsensitiveStrings(): void
    {
        $rows = Block::make([
            "low" => ["amount" => 10],
            "high" => ["amount" => 30],
        ]);

        $this->assertSame(
            ["high", "low"],
            array_keys(
                $rows->orderBy(field: "amount", order: "DESC")->toArray(),
            ),
        );
    }

    public function testLegacyOrderByRejectsAnUnknownDirection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Sort direction must be 'asc' or 'desc'");

        Block::make([])->orderBy("amount", "up");
    }

    public function testOrderByRejectsADirectionWithSortCriterion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "A SortCriterion already defines its sort direction",
        );

        Block::make([])->orderBy(
            SortCriterion::asc("amount"),
            SortDirection::DESC,
        );
    }

    public function testOrderByManyRejectsAssociativeCriteria(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Sort criteria must be provided as a list of SortCriterion objects",
        );

        /** @phpstan-ignore argument.type */
        Block::make([])->orderByMany(["amount" => SortDirection::ASC]);
    }

    public function testOrderByManyRejectsValuesThatAreNotCriteria(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Sort criteria must be provided as a list of SortCriterion objects",
        );

        /** @phpstan-ignore argument.type */
        Block::make([])->orderByMany(["amount"]);
    }

    public function testSortUsesBlocksPreservesKeysAndIsStable(): void
    {
        $rows = Block::make([
            "first" => ["priority" => 2],
            "second" => ["priority" => 1],
            "third" => ["priority" => 2],
        ]);

        $sorted = $rows->sort(
            fn(Block $left, Block $right): int => $left->get("priority")
                <=> $right->get("priority"),
        );

        $this->assertSame(
            ["second", "first", "third"],
            array_keys($sorted->toArray()),
        );
    }

    public function testSortUsesArraysWhenBlockIterationIsDisabled(): void
    {
        $rows = Block::make([
            "first" => ["priority" => 2],
            "second" => ["priority" => 1],
        ], false);

        $sorted = $rows->sort(
            fn(array $left, array $right): int => $left["priority"]
                <=> $right["priority"],
        );

        $this->assertSame(["second", "first"], array_keys($sorted->toArray()));
    }

    public function testSortSupportsScalarItems(): void
    {
        $values = Block::make(["first" => 30, "second" => 10]);

        $this->assertSame(
            ["second" => 10, "first" => 30],
            $values->sort(fn(int $left, int $right): int => $left <=> $right)
                ->toArray(),
        );
    }

    public function testSortRejectsANonIntegerComparatorResult(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Sort comparator must return an integer");

        Block::make([2, 1])->sort(fn(): bool => true);
    }
}
