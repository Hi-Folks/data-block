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

    public function testToJsonAcceptsEncodingFlags(): void
    {
        $data = Block::make(["emoji" => "🥑"]);

        $this->assertSame(
            '{"emoji":"🥑"}',
            $data->toJson(flags: JSON_UNESCAPED_UNICODE),
        );
        $this->assertStringContainsString(
            "\n",
            $data->toJson(
                flags: JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
            ),
        );
    }

    public function testToJsonReturnsFalseWhenEncodingFails(): void
    {
        $data = Block::make(["invalid" => "\xB1\x31"]);

        $this->assertFalse($data->toJson());
    }

    public function testToJsonCanThrowWhenEncodingFails(): void
    {
        $data = Block::make(["invalid" => "\xB1\x31"]);

        $this->expectException(JsonException::class);

        $data->toJson(flags: JSON_THROW_ON_ERROR);
    }

    public function testToJsonHonorsTheMaximumDepth(): void
    {
        $data = Block::make(["outer" => ["inner" => "value"]]);

        $this->assertFalse($data->toJson(depth: 1));
    }

    public function testToJsonRejectsANonPositiveDepth(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage("JSON depth must be greater than zero");

        Block::make([])->toJson(depth: 0);
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

    public function testSaveToJsonUsesTheRequestedEncodingFlags(): void
    {
        $filename = tempnam(sys_get_temp_dir(), "data-block-json-");
        $this->assertIsString($filename);

        try {
            $result = Block::make(["emoji" => "🥑"])->saveToJson(
                $filename,
                overwrite: true,
                flags: JSON_UNESCAPED_UNICODE,
            );

            $this->assertTrue($result);
            $this->assertSame('{"emoji":"🥑"}', file_get_contents($filename));
        } finally {
            if (file_exists($filename)) {
                unlink($filename);
            }
        }
    }

    public function testSaveToJsonReturnsFalseWhenEncodingFails(): void
    {
        $filename = sys_get_temp_dir() . "/data-block-invalid-" . uniqid() . ".json";

        $result = Block::make(["invalid" => "\xB1\x31"])->saveToJson($filename);

        $this->assertFalse($result);
        $this->assertFileDoesNotExist($filename);
    }

    public function testSaveToJsonReturnsFalseWhenWritingFails(): void
    {
        $filename = sys_get_temp_dir()
            . "/data-block-missing-"
            . uniqid()
            . "/output.json";

        $this->assertFalse(Block::make(["valid" => true])->saveToJson($filename));
        $this->assertFileDoesNotExist($filename);
    }

    public function testSaveToJsonCanThrowWhenEncodingFails(): void
    {
        $filename = sys_get_temp_dir() . "/data-block-invalid-" . uniqid() . ".json";

        try {
            $this->expectException(JsonException::class);

            Block::make(["invalid" => "\xB1\x31"])->saveToJson(
                $filename,
                flags: JSON_THROW_ON_ERROR,
            );
        } finally {
            if (file_exists($filename)) {
                unlink($filename);
            }
        }
    }

    public function testEmptyJson(): void
    {
        $data = Block::fromJsonFile("file-not-exists");
        $this->assertSame(0, $data->count());
    }
}
