<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BlockMultiColumnSummaryTest extends TestCase
{
    private Block $orders;

    protected function setUp(): void
    {
        $this->orders = Block::make([
            ["region" => "Europe", "status" => "paid", "amount" => 10],
            ["region" => "America", "status" => "paid", "amount" => 25],
            ["region" => "Europe", "status" => "pending", "amount" => 50],
            ["region" => "Europe", "status" => "paid", "amount" => "20.5"],
            ["region" => "America", "status" => "pending", "amount" => 15],
        ]);
    }

    public function testSummarizeByManyBuildsAFlatReport(): void
    {
        $summary = $this->orders->summarizeByMany(
            ["region", "status"],
            [
                "orders" => fn(Block $group): int => $group->count(),
                "amount" => fn(Block $group): int|float => $group->sum("amount"),
            ],
        );

        $this->assertSame([
            ["region" => "Europe", "status" => "paid", "orders" => 2, "amount" => 30.5],
            ["region" => "America", "status" => "paid", "orders" => 1, "amount" => 25],
            ["region" => "Europe", "status" => "pending", "orders" => 1, "amount" => 50],
            ["region" => "America", "status" => "pending", "orders" => 1, "amount" => 15],
        ], $summary->toArray());
    }

    public function testCountByManyIsAConvenientFlatCountReport(): void
    {
        $counts = $this->orders->countByMany(
            ["region", "status"],
            countAs: "orders",
        );

        $this->assertSame([
            ["region" => "Europe", "status" => "paid", "orders" => 2],
            ["region" => "America", "status" => "paid", "orders" => 1],
            ["region" => "Europe", "status" => "pending", "orders" => 1],
            ["region" => "America", "status" => "pending", "orders" => 1],
        ], $counts->toArray());
    }

    public function testGroupingCombinationsPreserveValueTypesWithoutCollisions(): void
    {
        $rows = Block::make([
            ["group" => 0, "type" => "x"],
            ["group" => "0", "type" => "x"],
            ["group" => false, "type" => "x"],
        ]);

        $this->assertSame([
            ["group" => 0, "type" => "x", "count" => 1],
            ["group" => "0", "type" => "x", "count" => 1],
            ["group" => false, "type" => "x", "count" => 1],
        ], $rows->countByMany(["group", "type"])->toArray());
    }

    public function testNestedFieldsAndDefaultsPerFieldAreSupported(): void
    {
        $rows = Block::make([
            ["customer" => ["region" => "north"], "status" => "paid"],
            ["customer" => ["region" => "north"], "status" => null],
            ["customer" => [], "status" => "paid"],
            ["status" => null],
        ]);

        $counts = $rows->countByMany(
            ["customer.region", "status"],
            defaultGroups: [
                "customer.region" => "unknown-region",
                "status" => "unknown-status",
            ],
        );

        $this->assertSame([
            ["customer.region" => "north", "status" => "paid", "count" => 1],
            ["customer.region" => "north", "status" => "unknown-status", "count" => 1],
            ["customer.region" => "unknown-region", "status" => "paid", "count" => 1],
            ["customer.region" => "unknown-region", "status" => "unknown-status", "count" => 1],
        ], $counts->toArray());
    }

    public function testRowsMissingAnyUndefaultedGroupingFieldAreSkipped(): void
    {
        $rows = Block::make([
            ["region" => "Europe", "status" => "paid"],
            ["region" => "Europe"],
            ["status" => "paid"],
            "scalar",
        ]);

        $this->assertSame(
            [["region" => "Europe", "status" => "paid", "count" => 1]],
            $rows->countByMany(["region", "status"])->toArray(),
        );
    }

    public function testMetricCallbacksReceiveAllGroupValuesAndIndependentGroups(): void
    {
        $summary = $this->orders->summarizeByMany(
            ["region", "status"],
            [
                "mutated_count" => function (Block $group): int {
                    $group->appendItem(["amount" => 100]);

                    return $group->count();
                },
                "label" => fn(Block $group, array $values): string => $values["region"]
                    . ":"
                    . $values["status"]
                    . ":"
                    . $group->count(),
            ],
        );

        $this->assertSame(3, $summary->get("0.mutated_count"));
        $this->assertSame("Europe:paid:2", $summary->get("0.label"));
    }

    #[DataProvider("invalidGroupingFields")]
    public function testGroupingFieldsAreValidated(array $fields, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        Block::make([])->summarizeByMany($fields, []);
    }

    /** @return iterable<string, array{array<mixed>, string}> */
    public static function invalidGroupingFields(): iterable
    {
        yield "empty" => [[], "at least two fields"];
        yield "one field" => [["region"], "at least two fields"];
        yield "associative" => [["first" => "region", "status"], "must be a list"];
        yield "duplicate" => [["region", "region"], "must be unique"];
        yield "empty field" => [["region", ""], "must be non-empty"];
        yield "unsupported field" => [["region", []], "must be non-empty"];
    }

    public function testMetricsAndOutputColumnNamesAreValidatedForEmptyInput(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("conflicts with a metric name");

        Block::make([])->summarizeByMany(
            ["region", "status"],
            ["status" => fn(): int => 1],
        );
    }

    public function testInvalidMetricIsRejectedForEmptyInput(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("must be defined by a callable");

        Block::make([])->summarizeByMany(
            ["region", "status"],
            ["count" => "not-callable"],
        );
    }

    public function testDefaultsMustReferenceGroupingFieldsAndContainScalarValues(): void
    {
        try {
            Block::make([])->countByMany(
                ["region", "status"],
                ["missing" => "unknown"],
            );
            $this->fail("An unknown default field should be rejected");
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString(
                "is not a configured grouping field",
                $exception->getMessage(),
            );
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("must be scalar or null");

        Block::make([])->countByMany(
            ["region", "status"],
            ["region" => []],
        );
    }

    public function testEmptyInputAndSourceImmutability(): void
    {
        $this->assertSame(
            [],
            Block::make([])->countByMany(["region", "status"])->toArray(),
        );

        $original = $this->orders->toArray();
        $this->orders->countByMany(["region", "status"]);

        $this->assertSame($original, $this->orders->toArray());
    }

    public function testResultPreservesNativeArrayIterationMode(): void
    {
        $rows = Block::make([
            ["region" => "Europe", "status" => "paid"],
        ], false);

        $result = $rows->countByMany(["region", "status"]);

        $this->assertIsArray($result->first());
    }
}
