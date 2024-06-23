<?php

use HiFolks\DataType\Block;

$fruitsArray = [
    [
        'name' => 'Avocado',
        'fruit' => '🥑',
        'wikipedia' => 'https://en.wikipedia.org/wiki/Avocado',
        'color' => 'green',
        'rating' => 8,
    ],
    [
        'name' => 'Apple',
        'fruit' => '🍎',
        'wikipedia' => 'https://en.wikipedia.org/wiki/Apple',
        'color' => 'red',
        'rating' => 7,
    ],
    [
        'name' => 'Banana',
        'fruit' => '🍌',
        'wikipedia' => 'https://en.wikipedia.org/wiki/Banana',
        'color' => 'yellow',
        'rating' => 8.5,
    ],
    [
        'name' => 'Cherry',
        'fruit' => '🍒',
        'wikipedia' => 'https://en.wikipedia.org/wiki/Cherry',
        'color' => 'red',
        'rating' => 9,
    ],
];

$schemaJson = <<<'JSON'
{
  "type": "array",
    "items" : {
        "type": "object",
        "properties": {
            "name": {
                "type": "string"
            },
            "fruit": {
                "type": "string"
            },
            "wikipedia": {
                "type": "string"
            },
            "color": {
                "type": "string"
            },
            "rating": {
                "type": "number"
            }
        }
    }
}
JSON;

test(
    'Validate json',
    function () use ($fruitsArray, $schemaJson): void {
        $data = Block::make($fruitsArray);
        expect($data->validateJsonWithSchema($schemaJson))->toBeTrue();
    },
);
