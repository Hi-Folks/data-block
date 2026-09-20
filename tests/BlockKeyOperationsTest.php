<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use HiFolks\DataType\Enums\SortDirection;
use PHPUnit\Framework\TestCase;

final class BlockKeyOperationsTest extends TestCase
{
    public function testWithoutKeysRemovesStringAndIntegerKeys(): void
    {
        $values = Block::make([
            "" => 10,
            "EUR" => 20,
            5 => 30,
            "USD" => 40,
        ]);

        $filtered = $values->withoutKeys("", 5);

        $this->assertSame(["EUR" => 20, "USD" => 40], $filtered->toArray());
    }

    public function testWithoutKeysIgnoresMissingKeysAndAcceptsNoKeys(): void
    {
        $values = Block::make(["EUR" => 20, "USD" => 40]);

        $this->assertSame(
            $values->toArray(),
            $values->withoutKeys("GBP", 10)->toArray(),
        );
        $this->assertSame($values->toArray(), $values->withoutKeys()->toArray());
    }

    public function testWithoutKeysUsesPhpExactArrayKeySemantics(): void
    {
        $values = Block::make([1 => "integer", "01" => "string"]);

        $this->assertSame(
            ["01" => "string"],
            $values->withoutKeys(1)->toArray(),
        );
    }

    public function testSortKeysSortsStringKeysInBothDirections(): void
    {
        $values = Block::make(["USD" => 20, "EUR" => 40, "GBP" => 30]);

        $this->assertSame(
            ["EUR", "GBP", "USD"],
            $values->sortKeys()->keys(),
        );
        $this->assertSame(
            ["USD", "GBP", "EUR"],
            $values->sortKeys(SortDirection::DESC)->keys(),
        );
    }

    public function testSortKeysDefinesMixedKeyOrdering(): void
    {
        $values = Block::make([
            "USD" => "usd",
            10 => "ten",
            "" => "empty",
            -3 => "negative",
            "EUR" => "eur",
            2 => "two",
        ]);

        $this->assertSame(
            [-3, 2, 10, "", "EUR", "USD"],
            $values->sortKeys()->keys(),
        );
        $this->assertSame(
            ["USD", "EUR", "", 10, 2, -3],
            $values->sortKeys(SortDirection::DESC)->keys(),
        );
    }

    public function testKeyOperationsDoNotMutateTheOriginalBlock(): void
    {
        $values = Block::make(["USD" => 20, "" => 10, "EUR" => 40]);

        $values->withoutKeys("")->sortKeys();

        $this->assertSame(
            ["USD" => 20, "" => 10, "EUR" => 40],
            $values->toArray(),
        );
    }

    public function testKeyOperationsPreserveValuesAndTheirAssociations(): void
    {
        $values = Block::make(["USD" => 20, "EUR" => 40]);

        $this->assertSame(
            ["EUR" => 40, "USD" => 20],
            $values->sortKeys()->toArray(),
        );
    }

    public function testKeyOperationsPreserveNativeArrayIterationMode(): void
    {
        $values = Block::make([
            "USD" => ["amount" => 20],
            "" => ["amount" => 10],
            "EUR" => ["amount" => 40],
        ], false);

        $result = $values->withoutKeys("")->sortKeys();

        $this->assertIsArray($result->first());
        $this->assertSame(["amount" => 40], $result->first());
    }

    public function testKeyHelpersWorkWithGroupedAggregations(): void
    {
        $orders = Block::make([
            ["currency" => "USD", "amount" => 20],
            ["currency" => "", "amount" => 10],
            ["currency" => "EUR", "amount" => 40],
        ]);

        $totals = $orders
            ->sumBy("currency", "amount")
            ->withoutKeys("")
            ->sortKeys();

        $this->assertSame(["EUR" => 40, "USD" => 20], $totals->toArray());
    }
}
