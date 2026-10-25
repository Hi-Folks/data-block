<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\TestCase;

final class BlockPivotSumTest extends TestCase
{
    public function testPivotSumBuildsAReadableWideNumericTable(): void
    {
        $orders = Block::make([
            ["region" => "Europe", "status" => "paid", "amount" => 10],
            ["region" => "America", "status" => "paid", "amount" => 25],
            ["region" => "Europe", "status" => "pending", "amount" => 30],
            ["region" => "Europe", "status" => "paid", "amount" => "20.5"],
        ]);

        $table = $orders->pivotSum("region", "status", "amount");

        $this->assertSame([
            ["region" => "Europe", "paid" => 30.5, "pending" => 30],
            ["region" => "America", "paid" => 25, "pending" => 0],
        ], $table->toArray());
        $this->assertSame(30.5, $table->get("0.paid"));
    }

    public function testObservedNonNumericCellsDifferFromMissingCells(): void
    {
        $orders = Block::make([
            ["region" => "Europe", "status" => "paid", "amount" => null],
            ["region" => "Europe", "status" => "paid", "amount" => "invalid"],
            ["region" => "Europe", "status" => "paid", "amount" => false],
            ["region" => "America", "status" => "pending", "amount" => 10],
        ]);

        $table = $orders->pivotSum(
            "region",
            "status",
            "amount",
            fill: null,
        );

        $this->assertSame([
            ["region" => "Europe", "paid" => 0, "pending" => null],
            ["region" => "America", "paid" => null, "pending" => 10],
        ], $table->toArray());
    }

    public function testPivotSumSupportsNestedFieldsAliasesAndDefaults(): void
    {
        $orders = Block::make([
            [
                "customer" => ["region" => "Europe"],
                "state" => "paid",
                "totals" => ["amount" => "10.5"],
            ],
            [
                "customer" => ["region" => "Europe"],
                "state" => null,
                "totals" => ["amount" => 20],
            ],
            ["state" => "paid", "totals" => ["amount" => 5]],
            ["customer" => [], "totals" => ["amount" => 2]],
        ]);

        $table = $orders->pivotSum(
            "customer.region",
            "state",
            "totals.amount",
            rowAs: "region",
            defaultRow: "unknown-region",
            defaultColumn: "unknown-state",
        );

        $this->assertSame([
            ["region" => "Europe", "paid" => 10.5, "unknown-state" => 20],
            ["region" => "unknown-region", "paid" => 5, "unknown-state" => 2],
        ], $table->toArray());
    }

    public function testPivotSumSkipsRowsWithoutGroupingValuesByDefault(): void
    {
        $orders = Block::make([
            ["region" => "Europe", "status" => "paid", "amount" => 10],
            ["region" => "Europe", "amount" => 20],
            ["status" => "paid", "amount" => 30],
            ["region" => null, "status" => "paid", "amount" => 40],
            "scalar row",
        ]);

        $this->assertSame(
            [["region" => "Europe", "paid" => 10]],
            $orders->pivotSum("region", "status", "amount")->toArray(),
        );
    }

    public function testPivotSumUsesTheSameColumnCollisionProtectionAsCrossTab(): void
    {
        $orders = Block::make([
            ["region" => "Europe", "status" => 0, "amount" => 10],
            ["region" => "America", "status" => "0", "amount" => 20],
        ]);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            "Cross-tab column values 0 (int) and '0' (string) both produce output column 0 (int)",
        );

        $orders->pivotSum("region", "status", "amount");
    }

    public function testPivotSumAcceptsBlockRowsAndPreservesTheSource(): void
    {
        $first = Block::make([
            "region" => "Europe",
            "status" => "paid",
            "amount" => 10,
        ]);
        $orders = Block::make([
            $first,
            ["region" => "America", "status" => "pending", "amount" => 20],
        ]);
        $original = $orders->toArray();

        $table = $orders->pivotSum("region", "status", "amount");

        $this->assertSame([
            ["region" => "Europe", "paid" => 10, "pending" => 0],
            ["region" => "America", "paid" => 0, "pending" => 20],
        ], $table->toArray());
        $this->assertSame($original, $orders->toArray());
        $this->assertSame($first, $orders->get(0));
    }

    public function testPivotSumSupportsEmptyInputAndNativeArrayIteration(): void
    {
        $this->assertSame(
            [],
            Block::make([])->pivotSum("region", "status", "amount")->toArray(),
        );

        $orders = Block::make([
            ["region" => "Europe", "status" => "paid", "amount" => 10],
        ], false);

        $this->assertIsArray(
            $orders->pivotSum("region", "status", "amount")->first(),
        );
    }

    public function testPivotSumRejectsAnEmptyValueField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Cross-tab value field must not be empty",
        );

        Block::make([])->pivotSum("region", "status", "");
    }
}
