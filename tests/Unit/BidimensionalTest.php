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
