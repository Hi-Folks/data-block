<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\TestCase;

final class BlockGetTest extends TestCase
{
    private array $fruitsArray = [
        "avocado" => [
            "name" => "Avocado",
            "fruit" => "🥑",
            "wikipedia" => "https://en.wikipedia.org/wiki/Avocado",
            "color" => "green",
            "rating" => 8,
        ],
        "apple" => [
            "name" => "Apple",
            "fruit" => "🍎",
            "wikipedia" => "https://en.wikipedia.org/wiki/Apple",
            "color" => "red",
            "rating" => 7,
        ],
        "banana" => [
            "name" => "Banana",
            "fruit" => "🍌",
            "wikipedia" => "https://en.wikipedia.org/wiki/Banana",
            "color" => "yellow",
            "rating" => 8.5,
        ],
        "cherry" => [
            "name" => "Cherry",
            "fruit" => "🍒",
            "wikipedia" => "https://en.wikipedia.org/wiki/Cherry",
            "color" => "red",
            "rating" => 9,
        ],
    ];

    public function testBlockMake(): void
    {
        $data = Block::make($this->fruitsArray);
        $this->assertInstanceOf(Block::class, $data);
        $this->assertCount(4, $data);

        $data = Block::make();
        $this->assertInstanceOf(Block::class, $data);
        $this->assertCount(0, $data);

        $data = Block::make([]);
        $this->assertInstanceOf(Block::class, $data);
        $this->assertCount(0, $data);
    }

    public function testBlockGet(): void
    {
        $data = Block::make($this->fruitsArray);

        $this->assertIsArray($data->get("avocado"));
        $this->assertCount(5, $data->get("avocado"));

        $this->assertIsString($data->get("avocado.color"));
        $this->assertSame("green", $data->get("avocado.color"));

        $this->assertSame("Avocado", $data->get("avocado.name"));

        $this->assertIsNumeric($data->get("avocado.rating"));
        $this->assertIsInt($data->get("avocado.rating"));
        $this->assertIsFloat($data->get("banana.rating"));

        $this->assertNull($data->get("avocado.notexists"));
        $this->assertSame(
            "NO VALUE",
            $data->get("avocado.notexists", "NO VALUE"),
        );
    }

    public function testBlockGetBlock(): void
    {
        $data = Block::make($this->fruitsArray);

        $this->assertInstanceOf(Block::class, $data->getBlock("avocado"));
        $this->assertCount(5, $data->getBlock("avocado"));

        $this->assertInstanceOf(Block::class, $data->getBlock("avocado.color"));
        $this->assertCount(1, $data->getBlock("avocado.color"));

        $this->assertInstanceOf(
            Block::class,
            $data->getBlock("avocado.notexists"),
        );
        $this->assertCount(0, $data->getBlock("avocado.notexists"));
    }

    public function testBlockKeys(): void
    {
        $data = Block::make($this->fruitsArray);

        $this->assertIsArray($data->getBlock("avocado")->keys());
        $this->assertCount(5, $data->getBlock("avocado")->keys());
        $this->assertSame("name", $data->getBlock("avocado")->keys()[0]);

        $this->assertIsArray($data->keys());
        $this->assertCount(4, $data->keys());
        $this->assertSame("avocado", $data->keys()[0]);
        $this->assertSame("apple", $data->keys()[1]);
    }

    public function testBasicGet(): void
    {
        $block = Block::make(["A", "B", "C"]);

        $this->assertSame("B", $block->get(1));
        $this->assertNull($block->get(4));
        $this->assertSame("AAAA", $block->get(4, "AAAA"));
    }

    public function testBasicNestedGet(): void
    {
        $block = Block::make([
            "A" => "First",
            "B" => ["some", "thing"],
            "C" => ["nested-item-1" => 10, "nested-item-2" => 20],
            "D" => [],
        ]);

        $this->assertSame("First", $block->get("A"));
        $this->assertIsArray($block->get("B"));
        $this->assertSame("some", $block->get("B.0"));
        $this->assertSame("thing", $block->get("B.1"));
        $this->assertNull($block->get("B.2"));
        $this->assertSame(1234, $block->get("B.2", 1234));

        $this->assertSame("some", $block->get("B#0", charNestedKey: "#"));
        $this->assertSame("thing", $block->get("B#1", charNestedKey: "#"));
        $this->assertNull($block->get("B#2", charNestedKey: "#"));
        $this->assertSame(1234, $block->get("B#2", 1234, "#"));

        $this->assertNull($block->get("C.0"));
        $this->assertSame(10, $block->get("C.nested-item-1"));
        $this->assertSame(20, $block->get("C.nested-item-2"));
        $this->assertNull($block->get("C.nested-item-2.other"));
        $this->assertSame("zzz", $block->get("C.nested-item-2.other", "zzz"));

        $this->assertNull($block->get("D.0"));
        $this->assertIsArray($block->get("D", "0"));
        $this->assertCount(0, $block->get("D", "0"));
    }

    public function testBasicGetBlockAdvanced(): void
    {
        $block = Block::make(["A", "B", "C"]);

        $this->assertInstanceOf(Block::class, $block->getBlock(1));
        $this->assertSame("B", $block->getBlock(1)->get(0));
        $this->assertNull($block->getBlockNullable(4));

        $b = $block->getBlock(4, "AAAA");
        $this->assertInstanceOf(Block::class, $b);
        $this->assertSame("AAAA", $b->get(0));
    }

    public function testGeneratesJson(): void
    {
        $block = Block::make([
            "A" => "First",
            "B" => ["some", "thing"],
            "C" => ["nested-item-1" => 10, "nested-item-2" => 20],
            "D" => [],
        ]);

        $json = $block->toJson();
        $this->assertIsString($json);

        $obj = json_decode($json);
        $this->assertInstanceOf(stdClass::class, $obj);
        $this->assertObjectHasProperty("C", $obj);
        $this->assertObjectHasProperty("nested-item-1", $obj->C);
    }

    public function testGeneratesJsonObject(): void
    {
        $block = Block::make([
            "A" => "First",
            "B" => ["some", "thing"],
            "C" => ["nested-item-1" => 10, "nested-item-2" => 20],
            "D" => [],
        ]);

        $obj = $block->toJsonObject();

        $this->assertInstanceOf(stdClass::class, $obj);
        $this->assertObjectHasProperty("C", $obj);
        $this->assertObjectHasProperty("nested-item-1", $obj->C);
    }
}
