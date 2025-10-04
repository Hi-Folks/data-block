<?php

use HiFolks\DataType\Block;

test(
    'Testing getInt()',
    function (): void {
        $data1 = Block::fromJsonFile(__DIR__ . "/../../data/commits-json/commits-10-p1.json");
        // $data2 = Block::fromJsonFile(__DIR__ . "/../../data/commits-json/commits-10-p2.json");
        // $data3 = Block::fromJsonFile(__DIR__ . "/../../data/commits-json/commits-10-p3.json");

        expect($data1->getInt("0.author.id"))->toBeInt()->toBe(678434);
        expect($data1->getInt("0.author.idx"))->toBeNull();
        expect($data1->getInt("0.author.idx", 44))->toBeInt()->toBe(44);

    },
);
