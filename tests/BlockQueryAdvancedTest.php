<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use HiFolks\DataType\Enums\Operator;
use PHPUnit\Framework\TestCase;

final class BlockQueryAdvancedTest extends TestCase
{
    private array $fruitsArray = [
        "avocado" => [
            "name" => "Avocado",
            "fruit" => "🥑",
            "wikipedia" => "https://en.wikipedia.org/wiki/Avocado",
            "color" => "green",
            "rating" => 8,
            "tags" => ["healthy", "creamy", "green"],
        ],
        "apple" => [
            "name" => "Apple",
            "fruit" => "🍎",
            "wikipedia" => "https://en.wikipedia.org/wiki/Apple",
            "color" => "red",
            "rating" => 7,
            "tags" => ["classic", "crunchy", "juicy", "red", "sweet"],
        ],
        "banana" => [
            "name" => "Banana",
            "fruit" => "🍌",
            "wikipedia" => "https://en.wikipedia.org/wiki/Banana",
            "color" => "yellow",
            "rating" => 8.5,
            "tags" => ["sweet", "soft", "yellow"],
        ],
        "cherry" => [
            "name" => "Cherry",
            "fruit" => "🍒",
            "wikipedia" => "https://en.wikipedia.org/wiki/Cherry",
            "color" => "red",
            "rating" => 9,
            "tags" => ["small", "tart", "red"],
        ],
    ];

    public function testQueryGreaterThan(): void
    {
        $data = Block::make($this->fruitsArray);
        $highRated = $data->where("rating", Operator::GREATER_THAN, 8);
        $this->assertCount(2, $highRated);

        $sorted = $data
            ->where("rating", Operator::GREATER_THAN, 8)
            ->orderBy("rating", "desc");
        $this->assertCount(2, $sorted);
    }

    public function testGroupByColor(): void
    {
        $table = Block::make($this->fruitsArray);
        $grouped = $table->groupBy("color");

        $this->assertCount(2, $grouped->getBlock("red"));
        $this->assertCount(1, $grouped->getBlock("yellow"));
        $this->assertCount(0, $grouped->getBlock("NotExists"));
    }

    public function testGroupByArray(): void
    {
        $data = Block::make([
            ["type" => "fruit", "name" => "apple"],
            ["type" => "fruit", "name" => "banana"],
            ["type" => "vegetable", "name" => "carrot"],
        ]);
        $grouped = $data->groupBy("type");

        $this->assertCount(2, $grouped->getBlock("fruit"));
        $this->assertCount(1, $grouped->getBlock("vegetable"));
        $this->assertCount(0, $grouped->getBlock("NotExists"));
    }

    public function testGroupByFunction(): void
    {
        $fruits = [
            ["name" => "Apple", "type" => "Citrus", "quantity" => 15],
            ["name" => "Banana", "type" => "Tropical", "quantity" => 10],
            ["name" => "Orange", "type" => "Citrus", "quantity" => 8],
            ["name" => "Mango", "type" => "Tropical", "quantity" => 5],
            ["name" => "Lemon", "type" => "Citrus", "quantity" => 12],
        ];

        $fruitsBlock = Block::make($fruits);

        $groupedByQuantityRange = $fruitsBlock->groupByFunction(
            fn($fruit): string => match (true) {
                $fruit["quantity"] < 10 => "Low",
                $fruit["quantity"] < 15 => "Medium",
                default => "High",
            },
        );

        $this->assertCount(3, $groupedByQuantityRange);
        $this->assertCount(2, $groupedByQuantityRange->getBlock("Low"));
        $this->assertCount(2, $groupedByQuantityRange->getBlock("Medium"));
        $this->assertCount(1, $groupedByQuantityRange->getBlock("High"));

        $groupedByNameLength = $fruitsBlock->groupByFunction(
            fn($fruit): int => strlen((string) $fruit["name"]),
        );

        $this->assertCount(2, $groupedByNameLength);
        $this->assertTrue($groupedByNameLength->hasKey(5));
        $this->assertTrue($groupedByNameLength->hasKey("6"));
        $this->assertCount(3, $groupedByNameLength->get("5"));
        $this->assertCount(2, $groupedByNameLength->get("6"));
    }

    public function testWhereInOperator(): void
    {
        $data = Block::make($this->fruitsArray);

        $greenOrBlack = $data->where("color", Operator::IN, ["green", "black"]);
        $this->assertCount(1, $greenOrBlack);

        $noResult = $data->where("color", Operator::IN, []);
        $this->assertCount(0, $noResult);

        $greenOrRed = $data->where("color", Operator::IN, ["green", "red"]);
        $this->assertCount(3, $greenOrRed);
    }

    public function testWhereHasOperator(): void
    {
        $data = Block::make($this->fruitsArray);

        $sweet = $data->where("tags", Operator::HAS, "sweet");
        $this->assertCount(2, $sweet);

        $noResult = $data->where("tags", Operator::HAS, "not-existent");
        $this->assertCount(0, $noResult);

        $softFruit = $data->where("tags", Operator::HAS, "soft");
        $this->assertCount(1, $softFruit);
    }

    public function testQueryWithOperators(): void
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

        $data1->append($data2)->append($data3);

        $this->assertCount(30, $data1);
        $this->assertCount(10, $data2);

        $block = $data1->where("commit.author.name", Operator::LIKE, "Roberto");
        $this->assertEquals(29, $block->count());
    }
}
