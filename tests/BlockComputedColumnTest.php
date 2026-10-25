<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\TestCase;

final class BlockComputedColumnTest extends TestCase
{
    public function testWithColumnAddsAComputedColumnAndPreservesRowKeys(): void
    {
        $rows = Block::make([
            "first" => ["quantity" => 2, "price" => 10.5],
            "second" => ["quantity" => 3, "price" => 4],
        ]);

        $result = $rows->withColumn(
            "total",
            fn(Block $row): float => $row->requireInt("quantity")
                * $row->requireFloat("price"),
        );

        $this->assertSame(["first", "second"], $result->keys());
        $this->assertSame(21.0, $result->get("first.total"));
        $this->assertSame(12.0, $result->get("second.total"));
    }

    public function testWithColumnsAddsSeveralColumnsAndProvidesTheRowKey(): void
    {
        $rows = Block::make([
            "item-a" => ["quantity" => 2, "price" => 10],
            "item-b" => ["quantity" => 4, "price" => 5],
        ]);

        $result = $rows->withColumns([
            "total" => fn(Block $row): float => $row->requireInt("quantity")
                * $row->requireFloat("price"),
            "source_key" => fn(Block $row, string $key): string => $key,
        ]);

        $this->assertSame(20.0, $result->get("item-a.total"));
        $this->assertSame("item-a", $result->get("item-a.source_key"));
        $this->assertSame("item-b", $result->get("item-b.source_key"));
    }

    public function testWithColumnsCanReplaceExistingAndNestedColumns(): void
    {
        $rows = Block::make([
            [
                "quantity" => 2,
                "totals" => ["amount" => 10, "currency" => "EUR"],
            ],
        ]);

        $result = $rows->withColumns([
            "quantity" => fn(): int => 5,
            "totals.amount" => fn(Block $row): int => $row->requireInt("quantity") * 10,
        ]);

        $this->assertSame(5, $result->get("0.quantity"));
        $this->assertSame(20, $result->get("0.totals.amount"));
        $this->assertSame("EUR", $result->get("0.totals.currency"));
    }

    public function testCallbacksUseIndependentSnapshotsOfTheOriginalRow(): void
    {
        $rows = Block::make([["quantity" => 2]]);

        $result = $rows->withColumns([
            "quantity" => function (Block $row): int {
                $row->set("quantity", 100);

                return 10;
            },
            "original_quantity" => fn(Block $row): int => $row->requireInt("quantity"),
        ]);

        $this->assertSame(10, $result->get("0.quantity"));
        $this->assertSame(2, $result->get("0.original_quantity"));
    }

    public function testOverwriteProtectionRejectsExistingColumnsIncludingNull(): void
    {
        $rows = Block::make([["value" => null]]);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Computed column 'value' already exists");

        $rows->withColumn("value", fn(): string => "replacement", overwrite: false);
    }

    public function testOverwriteProtectionSupportsNestedColumns(): void
    {
        $rows = Block::make([["metadata" => ["label" => "existing"]]]);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Computed column 'metadata.label' already exists");

        $rows->withColumn(
            "metadata.label",
            fn(): string => "replacement",
            overwrite: false,
        );
    }

    public function testComputedColumnsDoNotMutateTheOriginalBlock(): void
    {
        $rows = Block::make([["quantity" => 2]]);

        $result = $rows->withColumn("quantity", fn(): int => 10);

        $this->assertNotSame($rows, $result);
        $this->assertSame([["quantity" => 2]], $rows->toArray());
        $this->assertSame([["quantity" => 10]], $result->toArray());
    }

    public function testBlockRowsAreSupportedWithoutBeingMutated(): void
    {
        $row = Block::make(["quantity" => 2]);
        $rows = Block::make([$row]);

        $result = $rows->withColumn("double", fn(Block $item): int => $item->requireInt("quantity") * 2);

        $this->assertSame(4, $result->get("0.double"));
        $this->assertFalse($row->hasKey("double"));
    }

    public function testScalarRowsAreRejected(): void
    {
        $rows = Block::make([["quantity" => 2], "invalid"]);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("every row to be an array or Block");

        $rows->withColumn("double", fn(Block $row): int => $row->requireInt("quantity") * 2);
    }

    public function testComputedColumnDefinitionsAreValidatedForEmptyBlocks(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Computed column 'invalid' must be defined by a callable");

        Block::make([])->withColumns(["invalid" => "not-callable"]);
    }

    public function testEmptyColumnsReturnAnUnchangedCopy(): void
    {
        $rows = Block::make(["first" => ["quantity" => 2], "scalar"]);

        $result = $rows->withColumns([]);

        $this->assertNotSame($rows, $result);
        $this->assertSame($rows->toArray(), $result->toArray());
    }

    public function testEmptyBlocksAreSupported(): void
    {
        $result = Block::make([])->withColumn("value", fn(): int => 1);

        $this->assertSame([], $result->toArray());
    }

    public function testResultPreservesNativeArrayIterationMode(): void
    {
        $rows = Block::make([["quantity" => 2]], false);

        $result = $rows->withColumn("double", fn(Block $row): int => $row->requireInt("quantity") * 2);

        $this->assertIsArray($result->first());
        $this->assertSame(4, $result->first()["double"]);
    }
}
