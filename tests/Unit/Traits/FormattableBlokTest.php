<?php

use HiFolks\DataType\Block;

test(
    'Format field to data',
    function (): void {
        $data1 = Block::fromJsonFile(__DIR__ . "/../../data/commits-json/commits-10-p1.json");
        $data2 = Block::fromJsonFile(__DIR__ . "/../../data/commits-json/commits-10-p2.json");
        $data3 = Block::fromJsonFile(__DIR__ . "/../../data/commits-json/commits-10-p3.json");

        expect($data1)->toHaveCount(10);
        expect($data2)->toHaveCount(10);
        expect($data1->getFormattedDateTime("0.commit.author.date", "Y"))->toBe("2024");
        expect($data1->getFormattedDateTime("0.commit.author.date", "Y-m-d"))->toBe("2024-06-28");
        expect($data1->getFormattedDateTime("0.commit.author.dateNOTEXISTS", "Y-m-d"))->toBeNull();

    },
);

test(
    'Format byte field into KB or MB ot GB ',
    function (): void {
        $stringData = <<<DATA
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
DATA;
        $data1 = Block::fromJsonString($stringData);
        expect($data1->getBlock("assets"))->toHaveCount(2);
        expect($data1->getFormattedByte("assets.0.total_bytes"))->toBe("5.98 GB");
        expect($data1->getFormattedByte("assets.1.total_bytes"))->toBe("2.18 GB");
        expect($data1->getFormattedByte("assets.1.total_bytes", 5))->toBe("2.18288 GB");
        expect($data1->getFormattedByte("assets.1.total_bytes", 0))->toBe("2 GB");
    },
);

test(
    'Force field to string',
    function (): void {
        $data1 = Block::fromJsonFile(__DIR__ . "/../../data/commits-json/commits-10-p1.json");
        $data2 = Block::fromJsonFile(__DIR__ . "/../../data/commits-json/commits-10-p2.json");
        $data3 = Block::fromJsonFile(__DIR__ . "/../../data/commits-json/commits-10-p3.json");
        $data1->append($data2)->append($data3);
        expect($data1)->toHaveCount(30);
        expect($data2)->toHaveCount(10);
        expect($data1->getString("0.commit.author.date"))->toBeString();
        expect($data1->getString("0.commit.author.notexist"))->toBeString();
        expect($data1->getString("0.commit.author.notexist"))->toEqual("");
        expect($data1->getString("0.commit.author.notexist", "AA"))->toEqual("AA");
        expect($data1->getString("0.commit.comment_count"))->toBeString()->toEqual("0");
        expect($data1->getString("0.commit.comment_count", 1))->toBeString()->toEqual("0");
        expect($data1->getString("0.commit.comment_countnotexists", 1))->toBeString()->toEqual("1");
    },
);

test(
    'Force field to boolean',
    function (): void {
        $data1 = Block::fromJsonFile(__DIR__ . "/../../data/commits-json/commits-10-p1.json");
        $data2 = Block::fromJsonFile(__DIR__ . "/../../data/commits-json/commits-10-p2.json");
        $data3 = Block::fromJsonFile(__DIR__ . "/../../data/commits-json/commits-10-p3.json");
        $data1->append($data2)->append($data3);
        expect($data1)->toHaveCount(30);
        expect($data2)->toHaveCount(10);
        expect($data1->getBoolean("0.author.site_admin"))->toBeBool();
        expect($data1->getBoolean("0.author.notexist"))->toBeBool();
        expect($data1->getBoolean("0.author.notexist"))->toBeBool()->toEqual(false);
        expect($data1->getBoolean("0.author.notexist", "true"))->toEqual(true);
        expect($data1->getBoolean("0.author.site_admin"))->toBeBool()->toEqual(false);
        expect($data1->getBoolean("0.author.site_admin", true))->toBeBool()->toEqual(false);
        expect($data1->getBoolean("0.commit.comment_countnotexists", true))->toBeBool()->toEqual(true);
    },
);
