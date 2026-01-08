<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\TestCase;

final class BlockSetTest extends TestCase
{
    public function testBlockSetFromEmptyBlock(): void
    {
        $articleText = "Some words as a sample sentence";

        $textField = Block::make();

        $textField->set("type", "doc");
        $textField->set("content.0.content.0.text", $articleText);
        $textField->set("content.0.content.0.type", "text");
        $textField->set("content.0.type", "paragraph");

        $this->assertSame(
            $articleText,
            $textField->get("content.0.content.0.text"),
        );

        $this->assertIsArray($textField->get("content.0.content.0"));

        $this->assertCount(2, $textField->get("content.0.content.0"));

        $this->assertNull($textField->get("content.0.content.0.newfield"));

        $textField->set("content.0.content.0.newfield", "THIS IS A NEW FIELD");

        $this->assertIsString($textField->get("content.0.content.0.newfield"));

        $this->assertSame(
            "THIS IS A NEW FIELD",
            $textField->get("content.0.content.0.newfield"),
        );

        $this->assertIsArray($textField->get("content.0.content.0"));

        $this->assertCount(3, $textField->get("content.0.content.0"));
    }
}
