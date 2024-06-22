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
    'Test toJson',
    function () use ($fruitsArray): void {
        $data = Block::make($fruitsArray);
        expect($data->toJson())->toBeString();
        expect(strlen($data->toJson()))->toBe(773);
        $string = $data->toJson();
        $data1 = Block::fromJsonString($string);
        expect($data1->get("0.fruit"))->toBe($data->get("0.fruit"));
    },
);
test(
    'Test toJson with different iterateBlock',
    function () use ($fruitsArray): void {
        $data1 = Block::make($fruitsArray, true);
        $string1 = $data1->toJson();
        $data2 = Block::make($fruitsArray, false);
        $string2 = $data2->toJson();
        expect($string1)->toBeString();
        expect($string2)->toBeString();
        expect(strlen($string1))->toBe(773);
        expect(strlen($string2))->toBe(773);
        expect($string1)->toBe($string2);
    },
);
