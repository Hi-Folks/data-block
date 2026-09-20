<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\TestCase;

final class BlockAggregationTest extends TestCase
{
    private Block $rows;

    protected function setUp(): void
    {
        $this->rows = Block::make([
            ["currency" => "EUR", "amount" => 10, "invoice" => ["tax" => 2]],
            ["currency" => "USD", "amount" => "20.5", "invoice" => ["tax" => 4]],
            ["currency" => "EUR", "amount" => 30, "invoice" => ["tax" => 6]],
            ["currency" => "EUR", "amount" => null],
            ["currency" => "USD", "amount" => "not numeric"],
            ["currency" => "USD"],
        ]);
    }

    public function testNumericAggregationsForAField(): void
    {
        $this->assertSame(60.5, $this->rows->sum("amount"));
        $this->assertSame(20.166666666666668, $this->rows->average("amount"));
        $this->assertSame(10, $this->rows->min("amount"));
        $this->assertSame(30, $this->rows->max("amount"));
    }

    public function testAggregationsSupportNestedFields(): void
    {
        $this->assertSame(12, $this->rows->sum("invoice.tax"));
        $this->assertSame(4.0, $this->rows->average("invoice.tax"));
        $this->assertSame(2, $this->rows->min("invoice.tax"));
        $this->assertSame(6, $this->rows->max("invoice.tax"));
    }

    public function testAggregationsCanOperateOnScalarValues(): void
    {
        $values = Block::make([1, "2", 3.5, null, "invalid", true]);

        $this->assertSame(6.5, $values->sum());
        $this->assertSame(2.1666666666666665, $values->average());
        $this->assertSame(1, $values->min());
        $this->assertSame(3.5, $values->max());
    }

    public function testEmptyAggregationsHavePredictableResults(): void
    {
        $empty = Block::make([]);

        $this->assertSame(0, $empty->sum());
        $this->assertNull($empty->average());
        $this->assertNull($empty->min());
        $this->assertNull($empty->max());
    }

    public function testReduceReceivesTheCarryItemAndKey(): void
    {
        $totals = $this->rows->reduce(
            function (array $carry, Block $row, int $key): array {
                $currency = $row->getStringStrict("currency");
                $carry[$currency] = ($carry[$currency] ?? 0) + $row->getFloatStrict("amount");
                $carry["last_key"] = $key;

                return $carry;
            },
            [],
        );

        $this->assertSame(40.0, $totals["EUR"]);
        $this->assertSame(20.5, $totals["USD"]);
        $this->assertSame(5, $totals["last_key"]);
    }

    public function testGroupedBlocksCanBeAggregatedFluently(): void
    {
        $totals = $this->rows
            ->groupBy("currency")
            ->map(fn(Block $group): int|float => $group->sum("amount"));

        $this->assertSame([
            "EUR" => 40,
            "USD" => 20.5,
        ], $totals->toArray());
    }

    public function testGroupedAggregationHelpers(): void
    {
        $this->assertSame([
            "EUR" => 3,
            "USD" => 3,
        ], $this->rows->countBy("currency")->toArray());

        $this->assertSame([
            "EUR" => 40,
            "USD" => 20.5,
        ], $this->rows->sumBy("currency", "amount")->toArray());

        $this->assertSame([
            "EUR" => 20.0,
            "USD" => 20.5,
        ], $this->rows->averageBy("currency", "amount")->toArray());

        $this->assertSame([
            "EUR" => 10,
            "USD" => 20.5,
        ], $this->rows->minBy("currency", "amount")->toArray());

        $this->assertSame([
            "EUR" => 30,
            "USD" => 20.5,
        ], $this->rows->maxBy("currency", "amount")->toArray());
    }

    public function testGroupedAggregationHelpersSupportNestedFields(): void
    {
        $orders = Block::make([
            ["customer" => ["currency" => "EUR"], "totals" => ["amount" => 10]],
            ["customer" => ["currency" => "USD"], "totals" => ["amount" => "20.5"]],
            ["customer" => ["currency" => "EUR"], "totals" => ["amount" => 30]],
        ]);

        $this->assertSame([
            "EUR" => 2,
            "USD" => 1,
        ], $orders->countBy("customer.currency")->toArray());

        $this->assertSame([
            "EUR" => 40,
            "USD" => 20.5,
        ], $orders->sumBy("customer.currency", "totals.amount")->toArray());

        $this->assertSame([
            "EUR" => 20.0,
            "USD" => 20.5,
        ], $orders->averageBy("customer.currency", "totals.amount")->toArray());

        $this->assertSame([
            "EUR" => 10,
            "USD" => 20.5,
        ], $orders->minBy("customer.currency", "totals.amount")->toArray());

        $this->assertSame([
            "EUR" => 30,
            "USD" => 20.5,
        ], $orders->maxBy("customer.currency", "totals.amount")->toArray());
    }

    public function testGroupedAggregationHelpersSupportADefaultGroup(): void
    {
        $orders = Block::make([
            ["currency" => "EUR", "amount" => 10],
            ["currency" => null, "amount" => 20],
            ["amount" => 30],
        ]);

        $this->assertSame([
            "EUR" => 10,
            "unknown" => 50,
        ], $orders->sumBy(
            groupField: "currency",
            valueField: "amount",
            defaultGroup: "unknown",
        )->toArray());
    }

    public function testCountBySupportsFalseyKeysAndADefaultGroup(): void
    {
        $rows = Block::make([
            ["group" => 0],
            ["group" => "0"],
            ["group" => false],
            ["group" => ""],
            ["group" => null],
            ["name" => "missing"],
        ]);

        $this->assertSame([
            0 => 3,
            "" => 1,
        ], $rows->countBy("group")->toArray());

        $this->assertSame([
            0 => 3,
            "" => 1,
            "unknown" => 2,
        ], $rows->countBy("group", defaultGroup: "unknown")->toArray());
    }

    public function testCountByReturnsAnEmptyBlockForEmptyInput(): void
    {
        $this->assertSame([], Block::make([])->countBy("group")->toArray());
    }
}
