<?php

declare(strict_types=1);

use HiFolks\DataType\Aggregation;
use HiFolks\DataType\Block;
use HiFolks\DataType\Enums\Operator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BlockAggregateByTest extends TestCase
{
    public function testItCalculatesAllBuiltInMetricsForEachGroup(): void
    {
        $rows = Block::make([
            ["column1" => "a", "column2" => "value1", "column3" => 10],
            ["column1" => "b", "column2" => "value2", "column3" => 25],
            ["column1" => "a", "column2" => "value1", "column3" => "20.5"],
            ["column1" => "a", "column2" => "value2", "column3" => 30],
            ["column1" => "b", "column2" => "value1", "column3" => "not-numeric"],
        ]);

        $report = $rows->aggregateBy(
            "column1",
            [
                "records" => Aggregation::count(),
                "matching_records" => Aggregation::countWhere("column2", "value1"),
                "total" => Aggregation::sum("column3"),
                "matching_total" => Aggregation::sumWhere(
                    "column3",
                    "column2",
                    "value1",
                ),
                "minimum" => Aggregation::min("column3"),
                "maximum" => Aggregation::max("column3"),
                "average" => Aggregation::average("column3"),
            ],
        );

        $this->assertSame([
            [
                "column1" => "a",
                "records" => 3,
                "matching_records" => 2,
                "total" => 60.5,
                "matching_total" => 30.5,
                "minimum" => 10,
                "maximum" => 30,
                "average" => 60.5 / 3,
            ],
            [
                "column1" => "b",
                "records" => 2,
                "matching_records" => 1,
                "total" => 25,
                "matching_total" => 0,
                "minimum" => 25,
                "maximum" => 25,
                "average" => 25.0,
            ],
        ], $report->toArray());
    }

    public function testItSupportsMultipleNestedGroupsDefaultsAndBlockRows(): void
    {
        $rows = Block::make([
            Block::make([
                "column1" => ["nested" => "a"],
                "column2" => "x",
                "column3" => 10,
            ]),
            ["column1" => ["nested" => "a"], "column2" => null, "column3" => 20],
            ["column1" => [], "column2" => "x", "column3" => 30],
            ["column2" => "x", "column3" => 40],
            "incompatible row",
        ]);

        $report = $rows->aggregateBy(
            ["column1.nested", "column2"],
            ["records" => Aggregation::count(), "total" => Aggregation::sum("column3")],
            [
                "column1.nested" => "default1",
                "column2" => "default2",
            ],
        );

        $this->assertSame([
            ["column1.nested" => "a", "column2" => "x", "records" => 1, "total" => 10],
            ["column1.nested" => "a", "column2" => "default2", "records" => 1, "total" => 20],
            ["column1.nested" => "default1", "column2" => "x", "records" => 2, "total" => 70],
        ], $report->toArray());
    }

    public function testGroupIdentitiesPreserveFalseyValueTypesAndFirstSeenOrder(): void
    {
        $rows = Block::make([
            ["column1" => false, "column2" => "x"],
            ["column1" => "0", "column2" => "x"],
            ["column1" => 0, "column2" => "x"],
            ["column1" => "", "column2" => "x"],
            ["column1" => false, "column2" => "x"],
        ]);

        $this->assertSame([
            ["column1" => false, "column2" => "x", "records" => 2],
            ["column1" => "0", "column2" => "x", "records" => 1],
            ["column1" => 0, "column2" => "x", "records" => 1],
            ["column1" => "", "column2" => "x", "records" => 1],
        ], $rows->aggregateBy(
            ["column1", "column2"],
            ["records" => Aggregation::count()],
        )->toArray());
    }

    public function testConditionalMetricsUseWhereComparisonSemantics(): void
    {
        $rows = Block::make([
            ["column1" => "a", "column2" => 1, "column3" => 10, "enabled" => true],
            ["column1" => "a", "column2" => "1", "column3" => 20, "enabled" => false],
            ["column1" => "a", "column2" => 2, "column3" => 30, "enabled" => true],
            ["column1" => "a", "column3" => 40],
        ]);

        $report = $rows->aggregateBy("column1", [
            "loose" => Aggregation::countWhere("column2", Operator::EQUAL, 1),
            "strict" => Aggregation::countWhere("column2", Operator::STRICT_EQUAL, 1),
            "selected" => Aggregation::countWhere("column2", Operator::IN, [1, 2]),
            "enabled" => Aggregation::countWhere("enabled"),
            "enabled_total" => Aggregation::sumWhere("column3", "enabled"),
        ]);

        $this->assertSame([[
            "column1" => "a",
            "loose" => 2,
            "strict" => 1,
            "selected" => 2,
            "enabled" => 2,
            "enabled_total" => 40,
        ]], $report->toArray());
    }

    public function testMetricFieldsSupportNestedPaths(): void
    {
        $rows = Block::make([
            [
                "column1" => "a",
                "column2" => ["nested" => "value1"],
                "column3" => ["nested" => 10],
            ],
            [
                "column1" => "a",
                "column2" => ["nested" => "value2"],
                "column3" => ["nested" => 20],
            ],
        ]);

        $this->assertSame([[
            "column1" => "a",
            "total" => 30,
            "matching_total" => 10,
        ]], $rows->aggregateBy("column1", [
            "total" => Aggregation::sum("column3.nested"),
            "matching_total" => Aggregation::sumWhere(
                "column3.nested",
                "column2.nested",
                "value1",
            ),
        ])->toArray());
    }

    public function testMetricsForTheSameFieldShareItsRowLookup(): void
    {
        $row = Block::make(["column1" => "a"])->warnOnMissingKey();
        $warnings = [];

        set_error_handler(
            function (int $severity, string $message) use (&$warnings): bool {
                if ($severity !== E_USER_WARNING) {
                    return false;
                }

                $warnings[] = $message;

                return true;
            },
        );

        try {
            $report = Block::make([$row])->aggregateBy("column1", [
                "total" => Aggregation::sum("column2"),
                "average" => Aggregation::average("column2"),
            ]);
        } finally {
            restore_error_handler();
        }

        $this->assertSame(["Undefined array key: column2"], $warnings);
        $this->assertSame([[
            "column1" => "a",
            "total" => 0,
            "average" => null,
        ]], $report->toArray());
    }

    public function testConfiguredRowAccessorExceptionsAreNotHidden(): void
    {
        $row = Block::make(["column1" => "a"])->throwOnMissingKey(
            UnexpectedValueException::class,
            "aggregation input",
        );

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            "Undefined array key: column2 (aggregation input)",
        );

        Block::make([$row])->aggregateBy(
            "column1",
            ["total" => Aggregation::sum("column2")],
        );
    }

    public function testNumericMetricsIgnoreMissingNullAndNonNumericValues(): void
    {
        $rows = Block::make([
            ["column1" => "a", "column2" => null],
            ["column1" => "a", "column2" => "invalid"],
            ["column1" => "a"],
        ]);

        $this->assertSame([[
            "column1" => "a",
            "sum" => 0,
            "minimum" => null,
            "maximum" => null,
            "average" => null,
        ]], $rows->aggregateBy("column1", [
            "sum" => Aggregation::sum("column2"),
            "minimum" => Aggregation::min("column2"),
            "maximum" => Aggregation::max("column2"),
            "average" => Aggregation::average("column2"),
        ])->toArray());
    }

    public function testRowsWithoutAConfiguredGroupAreSkipped(): void
    {
        $rows = Block::make([
            ["column1" => "a"],
            ["column1" => null],
            ["column2" => "missing"],
        ]);

        $this->assertSame(
            [["column1" => "a", "records" => 1]],
            $rows->aggregateBy(
                "column1",
                ["records" => Aggregation::count()],
            )->toArray(),
        );
    }

    public function testEmptyInputSourceImmutabilityAndIterationMode(): void
    {
        $this->assertSame(
            [],
            Block::make([])->aggregateBy(
                "column1",
                ["records" => Aggregation::count()],
            )->toArray(),
        );

        $rows = Block::make([["column1" => "a", "column2" => 10]], false);
        $original = $rows->toArray();
        $report = $rows->aggregateBy(
            "column1",
            ["total" => Aggregation::sum("column2")],
        );

        $this->assertSame($original, $rows->toArray());
        $this->assertIsArray($report->first());
    }

    #[DataProvider("invalidGroupingFields")]
    public function testGroupingFieldsAreValidatedEarly(
        int|string|array $fields,
        string $message,
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        Block::make([])->aggregateBy($fields, []);
    }

    /** @return iterable<string, array{int|string|array<mixed>, string}> */
    public static function invalidGroupingFields(): iterable
    {
        yield "empty" => [[], "non-empty list"];
        yield "associative" => [["first" => "column1"], "non-empty list"];
        yield "duplicate" => [["column1", "column1"], "must be unique"];
        yield "empty field" => [[""], "non-empty strings or integers"];
        yield "unsupported field" => [[[]], "non-empty strings or integers"];
    }

    public function testMetricDefinitionsAreValidatedBeforeReadingRows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Aggregation metric 'invalid' must be an Aggregation specification",
        );

        Block::make([])->aggregateBy("column1", ["invalid" => "sum"]);
    }

    public function testGroupColumnsCannotConflictWithMetricNames(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("conflicts with a metric name");

        Block::make([])->aggregateBy(
            "column1",
            ["column1" => Aggregation::count()],
        );
    }

    public function testDefaultGroupsAreValidated(): void
    {
        try {
            Block::make([])->aggregateBy(
                "column1",
                ["records" => Aggregation::count()],
                ["column2" => "default"],
            );
            $this->fail("An unknown default group should be rejected");
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString(
                "is not a configured grouping field",
                $exception->getMessage(),
            );
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("must be scalar or null");

        Block::make([])->aggregateBy(
            "column1",
            ["records" => Aggregation::count()],
            ["column1" => []],
        );
    }

    #[DataProvider("invalidAggregationDefinitions")]
    public function testAggregationDefinitionsAreValidated(
        Closure $definition,
        string $message,
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $definition();
    }

    /** @return iterable<string, array{Closure, string}> */
    public static function invalidAggregationDefinitions(): iterable
    {
        yield "empty value field" => [
            fn(): Aggregation => Aggregation::sum(""),
            "must not be empty",
        ];
        yield "empty condition field" => [
            fn(): Aggregation => Aggregation::countWhere(""),
            "must not be empty",
        ];
        yield "empty sum condition field" => [
            fn(): Aggregation => Aggregation::sumWhere("column1", ""),
            "must not be empty",
        ];
        yield "unsupported operator" => [
            fn(): Aggregation => Aggregation::countWhere("column1", "unsupported", true),
            "Unsupported query operator",
        ];
        yield "invalid in value" => [
            fn(): Aggregation => Aggregation::sumWhere(
                "column1",
                "column2",
                Operator::IN,
                "not-an-array",
            ),
            "expects an array",
        ];
    }
}
