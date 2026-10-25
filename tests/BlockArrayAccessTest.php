<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\TestCase;

final class BlockArrayAccessTest extends TestCase
{
    public function testArrayAccessReportsExistingAndMissingKeys(): void
    {
        $block = Block::make([
            "name" => "Ada",
            "nullable" => null,
        ]);

        $this->assertTrue(isset($block["name"]));
        $this->assertTrue(isset($block["nullable"]));
        $this->assertFalse(isset($block["missing"]));
    }

    public function testArrayAccessReadsTopLevelAndNestedValues(): void
    {
        $block = Block::make([
            "name" => "Ada",
            "profile" => ["language" => "PHP"],
        ]);

        $this->assertSame("Ada", $block["name"]);
        $this->assertSame("PHP", $block["profile.language"]);
        $this->assertNull($block["missing"]);
    }

    public function testArrayAccessReadUsesConfiguredMissingKeyBehavior(): void
    {
        $block = Block::make([])->throwOnMissingKey(
            RuntimeException::class,
            "while using array access",
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Undefined array key: missing (while using array access)",
        );

        $block["missing"];
    }

    public function testArrayAccessAssignsAndReplacesStringAndIntegerKeys(): void
    {
        $block = Block::make(["name" => "Ada", 5 => "old"]);

        $block["name"] = "Grace";
        $block["language"] = "PHP";
        $block[5] = "new";

        $this->assertSame([
            "name" => "Grace",
            5 => "new",
            "language" => "PHP",
        ], $block->toArray());
    }

    public function testArrayAccessAppendsWithoutAnExplicitKey(): void
    {
        $block = Block::make([2 => "existing"]);

        $block[] = "appended";

        $this->assertSame(
            [2 => "existing", 3 => "appended"],
            $block->toArray(),
        );
    }

    public function testArrayAccessRemovesExistingKeysAndIgnoresMissingKeys(): void
    {
        $block = Block::make([
            "keep" => "value",
            "remove" => "value",
        ]);

        unset($block["remove"]);
        unset($block["missing"]);

        $this->assertSame(["keep" => "value"], $block->toArray());
        $this->assertFalse(isset($block["remove"]));
    }
}
