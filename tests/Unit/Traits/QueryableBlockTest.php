<?php

use HiFolks\DataType\Block;
use HiFolks\DataType\Enums\Operator;

$fruitsArray = [
    "avocado" =>
    [
        'name' => 'Avocado',
        'fruit' => '🥑',
        'wikipedia' => 'https://en.wikipedia.org/wiki/Avocado',
        'color' => 'green',
        'rating' => 8,
        'tags' => ['healthy', 'creamy', 'green'],
    ],
    "apple" =>
    [
        'name' => 'Apple',
        'fruit' => '🍎',
        'wikipedia' => 'https://en.wikipedia.org/wiki/Apple',
        'color' => 'red',
        'rating' => 7,
        'tags' => ['classic', 'crunchy', 'juicy', 'red', 'sweet'],
    ],
    "banana" =>
    [
        'name' => 'Banana',
        'fruit' => '🍌',
        'wikipedia' => 'https://en.wikipedia.org/wiki/Banana',
        'color' => 'yellow',
        'rating' => 8.5,
        'tags' => ['sweet', 'soft', 'yellow'],
    ],
    "cherry" =>
    [
        'name' => 'Cherry',
        'fruit' => '🍒',
        'wikipedia' => 'https://en.wikipedia.org/wiki/Cherry',
        'color' => 'red',
        'rating' => 9,
        'tags' => ['small', 'tart', 'red'],
    ],
];

test(
    'Test query greater than x',
    function () use ($fruitsArray): void {
        $data = Block::make($fruitsArray);
        $highRated = $data->where("rating", Operator::GREATER_THAN, 8);
        expect($highRated)->tohaveCount(2);
        $sorted = $data->where("rating", Operator::GREATER_THAN, 8)->orderBy("rating", "desc");
        expect($sorted)->tohaveCount(2);


    },
);

test(
    'group by',
    function () use ($fruitsArray): void {
        $table = Block::make($fruitsArray);
        $grouped = $table->groupBy("color");
        expect($grouped->getBlock("red"))->tohaveCount(2);
        expect($grouped->getBlock("yellow"))->tohaveCount(1);
        expect($grouped->getBlock("NotExists"))->tohaveCount(0);

    },
);

test(
    'group by 2',
    function (): void {
        $data = Block::make([
            ['type' => 'fruit', 'name' => 'apple'],
            ['type' => 'fruit', 'name' => 'banana'],
            ['type' => 'vegetable', 'name' => 'carrot'],
        ]);
        $grouped = $data->groupBy('type');
        expect($grouped->getBlock("fruit"))->tohaveCount(2);
        expect($grouped->getBlock("vegetable"))->tohaveCount(1);
        expect($grouped->getBlock("NotExists"))->tohaveCount(0);

    },
);


test(
    'where method, in operator',
    function () use ($fruitsArray): void {
        $data = Block::make($fruitsArray);
        $greenOrBlack = $data->where("color", Operator::IN, ["green", "black"]);
        expect($greenOrBlack)->tohaveCount(1);
        $noResult = $data->where("color", Operator::IN, []);
        expect($noResult)->tohaveCount(0);
        $greenOrRed = $data->where("color", Operator::IN, ["green", "red"]);
        expect($greenOrRed)->tohaveCount(3);
    },
);

test(
    'where method, has operator',
    function () use ($fruitsArray): void {
        $data = Block::make($fruitsArray);
        $sweet = $data->where("tags", Operator::HAS, "sweet");
        expect($sweet)->tohaveCount(2);
        $noResult = $data->where("tags", Operator::HAS, "not-existent");
        expect($noResult)->tohaveCount(0);
        $softFruit = $data->where("tags", Operator::HAS, "soft");
        expect($softFruit)->tohaveCount(1);
    },
);
