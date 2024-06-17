<?php

use HiFolks\DataType\Block;

$dataTable = [
    ['product' => 'Desk', 'price' => 200, 'active' => true],
    ['product' => 'Chair', 'price' => 100, 'active' => true],
    ['product' => 'Door', 'price' => 300, 'active' => false],
    ['product' => 'Bookcase', 'price' => 150, 'active' => true],
    ['product' => 'Door', 'price' => 100, 'active' => true],
];

test(
    'Block as table',
    function () use ($dataTable): void {
        $table = Block::make($dataTable);

        $data = $table
            //->select('product', 'price')
            ->where('price', ">", 100);
        //->calc('new_field', fn ($item) => $item['price'] * 2)

        expect($data)->toHaveCount(3);
    },
);

test(
    'Block as table select and where',
    function () use ($dataTable): void {
        $table = Block::make($dataTable);

        $data = $table
            ->select('product', 'price')
            ->where('price', ">", 100, false);
        //->calc('new_field', fn ($item) => $item['price'] * 2)

        expect($data)->toHaveCount(3);
        expect($data->get("0"))->toHaveCount(2);
        expect($data->get("0"))->toHaveKeys(["product", "price"]);
        expect($data->get("0.product"))->toBe("Desk");
        expect($data->get("1.product"))->toBe("Door");

        $table = Block::make($dataTable);

        $data = $table
            ->select('product', 'price');
        expect($data)->toHaveCount(5);
        expect($data->get("0"))->toHaveCount(2);
        expect($data->get("0"))->toHaveKeys(["product", "price"]);
        expect($data->get("0.product"))->toBe("Desk");
        expect($data->get("0.price"))->toBe(200);
        expect($data->get("1"))->toHaveCount(2);
        expect($data->get("1.product"))->toBe("Chair");
        expect($data->get("1.price"))->toBe(100);
        expect($data->get("4"))->toHaveCount(2);
        expect($data->get("4.product"))->toBe("Door");
        expect($data->get("4.price"))->toBe(100);

    },
);
