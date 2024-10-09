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
