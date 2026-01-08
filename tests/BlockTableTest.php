<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use HiFolks\DataType\Enums\Operator;
use PHPUnit\Framework\TestCase;

final class BlockTableTest extends TestCase
{
    private array $dataTable = [
        ["product" => "Desk", "price" => 200, "active" => true],
        ["product" => "Chair", "price" => 100, "active" => true],
        ["product" => "Door", "price" => 300, "active" => false],
        ["product" => "Bookcase", "price" => 150, "active" => true],
        ["product" => "Door", "price" => 100, "active" => true],
    ];

    public function testBlockAsTable(): void
    {
        $table = Block::make($this->dataTable);

        $data = $table->where("price", Operator::GREATER_THAN, 100);

        $this->assertCount(3, $data);
    }

    public function testBlockAsTableSelectAndWhere(): void
    {
        $table = Block::make($this->dataTable);

        $data = $table
            ->select("product", "price")
            ->where("price", Operator::GREATER_THAN, 100, false);

        $this->assertCount(3, $data);

        $this->assertCount(2, $data->get("0"));
        $this->assertArrayHasKey("product", $data->get("0"));
        $this->assertArrayHasKey("price", $data->get("0"));
        $this->assertSame("Desk", $data->get("0.product"));
        $this->assertSame("Door", $data->get("1.product"));

        $table = Block::make($this->dataTable);

        $data = $table->select("product", "price");

        $this->assertCount(5, $data);

        $this->assertCount(2, $data->get("0"));
        $this->assertSame("Desk", $data->get("0.product"));
        $this->assertSame(200, $data->get("0.price"));

        $this->assertCount(2, $data->get("1"));
        $this->assertSame("Chair", $data->get("1.product"));
        $this->assertSame(100, $data->get("1.price"));

        $this->assertCount(2, $data->get("4"));
        $this->assertSame("Door", $data->get("4.product"));
        $this->assertSame(100, $data->get("4.price"));
    }

    public function testReturnBlockWhileLooping(): void
    {
        $table = Block::make($this->dataTable);

        $data = $table
            ->select("product", "price")
            ->where("price", Operator::GREATER_THAN, 100, false);

        foreach ($data as $key => $item) {
            $this->assertInstanceOf(Block::class, $item);
            $this->assertIsInt($key);
            $this->assertGreaterThan(100, $item->get("price"));
        }

        $this->assertSame(200, $data->get("0.price"));
        $this->assertSame(300, $data->get("1.price"));

        // original table unchanged
        $this->assertSame(200, $table->get("0.price"));
        $this->assertSame(100, $table->get("1.price"));

        // array mode
        $table = Block::make($this->dataTable, false);

        $data = $table
            ->select("product", "price")
            ->where("price", Operator::GREATER_THAN, 100, false);

        foreach ($data as $key => $item) {
            $this->assertIsArray($item);
            $this->assertIsInt($key);
            $this->assertGreaterThan(100, $item["price"]);
            $this->assertGreaterThan(100, $data[$key]["price"]);
        }

        // reusing $data
        foreach ($data as $key => $item) {
            $this->assertIsArray($item);
            $this->assertIsInt($key);
            $this->assertGreaterThan(100, $item["price"]);
        }

        // block iteration mode
        $table = Block::make($this->dataTable, true);

        foreach ($table as $key => $item) {
            $this->assertInstanceOf(Block::class, $item);
            $this->assertIsInt($key);
            $this->assertGreaterThan(10, $item->get("price"));
        }

        foreach ($table->iterateBlock(false) as $key => $item) {
            $this->assertIsArray($item);
            $this->assertIsInt($key);
            $this->assertGreaterThan(10, $item["price"]);
        }

        // previous state preserved
        foreach ($table as $key => $item) {
            $this->assertIsArray($item);
            $this->assertIsInt($key);
            $this->assertGreaterThan(10, $item["price"]);
        }

        foreach ($table->iterateBlock(true) as $key => $item) {
            $this->assertInstanceOf(Block::class, $item);
            $this->assertIsInt($key);
            $this->assertGreaterThan(10, $item->get("price"));
        }

        foreach ($table->iterateBlock(false) as $key => $item) {
            $this->assertIsArray($item);
            $this->assertIsInt($key);
            $this->assertGreaterThan(10, $item["price"]);
        }
    }

    public function testGroupBy(): void
    {
        $table = Block::make($this->dataTable);

        $grouped = $table->groupBy("product");

        $this->assertCount(2, $grouped->getBlock("Door"));
        $this->assertCount(1, $grouped->getBlock("Desk"));
        $this->assertCount(0, $grouped->getBlock("NotExists"));
    }

    public function testExtractJsonByAttribute(): void
    {
        $file = __DIR__ . "/data/stories.json";

        $block = Block::fromJsonFile($file);

        $rel = $block
            ->getBlock("rels")
            ->where(
                "uuid",
                Operator::EQUAL,
                "a6af7728-eadf-4428-8cf5-343304857374",
            );

        $this->assertCount(1, $rel);
        $this->assertSame("Category C", $rel->get("4.name"));
        $this->assertCount(22, $rel->get("4"));

        $rel = $block
            ->getBlock("rels")
            ->where(
                field: "uuid",
                operator: Operator::EQUAL,
                value: "a6af7728-eadf-4428-8cf5-343304857374",
                preseveKeys: false,
            );

        $this->assertCount(1, $rel);
        $this->assertSame("Category C", $rel->get("0.name"));
        $this->assertCount(22, $rel->get("0"));

        $rel = $block
            ->getBlock("rels")
            ->select("uuid", "name")
            ->where(
                field: "uuid",
                operator: Operator::EQUAL,
                value: "a6af7728-eadf-4428-8cf5-343304857374",
                preseveKeys: false,
            );

        $this->assertCount(1, $rel);
        $this->assertSame("Category C", $rel->get("0.name"));
        $this->assertCount(2, $rel->get("0"));
    }

    public function testApplyField(): void
    {
        $table = Block::make([
            "title" => "Title",
            "number" => 11,
        ]);

        $table->applyField(
            "number",
            "newfield",
            fn($value): int|float => $value * 2,
        );

        $this->assertCount(3, $table);
        $this->assertSame(22, $table->get("newfield"));
        $this->assertSame(11, $table->get("number"));
    }

    public function testApplyField2(): void
    {
        $object = Block::make();

        $object
            ->set("name", "John Doe")
            ->applyField(
                "name",
                "uppercase_name",
                fn($value): string => strtoupper((string) $value),
            );

        $this->assertCount(2, $object);
        $this->assertSame("JOHN DOE", $object->get("uppercase_name"));
        $this->assertSame("John Doe", $object->get("name"));
    }
}
