<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\TestCase;

final class BlockAppendTest extends TestCase
{
    public function testAppendsJsons(): void
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

        $this->assertCount(10, $data1);
        $this->assertCount(10, $data2);

        $data1->append($data2);
        $this->assertCount(20, $data1);

        $arrayData3 = $data3->toArray();
        $this->assertIsArray($arrayData3);
        $this->assertCount(10, $arrayData3);

        $this->assertCount(20, $data1);

        $data1->append($arrayData3);
        $this->assertCount(30, $data1);
    }

    public function testAppendsArray(): void
    {
        $data1 = Block::make(["a", "b"]);
        $arrayData2 = ["c", "d"];

        $this->assertCount(2, $data1);

        $data1->append($arrayData2);

        $this->assertCount(4, $data1);

        $this->assertSame(
            ["a", "b", "c", "d"],
            array_values($data1->toArray()),
        );
    }

    public function testAppendsItem(): void
    {
        $data1 = Block::make(["a", "b"]);
        $arrayData2 = ["c", "d"];

        $this->assertCount(2, $data1);

        // appendItem adds the whole array as a single element
        $data1->appendItem($arrayData2);

        $this->assertCount(3, $data1);

        $this->assertSame(
            ["a", "b", ["c", "d"]],
            array_values($data1->toArray()),
        );
    }
}
