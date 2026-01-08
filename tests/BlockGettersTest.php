<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\TestCase;

final class BlockGettersTest extends TestCase
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

    private function loadCommits(): Block
    {
        $data1 = Block::fromJsonFile(
            __DIR__ . "/data/commits-json/commits-10-p1.json",
        );
        $data2 = Block::fromJsonFile(
            __DIR__ . "/data/commits-json/commits-10-p2.json",
        );
        $data3 = Block::fromJsonFile(
            __DIR__ . "/data/commits-json/commits-10-p3.json",
        );
        return $data1->append($data2)->append($data3);
    }

    public function testGetString(): void
    {
        $data1 = $this->loadCommits();

        $this->assertCount(30, $data1);
        $this->assertCount(
            10,
            Block::fromJsonFile(
                __DIR__ . "/data/commits-json/commits-10-p2.json",
            ),
        );

        $this->assertIsString($data1->getString("0.commit.author.date"));
        $this->assertNull($data1->getString("0.commit.author.notexist"));
        $this->assertSame(
            "AA",
            $data1->getString("0.commit.author.notexist", "AA"),
        );
        $this->assertSame("0", $data1->getString("0.commit.comment_count"));
        $this->assertSame(
            "0",
            $data1->getString("0.commit.comment_count", "1"),
        );
        $this->assertSame(
            "1",
            $data1->getString("0.commit.comment_countnotexists", "1"),
        );
    }

    public function testGetStringStrict(): void
    {
        $data1 = $this->loadCommits();

        $this->assertIsString($data1->getStringStrict("0.commit.author.date"));
        $this->assertSame(
            "",
            $data1->getStringStrict("0.commit.author.notexist"),
        );
        $this->assertSame(
            "AA",
            $data1->getStringStrict("0.commit.author.notexist", "AA"),
        );
        $this->assertSame(
            "0",
            $data1->getStringStrict("0.commit.comment_count"),
        );
        $this->assertSame(
            "0",
            $data1->getStringStrict("0.commit.comment_count", "1"),
        );
        $this->assertSame(
            "1",
            $data1->getStringStrict("0.commit.comment_countnotexists", "1"),
        );
    }

    public function testGetInt(): void
    {
        $data1 = Block::fromJsonFile(
            __DIR__ . "/data/commits-json/commits-10-p1.json",
        );

        $this->assertSame(678434, $data1->getInt("0.author.id"));
        $this->assertNull($data1->getInt("0.author.idx"));
        $this->assertSame(44, $data1->getInt("0.author.idx", 44));
    }

    public function testGetIntStrict(): void
    {
        $data1 = Block::fromJsonFile(
            __DIR__ . "/data/commits-json/commits-10-p1.json",
        );

        $this->assertSame(678434, $data1->getIntStrict("0.author.id"));
        $this->assertSame(0, $data1->getIntStrict("0.author.idx"));
        $this->assertSame(44, $data1->getIntStrict("0.author.idx", 44));
        $this->assertSame(2024, $data1->getIntStrict("0.commit.author.date"));
    }

    public function testGetBoolean(): void
    {
        $data1 = $this->loadCommits();

        $this->assertIsBool($data1->getBoolean("0.author.site_admin"));
        $this->assertNull($data1->getBoolean("0.author.notexist"));
        $this->assertTrue($data1->getBoolean("0.author.notexist", true));
        $this->assertFalse($data1->getBoolean("0.author.notexist", false));
        $this->assertFalse($data1->getBoolean("0.author.site_admin"));
        $this->assertFalse($data1->getBoolean("0.author.site_admin", true));
        $this->assertTrue(
            $data1->getBoolean("0.commit.comment_countnotexists", true),
        );
    }

    public function testGetBooleanStrict(): void
    {
        $data1 = $this->loadCommits();

        $this->assertFalse($data1->getBooleanStrict("0.author.site_admin"));
        $this->assertFalse($data1->getBooleanStrict("0.author.notexist"));
        $this->assertTrue($data1->getBooleanStrict("0.author.notexist", true));
        $this->assertFalse(
            $data1->getBooleanStrict("0.author.site_admin", true),
        );
        $this->assertTrue(
            $data1->getBooleanStrict("0.commit.comment_countnotexists", true),
        );
    }

    public function testGetFloat(): void
    {
        $data = Block::make($this->fruitsArray);

        $this->assertInstanceOf(Block::class, $data);
        $this->assertCount(4, $data);
        $this->assertSame(8.5, $data->getFloat("banana.rating"));
        $this->assertSame(7.0, $data->getFloat("apple.rating"));
        $this->assertSame(1.2, $data->getFloat("foo", 1.2));
        $this->assertSame(0.0, $data->getFloatStrict("missing"));
    }
}
