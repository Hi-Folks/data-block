<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\TestCase;

final class BlockCoreCoverageTest extends TestCase
{
    public function testFromEncodedJsonCreatesABlockFromAssociativeData(): void
    {
        $data = [
            "name" => "Ada",
            "languages" => ["PHP", "C++"],
        ];

        $block = Block::fromEncodedJson($data);

        $this->assertSame($data, $block->toArray());
        $this->assertSame("PHP", $block->get("languages.0"));
    }

    public function testFromEncodedJsonCreatesABlockFromListData(): void
    {
        $data = ["Ada", "Grace"];

        $block = Block::fromEncodedJson($data);

        $this->assertSame($data, $block->toArray());
    }

    public function testEntriesReturnsEveryKeyValuePairInOrder(): void
    {
        $block = Block::make([
            "name" => "Ada",
            0 => "zero",
            "" => "empty key",
        ]);

        $entries = $block->entries();

        $this->assertSame([
            ["name", "Ada"],
            [0, "zero"],
            ["", "empty key"],
        ], $entries->toArray());
        $this->assertSame([
            "name" => "Ada",
            0 => "zero",
            "" => "empty key",
        ], $block->toArray());
    }

    public function testKeysCanBeReturnedAsABlock(): void
    {
        $source = Block::make([
            "name" => "Ada",
            5 => "five",
            "" => "empty key",
        ]);

        $keys = $source->keys(returnBlockClass: true);

        $this->assertInstanceOf(Block::class, $keys);
        $this->assertSame(["name", 5, ""], $keys->toArray());
        $this->assertSame(
            ["name" => "Ada", 5 => "five", "" => "empty key"],
            $source->toArray(),
        );
    }

    public function testGetTraversesValuesStoredAsBlockInstances(): void
    {
        $profile = Block::make([
            "details" => Block::make(["name" => "Ada"]),
        ]);
        $data = Block::make(["profile" => $profile]);

        $this->assertSame("Ada", $data->get("profile.details.name"));
    }

    public function testGetBlockNullableReturnsAnExistingBlockInstance(): void
    {
        $profile = Block::make(["name" => "Ada"]);
        $data = Block::make(["profile" => $profile]);

        $result = $data->getBlockNullable("profile");

        $this->assertSame($profile, $result);
    }

    public function testGetBlockNullableReturnsAnEmptyBlockForUnsupportedObjects(): void
    {
        $data = Block::make(["value" => new stdClass()]);

        $result = $data->getBlockNullable("value");

        $this->assertInstanceOf(Block::class, $result);
        $this->assertSame([], $result->toArray());
    }

    public function testSetSupportsIntegerKeysAndReturnsTheSameInstance(): void
    {
        $block = Block::make([0 => "zero"]);

        $result = $block->set(5, "five");

        $this->assertSame($block, $result);
        $this->assertSame([0 => "zero", 5 => "five"], $block->toArray());
    }
}
