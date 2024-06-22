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
    'Block make()',
    function () use ($fruitsArray): void {
        $data = Block::make($fruitsArray);
        expect($data)->toBeInstanceOf(Block::class);
        expect($data)->toHaveLength(4);
        $data = Block::make();
        expect($data)->toBeInstanceOf(Block::class);
        expect($data)->toHaveLength(0);
        $data = Block::make([]);
        expect($data)->toBeInstanceOf(Block::class);
        expect($data)->toHaveLength(0);

    },
);

test(
    'Block get()',
    function () use ($fruitsArray): void {
        $data = Block::make($fruitsArray);
        expect($data->get("avocado"))->toBeArray();
        expect($data->get("avocado"))->toHaveCount(5);
        expect($data->get("avocado.color"))->toBeString();
        expect($data->get("avocado.color"))->toBe("green");
        expect($data->get("avocado.name"))->toBeString();
        expect($data->get("avocado.name"))->toBe("Avocado");
        expect($data->get("avocado.rating"))->toBeNumeric();
        expect($data->get("avocado.rating"))->toBeInt();
        expect($data->get("banana.rating"))->toBeFloat();
        expect($data->get("avocado.notexists"))->toBeNull();
        expect($data->get("avocado.notexists", "NO VALUE"))->toBe("NO VALUE");

    },
);

test(
    'Block getBlock()',
    function () use ($fruitsArray): void {
        $data = Block::make($fruitsArray);
        expect($data->getBlock("avocado"))->toBeInstanceOf(Block::class);
        expect($data->getBlock("avocado"))->toHaveCount(5);
        expect($data->getBlock("avocado.color"))->toBeInstanceOf(Block::class);
        expect($data->getBlock("avocado.color"))->toHaveCount(1);

        expect($data->getBlock("avocado.notexists"))->toBeInstanceOf(Block::class);
        expect($data->getBlock("avocado.notexists"))->toHaveCount(0);
    },
);

test(
    'Block keys()',
    function () use ($fruitsArray): void {
        $data = Block::make($fruitsArray);
        expect($data->getBlock("avocado")->keys())->toHaveCount(5);
        expect($data->getBlock("avocado")->keys())->toBeArray();
        expect($data->getBlock("avocado")->keys())->toMatchArray([0 => "name"]);
        expect($data->keys())->toHaveCount(4);
        expect($data->keys())->toBeArray();
        expect($data->keys())->toMatchArray([0 => "avocado"]);
        expect($data->keys())->toMatchArray([1 => "apple"]);



    },
);


it('Basic get Block', function (): void {
    $block = Block::make(['A','B','C']);
    expect($block->get(1))->toBe('B');
    expect($block->get(4))->toBeNull();
    expect($block->get(4, 'AAAA'))->toBe("AAAA");
});



it('Basic nested get', function (): void {
    $block = Block::make([
        'A' => 'First',
        'B' => ['some', 'thing'],
        'C' => [ 'nested-item-1' => 10, 'nested-item-2' => 20],
        'D' => [],
    ]);
    expect($block->get('A'))->toBe('First');
    expect($block->get('B'))->toBeArray();
    expect($block->get('B.0'))->toBe('some');
    expect($block->get('B.1'))->toBe('thing');
    expect($block->get('B.2'))->toBeNull();
    expect($block->get('B.2', 1234))->toBe(1234);
    expect($block->get('B#0', charNestedKey: '#'))->toBe('some');
    expect($block->get('B#1', charNestedKey: '#'))->toBe('thing');
    expect($block->get('B#2', charNestedKey: '#'))->toBeNull();
    expect($block->get('B#2', 1234, '#'))->toBe(1234);

    expect($block->get('C.0'))->toBeNull();
    expect($block->get('C.nested-item-1'))->toBe(10);
    expect($block->get('C.nested-item-2'))->toBe(20);
    expect($block->get('C.nested-item-2.other'))->toBeNull();
    expect($block->get('C.nested-item-2.other', 'zzz'))->toBe('zzz');
    expect($block->get('C#nested-item-2#other', 'zzz', '#'))->toBe('zzz');
    expect($block->get('C#nested-item-2#other', 'zzz'))->toBe('zzz');
    expect($block->get('C#nested-item-2', 'zzz'))->toBe('zzz');
    expect($block->get('C#nested-item-4', 'zzz'))->toBe('zzz');
    expect($block->get('D#nested-item-4', 'zzz'))->toBe('zzz');
    expect($block->get('D.0'))->toBeNull();
    expect($block->get('D', '0'))->toBeArray()->toHaveLength(0);

});

it('Basic getBlock', function (): void {
    $block = Block::make(['A','B','C']);
    expect($block->getBlock(1))->toBeInstanceOf(Block::class);
    expect($block->getBlock(1)->get(0))->toBe('B');
    expect($block->getBlockNullable(4))->toBeNull();
    expect($block->getBlock(4, 'AAAA'))->toBeInstanceOf(Block::class);
    expect($block->getBlock(4, 'AAAA')->get(0))->toBe('AAAA');


    $block = Block::make([
        'A' => 'First',
        'B' => ['some', 'thing'],
        'C' => [ 'nested-item-1' => 10, 'nested-item-2' => 20],
        'D' => [],
    ]);

    expect($block->getBlock('C')->get('nested-item-1'))->toBe(10);
    expect($block->getBlock('C')->entries()->get(0))->toBeArray();
    expect($block->getBlock('C')->entries()->get(0))->toBe(['nested-item-1',10]);
    expect($block->getBlock('C')->keys())->toBe(['nested-item-1','nested-item-2' ]);

    $block = Block::make(
        [
            "avocado" =>
                [
                    'name' => 'Avocado',
                    'fruit' => '🥑',
                    'wikipedia' => 'https://en.wikipedia.org/wiki/Avocado',
                ],
            "apple" =>
                [
                    'name' => 'Apple',
                    'fruit' => '🍎',
                    'wikipedia' => 'https://en.wikipedia.org/wiki/Apple',
                ],
            "banana" =>
                [
                    'name' => 'Banana',
                    'fruit' => '🍌',
                    'wikipedia' => 'https://en.wikipedia.org/wiki/Banana',
                ],
            "cherry" =>
                [
                    'name' => 'Cherry',
                    'fruit' => '🍒',
                    'wikipedia' => 'https://en.wikipedia.org/wiki/Cherry',
                ],
        ],
    );
    $appleArr = $block->getBlock("apple");
    expect($appleArr->count())->toBe(3)
        ->and($appleArr->get("name"))->toBe('Apple');
    $appleNameArr = $block->getBlock("apple.name");
    expect($appleNameArr->get(0))->toBe('Apple');
    $appleNoExists = $block->getBlock("apple.name.noexists");
    expect($appleNoExists)->toBeInstanceOf(Block::class);
    expect($appleNoExists->count())->toBe(0);
    $appleNoExists = $block->getBlockNullable("apple.name.noexists");
    expect($appleNoExists)->toBeNull();
    $appleNoExists = $block->getBlock("apple.name.noexists", "some");
    expect($appleNoExists)->toBeInstanceOf(Block::class)
        ->and($appleNoExists->get(0))->toBe("some");
});


it('generates JSON', function (): void {
    $block = Block::make([
        'A' => 'First',
        'B' => ['some', 'thing'],
        'C' => [ 'nested-item-1' => 10, 'nested-item-2' => 20],
        'D' => [],
    ]);
    expect($block->toJson())->toBeString();
    expect(json_decode($block->toJson(), associative: false))->toBeInstanceOf(stdClass::class);

    expect(json_decode($block->toJson(), associative: false))->toHaveProperty("C");
    expect(json_decode($block->toJson(), associative: false)->C)->toHaveProperty("nested-item-1");
});

it('generates JSON object', function (): void {
    $block = Block::make([
        'A' => 'First',
        'B' => ['some', 'thing'],
        'C' => ['nested-item-1' => 10, 'nested-item-2' => 20],
        'D' => [],
    ]);
    expect($block->toJsonObject())->toBeInstanceOf(stdClass::class);
    expect($block->toJsonObject())->toHaveProperty("C");
    expect($block->toJsonObject()->C)->toHaveProperty("nested-item-1");
});
