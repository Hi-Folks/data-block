<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\TestCase;

final class BlockCrossTabTest extends TestCase
{
    public function testCrossTabBuildsAReadableWideCountTable(): void
    {
        $orders = Block::make([
            ["region" => "Europe", "status" => "paid"],
            ["region" => "America", "status" => "paid"],
            ["region" => "Europe", "status" => "pending"],
            ["region" => "Europe", "status" => "paid"],
        ]);

        $table = $orders->crossTab("region", "status");

        $this->assertSame([
            ["region" => "Europe", "paid" => 2, "pending" => 1],
            ["region" => "America", "paid" => 1, "pending" => 0],
        ], $table->toArray());
        $this->assertSame(2, $table->get("0.paid"));
    }

    public function testCrossTabSupportsARowAliasAndCustomFillValue(): void
    {
        $orders = Block::make([
            ["region" => "Europe", "status" => "paid"],
            ["region" => "America", "status" => "pending"],
        ]);

        $table = $orders->crossTab(
            "region",
            "status",
            rowAs: "market",
            fill: null,
        );

        $this->assertSame([
            ["market" => "Europe", "paid" => 1, "pending" => null],
            ["market" => "America", "paid" => null, "pending" => 1],
        ], $table->toArray());
    }

    public function testCrossTabSupportsNestedFieldsAndDefaults(): void
    {
        $orders = Block::make([
            ["customer" => ["region" => "Europe"], "state" => "paid"],
            ["customer" => ["region" => "Europe"], "state" => null],
            ["customer" => [], "state" => "paid"],
            ["state" => "pending"],
            ["customer" => ["region" => "Europe"]],
        ]);

        $table = $orders->crossTab(
            "customer.region",
            "state",
            rowAs: "region",
            defaultRow: "unknown-region",
            defaultColumn: "unknown-state",
        );

        $this->assertSame([
            [
                "region" => "Europe",
                "paid" => 1,
                "unknown-state" => 2,
                "pending" => 0,
            ],
            [
                "region" => "unknown-region",
                "paid" => 1,
                "unknown-state" => 0,
                "pending" => 1,
            ],
        ], $table->toArray());
    }

    public function testRowsWithMissingGroupsAreSkippedWithoutDefaults(): void
    {
        $orders = Block::make([
            ["region" => "Europe", "status" => "paid"],
            ["region" => "Europe"],
            ["status" => "paid"],
            ["region" => null, "status" => "paid"],
            ["region" => "Europe", "status" => []],
            "scalar row",
        ]);

        $this->assertSame(
            [["region" => "Europe", "paid" => 1]],
            $orders->crossTab("region", "status")->toArray(),
        );
    }

    public function testCrossTabKeepsFalseyRowValuesDistinct(): void
    {
        $rows = Block::make([
            ["group" => 0, "type" => "x"],
            ["group" => "0", "type" => "x"],
            ["group" => false, "type" => "x"],
            ["group" => "", "type" => "x"],
        ]);

        $this->assertSame([
            ["group" => 0, "x" => 1],
            ["group" => "0", "x" => 1],
            ["group" => false, "x" => 1],
            ["group" => "", "x" => 1],
        ], $rows->crossTab("group", "type")->toArray());
    }

    public function testBooleanAndFloatColumnValuesBecomeReadableKeys(): void
    {
        $rows = Block::make([
            ["group" => "a", "type" => false],
            ["group" => "a", "type" => 1.5],
            ["group" => "b", "type" => 1.5],
        ]);

        $this->assertSame([
            ["group" => "a", 0 => 1, "1.5" => 1],
            ["group" => "b", 0 => 0, "1.5" => 1],
        ], $rows->crossTab("group", "type")->toArray());
    }

    public function testCrossTabRejectsAmbiguousGeneratedColumnNames(): void
    {
        $rows = Block::make([
            ["group" => "a", "type" => 0],
            ["group" => "b", "type" => "0"],
        ]);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            "Cross-tab column values 0 (int) and '0' (string) both produce output column 0 (int)",
        );

        $rows->crossTab("group", "type");
    }

    public function testCrossTabRejectsAColumnThatConflictsWithTheRowLabel(): void
    {
        $rows = Block::make([
            ["region" => "Europe", "status" => "region"],
        ]);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            "conflicts with row output column 'region' (string)",
        );

        $rows->crossTab("region", "status");
    }

    public function testCrossTabAcceptsBlockRowsAndPreservesTheSource(): void
    {
        $first = Block::make(["region" => "Europe", "status" => "paid"]);
        $rows = Block::make([
            $first,
            ["region" => "America", "status" => "pending"],
        ]);
        $original = $rows->toArray();

        $table = $rows->crossTab("region", "status");

        $this->assertSame([
            ["region" => "Europe", "paid" => 1, "pending" => 0],
            ["region" => "America", "paid" => 0, "pending" => 1],
        ], $table->toArray());
        $this->assertSame($original, $rows->toArray());
        $this->assertSame($first, $rows->get(0));
    }

    public function testCrossTabSupportsEmptyInputAndNativeArrayIteration(): void
    {
        $this->assertSame(
            [],
            Block::make([])->crossTab("region", "status")->toArray(),
        );

        $rows = Block::make([
            ["region" => "Europe", "status" => "paid"],
        ], false);

        $this->assertIsArray(
            $rows->crossTab("region", "status")->first(),
        );
    }

    public function testCrossTabRejectsAnEmptyOutputField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Cross-tab row output field must not be empty",
        );

        Block::make([])->crossTab("region", "status", rowAs: "");
    }
}
