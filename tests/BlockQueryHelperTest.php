<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use HiFolks\DataType\Enums\Operator;
use PHPUnit\Framework\TestCase;

final class BlockQueryHelperTest extends TestCase
{
    private Block $rows;

    protected function setUp(): void
    {
        $this->rows = Block::make([
            "null" => ["amount" => null, "close_date" => null],
            "missing" => ["name" => "Missing fields"],
            "zero" => ["amount" => 0, "close_date" => "2026-01-01", "stage" => 0],
            "false" => ["amount" => false, "close_date" => false],
            "empty" => ["amount" => "", "close_date" => ""],
            "proposal" => [
                "amount" => 100,
                "close_date" => "2026-02-15",
                "stage" => "Proposal",
                "tags" => ["priority", "sales"],
            ],
            "negotiation" => [
                "amount" => "100",
                "close_date" => "2026-03-31",
                "stage" => "Negotiation",
                "tags" => "priority",
            ],
            "late" => [
                "amount" => 200,
                "close_date" => "2026-04-01",
                "stage" => "Closed",
            ],
        ]);
    }

    public function testStrictEqualityDoesNotCoerceValues(): void
    {
        $this->assertSame(
            ["proposal"],
            $this->rows->where("amount", Operator::STRICT_EQUAL, 100)->keys(),
        );
        $this->assertSame(
            ["proposal", "negotiation"],
            $this->rows->where("amount", Operator::EQUAL, 100)->keys(),
        );
    }

    public function testUnknownOperatorsAreRejectedEvenForEmptyBlocks(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unsupported query operator");

        Block::make([])->where("amount", "approximately", 100);
    }

    public function testWhereNullMatchesExplicitNullAndMissingFieldsOnly(): void
    {
        $this->assertSame(
            ["null", "missing"],
            $this->rows->whereNull("amount")->keys(),
        );
    }

    public function testWhereNotNullRequiresAnExistingNonNullField(): void
    {
        $this->assertSame(
            ["zero", "false", "empty", "proposal", "negotiation", "late"],
            $this->rows->whereNotNull("amount")->keys(),
        );
    }

    public function testNullHelpersCanReindexResults(): void
    {
        $this->assertSame(
            [0, 1],
            $this->rows->whereNull("amount", preserveKeys: false)->keys(),
        );
    }

    public function testWhereBetweenIsInclusiveAndExcludesMissingNullAndIncompatibleValues(): void
    {
        $this->assertSame(
            ["zero", "proposal", "negotiation"],
            $this->rows->whereBetween(
                "close_date",
                "2026-01-01",
                "2026-03-31",
            )->keys(),
        );
    }

    public function testWhereBetweenSupportsNestedFields(): void
    {
        $rows = Block::make([
            ["period" => ["month" => 1]],
            ["period" => ["month" => 2]],
            ["period" => ["month" => 3]],
        ]);

        $this->assertSame(
            [1],
            $rows->whereBetween("period.month", 2, 2)->keys(),
        );
    }

    public function testWhereInUsesStrictComparisonByDefault(): void
    {
        $this->assertSame(
            ["proposal", "negotiation"],
            $this->rows->whereIn(
                "stage",
                ["Proposal", "Negotiation"],
            )->keys(),
        );
        $this->assertSame([], $this->rows->whereIn("stage", ["0"])->keys());
        $this->assertSame(
            ["zero"],
            $this->rows->whereIn("stage", ["0"], strict: false)->keys(),
        );
    }

    public function testInOperatorUsesStrictComparisonAndValidatesItsOperand(): void
    {
        $this->assertSame(
            [],
            $this->rows->where("stage", Operator::IN, ["0"])->keys(),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("expects an array");

        $this->rows->where("stage", Operator::IN, "Proposal");
    }

    public function testRelationalOperatorsNeverMatchMissingNullOrIncompatibleValues(): void
    {
        $this->assertSame(
            ["zero"],
            $this->rows->where(
                "close_date",
                Operator::LESS_THAN,
                "2026-02-01",
            )->keys(),
        );
        $this->assertSame(
            ["proposal", "negotiation", "late"],
            $this->rows->where(
                "close_date",
                Operator::GREATER_THAN,
                "2026-02-01",
            )->keys(),
        );
    }

    public function testHasAndLikeSafelyIgnoreMissingNullAndIncompatibleValues(): void
    {
        $this->assertSame(
            ["proposal"],
            $this->rows->where("tags", Operator::HAS, "priority")->keys(),
        );
        $this->assertSame(
            ["negotiation"],
            $this->rows->where("stage", Operator::LIKE, "ti")->keys(),
        );
    }
}
