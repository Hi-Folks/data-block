<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\TestCase;

final class BlockJsonTest extends TestCase
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

    public function testToJson(): void
    {
        $data = Block::make($this->fruitsArray);

        $this->assertIsString($data->toJson());

        $this->assertSame(773, strlen($data->toJson()));

        $string = $data->toJson();
        $data1 = Block::fromJsonString($string);

        $this->assertSame($data->get("0.fruit"), $data1->get("0.fruit"));
    }

    public function testToJsonWithDifferentIterateBlock(): void
    {
        $data1 = Block::make($this->fruitsArray, true);
        $string1 = $data1->toJson();

        $data2 = Block::make($this->fruitsArray, false);
        $string2 = $data2->toJson();

        $this->assertIsString($string1);
        $this->assertIsString($string2);

        $this->assertSame(773, strlen($string1));
        $this->assertSame(773, strlen($string2));

        $this->assertSame($string1, $string2);
    }

    public function testSaveToJson(): void
    {
        $data = Block::make($this->fruitsArray);

        $data->saveToJson("fruits.json");

        $this->assertFileExists("fruits.json");

        unlink("fruits.json");
    }

    public function testSaveToJsonWithOverwrite(): void
    {
        $data = Block::make($this->fruitsArray);

        $data->saveToJson("fruits.json");

        $this->assertFileExists("fruits.json");

        $result = $data->saveToJson("fruits.json", true);

        $this->assertTrue($result);

        unlink("fruits.json");
    }

    public function testSaveToJsonWithExistingFile(): void
    {
        $data = Block::make($this->fruitsArray);

        $data->saveToJson("fruits.json");

        $this->assertFileExists("fruits.json");

        $result = $data->saveToJson("fruits.json");

        $this->assertFalse($result);

        unlink("fruits.json");
    }
}
