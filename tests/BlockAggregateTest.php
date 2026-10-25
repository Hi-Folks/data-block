<?php

declare(strict_types=1);

use HiFolks\DataType\Aggregation;
use HiFolks\DataType\Block;
use HiFolks\DataType\Enums\Operator;
use PHPUnit\Framework\TestCase;

final class BlockAggregateTest extends TestCase
{
    public function testItCalculatesAllBuiltInMetricsForTheCompleteDataset(): void
    {
        $rows = Block::make([
            ["column1" => "value1", "column2" => 10],
            ["column1" => "value2", "column2" => 25],
            ["column1" => "value1", "column2" => "20.5"],
            ["column1" => "value2", "column2" => 30],
            ["column1" => "value1", "column2" => "not-numeric"],
        ]);

        $summary = $rows->aggregate([
            "records" => Aggregation::count(),
            "matching_records" => Aggregation::countWhere("column1", "value1"),
            "total" => Aggregation::sum("column2"),
            "matching_total" => Aggregation::sumWhere(
                "column2",
                "column1",
                "value1",
            ),
            "minimum" => Aggregation::min("column2"),
            "maximum" => Aggregation::max("column2"),
            "average" => Aggregation::average("column2"),
        ]);

        $this->assertSame([[
            "records" => 5,
            "matching_records" => 3,
            "total" => 85.5,
            "matching_total" => 30.5,
            "minimum" => 10,
            "maximum" => 30,
            "average" => 85.5 / 4,
        ]], $summary->toArray());
    }

    public function testEmptyInputReturnsOneRowWithInitialMetricValues(): void
    {
        $summary = Block::make([])->aggregate([
            "records" => Aggregation::count(),
            "matching_records" => Aggregation::countWhere("column1", "value1"),
            "total" => Aggregation::sum("column2"),
            "matching_total" => Aggregation::sumWhere(
                "column2",
                "column1",
                "value1",
            ),
            "minimum" => Aggregation::min("column2"),
            "maximum" => Aggregation::max("column2"),
            "average" => Aggregation::average("column2"),
        ]);

        $this->assertSame([[
            "records" => 0,
            "matching_records" => 0,
            "total" => 0,
            "matching_total" => 0,
            "minimum" => null,
            "maximum" => null,
            "average" => null,
        ]], $summary->toArray());
    }

    public function testNestedFieldsBlockRowsAndConditionalOperatorsAreSupported(): void
    {
        $rows = Block::make([
            Block::make([
                "column1" => ["nested" => 1],
                "column2" => ["nested" => 10],
            ]),
            [
                "column1" => ["nested" => "1"],
                "column2" => ["nested" => 20],
            ],
            [
                "column1" => ["nested" => 2],
                "column2" => ["nested" => 30],
            ],
            "incompatible row",
        ]);

        $summary = $rows->aggregate([
            "records" => Aggregation::count(),
            "loose" => Aggregation::countWhere(
                "column1.nested",
                Operator::EQUAL,
                1,
            ),
            "strict" => Aggregation::countWhere(
                "column1.nested",
                Operator::STRICT_EQUAL,
                1,
            ),
            "selected_total" => Aggregation::sumWhere(
                "column2.nested",
                "column1.nested",
                Operator::IN,
                [1, 2],
            ),
        ]);

        $this->assertSame([[
            "records" => 3,
            "loose" => 2,
            "strict" => 1,
            "selected_total" => 40,
        ]], $summary->toArray());
    }

    public function testMissingAndInvalidMetricValuesUseExistingNumericSemantics(): void
    {
        $rows = Block::make([
            ["column1" => true, "column2" => null],
            ["column1" => false, "column2" => "invalid"],
            ["column1" => true],
        ]);

        $this->assertSame([[
            "enabled" => 2,
            "enabled_total" => 0,
            "total" => 0,
            "minimum" => null,
            "maximum" => null,
            "average" => null,
        ]], $rows->aggregate([
            "enabled" => Aggregation::countWhere("column1"),
            "enabled_total" => Aggregation::sumWhere("column2", "column1"),
            "total" => Aggregation::sum("column2"),
            "minimum" => Aggregation::min("column2"),
            "maximum" => Aggregation::max("column2"),
            "average" => Aggregation::average("column2"),
        ])->toArray());
    }

    public function testInvalidMetricsAreRejectedBeforeReadingRows(): void
    {
        $row = Block::make([])->throwOnMissingKey(
            UnexpectedValueException::class,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Aggregation metric 'invalid' must be an Aggregation specification",
        );

        Block::make([$row])->aggregate(["invalid" => "count"]);
    }

    public function testItReturnsOneEmptyRowWhenNoMetricsAreRequested(): void
    {
        $this->assertSame(
            [[]],
            Block::make([["column1" => "value1"]])->aggregate([])->toArray(),
        );
    }

    public function testSourceImmutabilityAndIterationModeArePreserved(): void
    {
        $rows = Block::make([["column1" => "value1"]], false);
        $original = $rows->toArray();

        $summary = $rows->aggregate([
            "records" => Aggregation::count(),
        ]);

        $this->assertSame($original, $rows->toArray());
        $this->assertIsArray($summary->first());
        $this->assertSame(["records" => 1], $summary->first());
    }
}
