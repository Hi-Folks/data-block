<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use HiFolks\DataType\Enums\Operator;
use PHPUnit\Framework\TestCase;

final class BlockConditionalAggregationTest extends TestCase
{
    private Block $rows;

    protected function setUp(): void
    {
        $this->rows = Block::make([
            "first" => [
                "status" => "paid",
                "amount" => 10,
                "customer" => ["tier" => 1],
                "totals" => ["net" => "8.5"],
                "active" => true,
            ],
            "second" => [
                "status" => "paid",
                "amount" => "20.5",
                "customer" => ["tier" => "1"],
                "totals" => ["net" => 15],
                "active" => false,
            ],
            "third" => [
                "status" => "pending",
                "amount" => 30,
                "customer" => ["tier" => 2],
                "totals" => ["net" => "invalid"],
                "active" => true,
            ],
            "fourth" => [
                "status" => "paid",
                "amount" => null,
                "customer" => ["tier" => 3],
            ],
            "fifth" => [
                "status" => "paid",
                "amount" => "invalid",
                "customer" => null,
            ],
            "sixth" => ["amount" => 100],
            "scalar",
        ]);
    }

    public function testCountWhereUsesTheWhereShorthand(): void
    {
        $this->assertSame(4, $this->rows->countWhere("status", "paid"));
        $this->assertSame(2, $this->rows->countWhere("active"));
    }

    public function testCountWhereSupportsEveryWhereOperatorForm(): void
    {
        $this->assertSame(
            2,
            $this->rows->countWhere(
                "customer.tier",
                Operator::GREATER_THAN,
                1,
            ),
        );
        $this->assertSame(
            1,
            $this->rows->countWhere(
                "status",
                Operator::IN,
                ["pending", "missing"],
            ),
        );
    }

    public function testCountWhereMakesLooseAndStrictEqualityExplicit(): void
    {
        $this->assertSame(2, $this->rows->countWhere("customer.tier", 1));
        $this->assertSame(
            1,
            $this->rows->countWhere(
                "customer.tier",
                Operator::STRICT_EQUAL,
                1,
            ),
        );
    }

    public function testCountWhereSkipsMissingFieldsAndScalarRows(): void
    {
        $this->assertSame(0, $this->rows->countWhere("missing", "value"));
        $this->assertSame(1, $this->rows->countWhere("customer", null));
    }

    public function testSumWhereIncludesNumericStringsAndIgnoresInvalidValues(): void
    {
        $this->assertSame(
            30.5,
            $this->rows->sumWhere("amount", "status", "paid"),
        );
    }

    public function testSumWhereSupportsNestedConditionAndValueFields(): void
    {
        $this->assertSame(
            23.5,
            $this->rows->sumWhere(
                "totals.net",
                "customer.tier",
                Operator::LESS_THAN_OR_EQUAL,
                2,
            ),
        );
    }

    public function testSumWhereCanMatchBooleanTrueWithTheOneArgumentWhereForm(): void
    {
        $this->assertSame(40, $this->rows->sumWhere("amount", "active"));
    }

    public function testConditionalAggregationsReturnZeroForNoMatchesAndEmptyBlocks(): void
    {
        $this->assertSame(
            0,
            $this->rows->sumWhere("amount", "status", "cancelled"),
        );
        $this->assertSame(0, Block::make([])->countWhere("status", "paid"));
        $this->assertSame(
            0,
            Block::make([])->sumWhere("amount", "status", "paid"),
        );
    }

    public function testInvalidOperatorsAreRejectedEvenForEmptyBlocks(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unsupported query operator");

        Block::make([])->countWhere("status", "approximately", "paid");
    }

    public function testConditionalAggregationsDoNotModifyTheSourceBlock(): void
    {
        $original = $this->rows->toArray();

        $this->rows->countWhere("status", "paid");
        $this->rows->sumWhere("amount", "status", "paid");

        $this->assertSame($original, $this->rows->toArray());
    }
}
