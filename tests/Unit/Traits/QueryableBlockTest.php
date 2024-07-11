<?php

use HiFolks\DataType\Block;

$fruitsArray = [
    "avocado" =>
    [
        'name' => 'Avocado',
        'fruit' => '🥑',
        'wikipedia' => 'https://en.wikipedia.org/wiki/Avocado',
        'color' => 'green',
        'rating' => 8,
    ],
    "apple" =>
    [
        'name' => 'Apple',
        'fruit' => '🍎',
        'wikipedia' => 'https://en.wikipedia.org/wiki/Apple',
        'color' => 'red',
        'rating' => 7,
    ],
    "banana" =>
    [
        'name' => 'Banana',
        'fruit' => '🍌',
        'wikipedia' => 'https://en.wikipedia.org/wiki/Banana',
        'color' => 'yellow',
        'rating' => 8.5,
    ],
    "cherry" =>
    [
        'name' => 'Cherry',
        'fruit' => '🍒',
        'wikipedia' => 'https://en.wikipedia.org/wiki/Cherry',
        'color' => 'red',
        'rating' => 9,
    ],
];

test(
    'Test query greater than x',
    function () use ($fruitsArray): void {
        $data = Block::make($fruitsArray);
        $highRated = $data->where("rating", ">", 8);
        expect($highRated)->tohaveCount(2);
        $sorted = $data->where("rating", ">", 8)->orderBy("rating", "desc");
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
