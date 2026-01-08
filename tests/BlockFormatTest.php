<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\TestCase;

final class BlockFormatTest extends TestCase
{
    public function testFormatFieldToData(): void
    {
        $data1 = Block::fromJsonFile(
            __DIR__ . "/data/commits-json/commits-10-p1.json",
        );
        $data2 = Block::fromJsonFile(
            __DIR__ . "/data/commits-json/commits-10-p2.json",
        );
        Block::fromJsonFile(
            __DIR__ . "/data/commits-json/commits-10-p3.json",
        );

        $this->assertCount(10, $data1);
        $this->assertCount(10, $data2);

        $this->assertSame(
            "2024",
            $data1->getFormattedDateTime("0.commit.author.date", "Y"),
        );
        $this->assertSame(
            "2024-06-28",
            $data1->getFormattedDateTime("0.commit.author.date", "Y-m-d"),
        );

        // Non-existing field should return null
        $this->assertNull(
            $data1->getFormattedDateTime(
                "0.commit.author.dateNOTEXISTS",
                "Y-m-d",
            ),
        );
    }

    public function testFormatByteField(): void
    {
        $stringData = <<<JSON
        {
            "assets": [
                {
                    "id": 17571534,
                    "alt": "",
                    "asset_folder_id": null,
                    "content_length": 146327844,
                    "content_type": "image\/gif",
                    "deleted_at": "2024-10-07T11:03:20.415Z",
                    "filename": "demo-4.gif",
                    "is_private": false,
                    "total_bytes": 6423742856
                },
                {
                    "id": 17580896,
                    "alt": "",
                    "asset_folder_id": null,
                    "content_length": 146327844,
                    "content_type": "image\/gif",
                    "deleted_at": null,
                    "filename": "demo-5.gif",
                    "is_private": false,
                    "total_bytes": 2343850964
                }
            ]
        }
        JSON;

        $data1 = Block::fromJsonString($stringData);

        $this->assertCount(2, $data1->getBlock("assets"));

        $this->assertSame(
            "5.98 GB",
            $data1->getFormattedByte("assets.0.total_bytes"),
        );
        $this->assertSame(
            "2.18 GB",
            $data1->getFormattedByte("assets.1.total_bytes"),
        );
        $this->assertSame(
            "2.18288 GB",
            $data1->getFormattedByte("assets.1.total_bytes", 5),
        );
        $this->assertSame(
            "2 GB",
            $data1->getFormattedByte("assets.1.total_bytes", 0),
        );
    }
}
