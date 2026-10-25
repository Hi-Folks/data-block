<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BlockRequiredGettersTest extends TestCase
{
    public function testRequiredAccessorsParseValidValues(): void
    {
        $row = Block::make([
            "name" => "Data Block",
            "quantity" => "0042",
            "integral_float" => 12.0,
            "amount" => "19.75",
            "enabled" => "YeS",
            "disabled" => " off ",
        ]);

        $this->assertSame("Data Block", $row->requireString("name"));
        $this->assertSame(42, $row->requireInt("quantity"));
        $this->assertSame(12, $row->requireInt("integral_float"));
        $this->assertSame(19.75, $row->requireFloat("amount"));
        $this->assertTrue($row->requireBoolean("enabled"));
        $this->assertFalse($row->requireBoolean("disabled"));
    }

    public function testRequiredAccessorsSupportNestedKeysAndCustomSeparators(): void
    {
        $row = Block::make([
            "order" => [
                "customer" => ["name" => "Ada"],
                "quantity" => "3",
                "amount" => 9,
                "paid" => "true",
            ],
        ]);

        $this->assertSame("Ada", $row->requireString("order#customer#name", "#"));
        $this->assertSame(3, $row->requireInt("order#quantity", "#"));
        $this->assertSame(9.0, $row->requireFloat("order#amount", "#"));
        $this->assertTrue($row->requireBoolean("order#paid", "#"));
    }

    #[DataProvider("requiredAccessors")]
    public function testMissingFieldsThrowAnOutOfBoundsException(string $method): void
    {
        $row = Block::make([]);

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage("Required field 'quantity' is missing");

        $row->{$method}("quantity");
    }

    #[DataProvider("requiredAccessors")]
    public function testNullFieldsThrowAnUnexpectedValueException(string $method): void
    {
        $row = Block::make(["amount" => null]);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Required field 'amount' is null");

        $row->{$method}("amount");
    }

    /** @return iterable<string, array{string}> */
    public static function requiredAccessors(): iterable
    {
        yield "string" => ["requireString"];
        yield "integer" => ["requireInt"];
        yield "float" => ["requireFloat"];
        yield "boolean" => ["requireBoolean"];
    }

    #[DataProvider("invalidStringValues")]
    public function testRequireStringRejectsBlankAndNonStringValues(mixed $value): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Required field 'value' must contain a non-blank string");

        Block::make(["value" => $value])->requireString("value");
    }

    /** @return iterable<string, array{mixed}> */
    public static function invalidStringValues(): iterable
    {
        yield "empty string" => [""];
        yield "whitespace-only string" => ["  \t"];
        yield "integer" => [12];
        yield "boolean" => [true];
        yield "array" => [["text"]];
        yield "object" => [new stdClass()];
    }

    #[DataProvider("invalidIntegerValues")]
    public function testRequireIntRejectsInvalidValues(mixed $value): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Required field 'value' must contain an integer");

        Block::make(["value" => $value])->requireInt("value");
    }

    /** @return iterable<string, array{mixed}> */
    public static function invalidIntegerValues(): iterable
    {
        yield "fractional float" => [12.5];
        yield "fractional numeric string" => ["12.5"];
        yield "non-numeric string" => ["twelve"];
        yield "boolean" => [true];
        yield "array" => [[12]];
        yield "object" => [new stdClass()];
        yield "integer overflow" => [PHP_INT_MAX . "0"];
    }

    #[DataProvider("invalidFloatValues")]
    public function testRequireFloatRejectsInvalidValues(mixed $value): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Required field 'value' must contain a finite number");

        Block::make(["value" => $value])->requireFloat("value");
    }

    /** @return iterable<string, array{mixed}> */
    public static function invalidFloatValues(): iterable
    {
        yield "empty string" => [""];
        yield "non-numeric string" => ["many"];
        yield "boolean" => [false];
        yield "infinity" => [INF];
        yield "array" => [[1.5]];
        yield "object" => [new stdClass()];
    }

    #[DataProvider("booleanValues")]
    public function testRequireBooleanUsesAnExplicitVocabulary(
        mixed $value,
        bool $expected,
    ): void {
        $this->assertSame(
            $expected,
            Block::make(["value" => $value])->requireBoolean("value"),
        );
    }

    /** @return iterable<string, array{mixed, bool}> */
    public static function booleanValues(): iterable
    {
        yield "native true" => [true, true];
        yield "native false" => [false, false];
        yield "integer one" => [1, true];
        yield "integer zero" => [0, false];
        yield "true string" => ["true", true];
        yield "false string" => ["false", false];
        yield "string one" => ["1", true];
        yield "string zero" => ["0", false];
        yield "yes string" => ["yes", true];
        yield "no string" => ["no", false];
        yield "on string" => ["on", true];
        yield "off string" => ["off", false];
        yield "case and whitespace" => [" TRUE ", true];
    }

    #[DataProvider("invalidBooleanValues")]
    public function testRequireBooleanRejectsAmbiguousValues(mixed $value): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Required field 'value' must contain a boolean");

        Block::make(["value" => $value])->requireBoolean("value");
    }

    /** @return iterable<string, array{mixed}> */
    public static function invalidBooleanValues(): iterable
    {
        yield "empty string" => [""];
        yield "unknown string" => ["enabled"];
        yield "integer two" => [2];
        yield "float one" => [1.0];
        yield "array" => [[true]];
        yield "object" => [new stdClass()];
    }
}
