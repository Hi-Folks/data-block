<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\TestCase;

final class BlockSummaryTest extends TestCase
{
    private Block $orders;

    protected function setUp(): void
    {
        $this->orders = Block::make([
            "eur-first" => ["currency" => "EUR", "status" => "paid", "amount" => 10],
            "usd-first" => ["currency" => "USD", "status" => "pending", "amount" => 50],
            "eur-second" => ["currency" => "EUR", "status" => "paid", "amount" => "20.5"],
            "eur-third" => ["currency" => "EUR", "status" => "pending", "amount" => 30],
            "usd-second" => ["currency" => "USD", "status" => "paid", "amount" => 25],
        ]);
    }

    public function testSummarizeByBuildsAFlatGroupedReport(): void
    {
        $summary = $this->orders->summarizeBy(
            "currency",
            [
                "orders" => fn(Block $group): int => $group->count(),
                "paid_orders" => fn(Block $group): int => $group->countWhere("status", "paid"),
                "paid_amount" => fn(Block $group): int|float => $group->sumWhere("amount", "status", "paid"),
            ],
        );

        $this->assertSame([
            [
                "currency" => "EUR",
                "orders" => 3,
                "paid_orders" => 2,
                "paid_amount" => 30.5,
            ],
            [
                "currency" => "USD",
                "orders" => 2,
                "paid_orders" => 1,
                "paid_amount" => 25,
            ],
        ], $summary->toArray());
    }

    public function testSummarizeBySupportsAGroupAliasAndDefaultGroup(): void
    {
        $rows = Block::make([
            ["team" => "blue", "amount" => 10],
            ["team" => null, "amount" => 20],
            ["amount" => 30],
        ]);

        $summary = $rows->summarizeBy(
            "team",
            ["amount" => fn(Block $group): int|float => $group->sum("amount")],
            defaultGroup: "unassigned",
            groupAs: "group",
        );

        $this->assertSame([
            ["group" => "blue", "amount" => 10],
            ["group" => "unassigned", "amount" => 50],
        ], $summary->toArray());
    }

    public function testSummarizeBySupportsNestedGroupingFields(): void
    {
        $rows = Block::make([
            ["customer" => ["region" => "north"], "amount" => 10],
            ["customer" => ["region" => "south"], "amount" => 20],
            ["customer" => ["region" => "north"], "amount" => 30],
        ]);

        $summary = $rows->summarizeBy(
            "customer.region",
            ["amount" => fn(Block $group): int|float => $group->sum("amount")],
            groupAs: "region",
        );

        $this->assertSame([
            ["region" => "north", "amount" => 40],
            ["region" => "south", "amount" => 20],
        ], $summary->toArray());
    }

    public function testMetricCallbacksReceiveTheGroupValue(): void
    {
        $summary = $this->orders->summarizeBy(
            "currency",
            [
                "label" => fn(Block $group, string $currency): string => $currency
                    . ":"
                    . $group->count(),
            ],
        );

        $this->assertSame("EUR:3", $summary->get("0.label"));
        $this->assertSame("USD:2", $summary->get("1.label"));
    }

    public function testMetricsReceiveIndependentGroupSnapshots(): void
    {
        $summary = $this->orders->summarizeBy(
            "currency",
            [
                "mutating_metric" => function (Block $group): int {
                    $group->appendItem(["status" => "paid", "amount" => 100]);

                    return $group->count();
                },
                "original_count" => fn(Block $group): int => $group->count(),
            ],
        );

        $this->assertSame(4, $summary->get("0.mutating_metric"));
        $this->assertSame(3, $summary->get("0.original_count"));
        $this->assertSame(3, $summary->get("1.mutating_metric"));
        $this->assertSame(2, $summary->get("1.original_count"));
    }

    public function testSummarizeByPreservesFirstSeenGroupOrder(): void
    {
        $summary = $this->orders->summarizeBy("currency", []);

        $this->assertSame([
            ["currency" => "EUR"],
            ["currency" => "USD"],
        ], $summary->toArray());
    }

    public function testMissingGroupsAreSkippedWithoutADefault(): void
    {
        $rows = Block::make([
            ["group" => null],
            ["name" => "missing"],
            ["group" => "present"],
            "scalar",
        ]);

        $this->assertSame(
            [["group" => "present", "count" => 1]],
            $rows->summarizeBy(
                "group",
                ["count" => fn(Block $group): int => $group->count()],
            )->toArray(),
        );
    }

    public function testFalseyGroupValuesFollowPhpArrayKeySemantics(): void
    {
        $rows = Block::make([
            ["group" => 0, "amount" => 1],
            ["group" => "0", "amount" => 2],
            ["group" => false, "amount" => 4],
            ["group" => "", "amount" => 8],
            ["group" => null, "amount" => 16],
            ["amount" => 32],
        ]);

        $summary = $rows->summarizeBy(
            "group",
            [
                "count" => fn(Block $group): int => $group->count(),
                "amount" => fn(Block $group): int|float => $group->sum("amount"),
            ],
            defaultGroup: "unknown",
        );

        $this->assertSame([
            ["group" => 0, "count" => 3, "amount" => 7],
            ["group" => "", "count" => 1, "amount" => 8],
            ["group" => "unknown", "count" => 2, "amount" => 48],
        ], $summary->toArray());
    }

    public function testMetricDefinitionsAreValidatedForEmptyBlocks(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Summary metric 'invalid' must be defined by a callable");

        Block::make([])->summarizeBy("group", ["invalid" => "not-callable"]);
    }

    public function testGroupColumnCannotConflictWithAMetricName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Summary group column 'group' conflicts with a metric name");

        Block::make([])->summarizeBy(
            "category",
            ["group" => fn(): int => 1],
            groupAs: "group",
        );
    }

    public function testSummarizeByReturnsAnEmptyBlockForEmptyInput(): void
    {
        $summary = Block::make([])->summarizeBy(
            "group",
            ["count" => fn(Block $group): int => $group->count()],
        );

        $this->assertSame([], $summary->toArray());
    }

    public function testSummarizeByDoesNotModifyTheSourceBlock(): void
    {
        $original = $this->orders->toArray();

        $this->orders->summarizeBy(
            "currency",
            ["orders" => fn(Block $group): int => $group->count()],
        );

        $this->assertSame($original, $this->orders->toArray());
    }

    public function testSummaryPreservesNativeArrayIterationMode(): void
    {
        $rows = Block::make([
            ["group" => "a"],
            ["group" => "a"],
        ], false);

        $summary = $rows->summarizeBy(
            "group",
            ["count" => fn(Block $group): int => $group->count()],
        );

        $this->assertIsArray($summary->first());
        $this->assertSame(["group" => "a", "count" => 2], $summary->first());
    }
}
