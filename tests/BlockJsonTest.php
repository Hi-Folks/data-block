<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\TestCase;

final class BlockJsonTest extends TestCase
{
    private ?string $temporaryDirectory = null;

    /** @var list<string> */
    private array $registeredFiles = [];

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

    protected function tearDown(): void
    {
        try {
            $unexpectedFiles = $this->cleanTemporaryDirectory();

            self::assertSame(
                [],
                $unexpectedFiles,
                "The test created unexpected temporary files",
            );
        } finally {
            parent::tearDown();
        }
    }

    private function temporaryPath(string $filename): string
    {
        if ($this->temporaryDirectory === null) {
            $this->temporaryDirectory = sys_get_temp_dir()
                . DIRECTORY_SEPARATOR
                . "data-block-json-"
                . bin2hex(random_bytes(8));

            if (!mkdir($this->temporaryDirectory, 0700)) {
                throw new RuntimeException("Unable to create the temporary test directory");
            }
        }

        $path = $this->temporaryDirectory . DIRECTORY_SEPARATOR . $filename;
        $this->registeredFiles[] = $path;

        return $path;
    }

    /** @return list<string> */
    private function cleanTemporaryDirectory(): array
    {
        if (
            $this->temporaryDirectory === null
            || !is_dir($this->temporaryDirectory)
        ) {
            return [];
        }

        foreach (array_reverse($this->registeredFiles) as $path) {
            if (is_file($path) || is_link($path)) {
                unlink($path);
            }
        }

        $unexpectedFiles = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->temporaryDirectory,
                FilesystemIterator::SKIP_DOTS,
            ),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            $unexpectedFiles[] = substr(
                $item->getPathname(),
                strlen($this->temporaryDirectory) + 1,
            );

            if ($item->isDir() && !$item->isLink()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($this->temporaryDirectory);

        return $unexpectedFiles;
    }

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
        $filename = $this->temporaryPath("fruits.json");

        $data->saveToJson($filename);

        $this->assertFileExists($filename);
    }

    public function testSaveToJsonWithOverwrite(): void
    {
        $data = Block::make($this->fruitsArray);
        $filename = $this->temporaryPath("fruits.json");

        $data->saveToJson($filename);

        $this->assertFileExists($filename);

        $result = $data->saveToJson($filename, true);

        $this->assertTrue($result);
    }

    public function testSaveToJsonWithExistingFile(): void
    {
        $data = Block::make($this->fruitsArray);
        $filename = $this->temporaryPath("fruits.json");

        $data->saveToJson($filename);

        $this->assertFileExists($filename);

        $result = $data->saveToJson($filename);

        $this->assertFalse($result);
    }

    public function testSaveToJsonUsesTheRequestedEncodingFlags(): void
    {
        $filename = $this->temporaryPath("unicode.json");

        $result = Block::make(["emoji" => "🥑"])->saveToJson(
            $filename,
            flags: JSON_UNESCAPED_UNICODE,
        );

        $this->assertTrue($result);
        $this->assertSame('{"emoji":"🥑"}', file_get_contents($filename));
    }

    public function testSaveToJsonReturnsFalseWhenEncodingFails(): void
    {
        $filename = $this->temporaryPath("invalid.json");

        $result = Block::make(["invalid" => "\xB1\x31"])->saveToJson($filename);

        $this->assertFalse($result);
        $this->assertFileDoesNotExist($filename);
    }

    public function testSaveToJsonReturnsFalseWhenWritingFails(): void
    {
        $filename = $this->temporaryPath(
            "missing" . DIRECTORY_SEPARATOR . "output.json",
        );

        $this->assertFalse(Block::make(["valid" => true])->saveToJson($filename));
        $this->assertFileDoesNotExist($filename);
    }

    public function testSaveToJsonCanThrowWhenEncodingFails(): void
    {
        $filename = $this->temporaryPath("invalid.json");

        $this->expectException(JsonException::class);

        Block::make(["invalid" => "\xB1\x31"])->saveToJson(
            $filename,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    public function testEmptyJson(): void
    {
        $data = Block::fromJsonFile("file-not-exists");
        $this->assertSame(0, $data->count());
    }
}
