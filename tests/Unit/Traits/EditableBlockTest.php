<?php

use HiFolks\DataType\Block;

test(
    'appends jsons',
    function (): void {
        $data1 = Block::fromJsonFile(__DIR__ . "/../../data/commits-json/commits-10-p1.json");
        $data2 = Block::fromJsonFile(__DIR__ . "/../../data/commits-json/commits-10-p2.json");
        $data3 = Block::fromJsonFile(__DIR__ . "/../../data/commits-json/commits-10-p3.json");

        expect($data1)->toHaveCount(10);
        expect($data2)->toHaveCount(10);
        $data1->append($data2);
        expect($data1)->toHaveCount(20);

        $arrayData3 = $data3->toArray();
        expect($arrayData3)->toBeArray();
        expect($arrayData3)->toHaveCount(10);
        expect($data1)->toHaveCount(20);
        $data1->append($arrayData3);
        expect($data1)->toHaveCount(30);




    },
);
test(
    'appends array',
    function (): void {
        $data1 = Block::make(["a", "b"]);
        $arrayData2 = ["c", "d"];
        expect($data1)->toHaveCount(2);
        $data1->append($arrayData2);
        expect($data1)->toHaveCount(4);
        expect($data1->toArray())->toMatchArray([
            'a',
            'b',
            'c',
            'd',
        ]);
    },
);

test(
    'appends item',
    function (): void {
        $data1 = Block::make(["a", "b"]);
        $arrayData2 = ["c", "d"];
        expect($data1)->toHaveCount(2);
        // because of the appendItem, here the whole array is
        // added as element
        $data1->appendItem($arrayData2);
        expect($data1)->toHaveCount(3);
        expect($data1->toArray())->toMatchArray([
            'a',
            'b',
            [
                'c',
                'd',
            ],
        ]);
    },
);
