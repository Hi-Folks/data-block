<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BlockDateTest extends TestCase
{
    public function testGetDateParsesAnIsoValueAutomatically(): void
    {
        $row = Block::make(["created_at" => "2026-09-20T14:30:00+02:00"]);

        $date = $row->getDate("created_at");

        $this->assertInstanceOf(DateTimeImmutable::class, $date);
        $this->assertSame("2026-09-20 14:30:00 +02:00", $date->format("Y-m-d H:i:s P"));
    }

    public function testGetDateUsesAnExplicitInputFormat(): void
    {
        $row = Block::make(["Close Date" => "20/09/2026"]);

        $date = $row->getDate("Close Date", inputFormat: "!d/m/Y");

        $this->assertInstanceOf(DateTimeImmutable::class, $date);
        $this->assertSame("2026-09-20 00:00:00", $date->format("Y-m-d H:i:s"));
    }

    public function testExplicitParsingNeverFallsBackToAutomaticParsing(): void
    {
        $row = Block::make(["date" => "2026-09-20"]);

        $this->assertNull(
            $row->getDate("date", inputFormat: "!d/m/Y"),
        );
    }

    #[DataProvider("invalidNullableValues")]
    public function testGetDateReturnsNullForMissingEmptyAndInvalidValues(
        array $data,
        string $key,
        ?string $inputFormat,
    ): void {
        $this->assertNull(
            Block::make($data)->getDate($key, $inputFormat),
        );
    }

    /** @return iterable<string, array{array<string, mixed>, string, string|null}> */
    public static function invalidNullableValues(): iterable
    {
        yield "missing" => [[], "date", "!d/m/Y"];
        yield "null" => [["date" => null], "date", "!d/m/Y"];
        yield "empty" => [["date" => ""], "date", "!d/m/Y"];
        yield "whitespace" => [["date" => "  "], "date", "!d/m/Y"];
        yield "invalid syntax" => [["date" => "not-a-date"], "date", null];
        yield "invalid calendar date" => [["date" => "31/02/2026"], "date", "!d/m/Y"];
    }

    public function testGetDateSupportsNestedPathsAndCustomSeparators(): void
    {
        $row = Block::make([
            "metadata" => ["dates" => ["close" => "20/09/2026"]],
        ]);

        $date = $row->getDate(
            "metadata/dates/close",
            inputFormat: "!d/m/Y",
            charNestedKey: "/",
        );

        $this->assertSame("2026-09-20", $date?->format("Y-m-d"));
    }

    public function testGetDateUsesTheProvidedTimezone(): void
    {
        $timezone = new DateTimeZone("Europe/Rome");
        $row = Block::make(["created_at" => "2026-09-20 14:30"]);

        $date = $row->getDate(
            "created_at",
            inputFormat: "!Y-m-d H:i",
            timezone: $timezone,
        );

        $this->assertSame("Europe/Rome", $date?->getTimezone()->getName());
        $this->assertSame("2026-09-20 14:30 +02:00", $date?->format("Y-m-d H:i P"));
    }

    public function testGetDateAcceptsExistingDateTimeObjects(): void
    {
        $mutable = new DateTime("2026-09-20T12:00:00+00:00");
        $row = Block::make(["date" => $mutable]);

        $date = $row->getDate(
            "date",
            timezone: new DateTimeZone("Europe/Rome"),
        );

        $this->assertInstanceOf(DateTimeImmutable::class, $date);
        $this->assertSame("2026-09-20 14:00 +02:00", $date->format("Y-m-d H:i P"));
    }

    public function testRequireDateReturnsAValidDate(): void
    {
        $row = Block::make(["date" => "20/09/2026"]);

        $date = $row->requireDate("date", inputFormat: "!d/m/Y");

        $this->assertSame("2026-09-20", $date->format("Y-m-d"));
    }

    #[DataProvider("requiredInvalidValues")]
    public function testRequireDateThrowsForMissingAndInvalidValues(
        array $data,
    ): void {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Field 'date' does not contain a valid date");

        Block::make($data)->requireDate("date", inputFormat: "!d/m/Y");
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function requiredInvalidValues(): iterable
    {
        yield "missing" => [[]];
        yield "null" => [["date" => null]];
        yield "empty" => [["date" => ""]];
        yield "invalid" => [["date" => "31/02/2026"]];
    }

    public function testGetFormattedDateParsesAndFormatsInOneStep(): void
    {
        $row = Block::make([
            "Close Date" => "20/09/2026",
            "empty" => "",
        ]);

        $this->assertSame(
            "2026-09-20",
            $row->getFormattedDate(
                "Close Date",
                outputFormat: "Y-m-d",
                inputFormat: "!d/m/Y",
            ),
        );
        $this->assertNull(
            $row->getFormattedDate(
                "empty",
                outputFormat: "Y-m-d",
                inputFormat: "!d/m/Y",
            ),
        );
    }
}
