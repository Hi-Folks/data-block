<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\TestCase;

final class BlockMissingKeyTest extends TestCase
{
    public function testMissingKeysAreSilentByDefault(): void
    {
        $block = Block::make(["present" => "value"]);

        $this->assertSame("fallback", $block->get("missing", "fallback"));
    }

    public function testWarningModeEmitsAUserWarningAndReturnsTheDefault(): void
    {
        $block = Block::make([]);
        $warnings = [];

        set_error_handler(
            function (int $severity, string $message) use (&$warnings): bool {
                $warnings[] = [$severity, $message];

                return true;
            },
            E_USER_WARNING,
        );

        try {
            $configured = $block->warnOnMissingKey();
            $result = $block->get("missing", "fallback");
        } finally {
            restore_error_handler();
        }

        $this->assertSame($block, $configured);
        $this->assertSame("fallback", $result);
        $this->assertSame(
            [[E_USER_WARNING, "Undefined array key: missing"]],
            $warnings,
        );
    }

    public function testExceptionModeThrowsTheDefaultExceptionWithTheMissingKey(): void
    {
        $block = Block::make([]);

        $this->assertSame($block, $block->throwOnMissingKey());

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage("Undefined array key: missing");

        $block->get("missing");
    }

    public function testExceptionModeSupportsACustomExceptionAndContextHint(): void
    {
        $block = Block::make([])->throwOnMissingKey(
            RuntimeException::class,
            "while reading the application configuration",
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Undefined array key: database.host (while reading the application configuration)",
        );

        $block->get("database.host");
    }

    public function testExceptionModeRejectsAClassThatIsNotThrowable(): void
    {
        $block = Block::make([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Exception class must extend Throwable");

        /** @phpstan-ignore argument.type Testing runtime validation of an invalid class. */
        $block->throwOnMissingKey(stdClass::class);
    }

    public function testSilentModeClearsPreviousExceptionConfiguration(): void
    {
        $block = Block::make([])->throwOnMissingKey(
            RuntimeException::class,
            "context that must be cleared",
        );

        $configured = $block->silentOnMissingKey();

        $this->assertSame($block, $configured);
        $this->assertSame("fallback", $block->get("missing", "fallback"));
    }
}
