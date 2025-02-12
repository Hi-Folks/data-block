<?php

use HiFolks\DataType\Block;
use HiFolks\DataType\Enums\Operator;

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
            ->where('price', Operator::GREATER_THAN, 100);

        expect($data)->toHaveCount(3);
    },
);

test(
    'Block as table select and where',
    function () use ($dataTable): void {
        $table = Block::make($dataTable);

        $data = $table
            ->select('product', 'price')
            ->where('price', Operator::GREATER_THAN, 100, false);

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

test(
    'Return Block while looping',
    function () use ($dataTable): void {
        $table = Block::make($dataTable);

        $data = $table
            ->select('product', 'price')
            ->where('price', Operator::GREATER_THAN, 100, false);

        //->calc('new_field', fn ($item) => $item['price'] * 2)
        foreach ($data as $key => $item) {
            expect($item)->toBeInstanceOf(Block::class);
            expect($key)->toBeInt();
            expect($item->get("price"))->toBeGreaterThan(100);
        }
        expect($data->get("0.price"))->toBe(200);
        expect($data->get("1.price"))->toBe(300);


        expect($table->get("0.price"))->toBe(200);
        expect($table->get("1.price"))->toBe(100);

        $table = Block::make($dataTable, false);
        $data = $table
            ->select('product', 'price')
            ->where('price', Operator::GREATER_THAN, 100, false);

        foreach ($data as $key => $item) {
            expect($item)->toBeArray();
            expect($key)->toBeInt();
            expect($item["price"])->toBeGreaterThan(100);
            expect($data[$key]["price"])->toBeGreaterThan(100);
        }


        // re-using the $data object with the previous state
        foreach ($data as $key => $item) {
            expect($item)->toBeArray();
            expect($key)->toBeInt();
            expect($item["price"])->toBeGreaterThan(100);
            expect($data[$key]["price"])->toBeGreaterThan(100);
        }

        $table = Block::make($dataTable, true);
        foreach ($table as $key => $item) {
            expect($item)->toBeInstanceOf(Block::class);
            expect($key)->toBeInt();
            expect($item->get("price"))->toBeGreaterThan(10);
        }

        foreach ($table->iterateBlock(false) as $key => $item) {
            expect($item)->toBeArray();
            expect($key)->toBeInt();
            expect($item["price"])->toBeGreaterThan(10);
        }
        // keep the previous state iterateBlock(false)
        foreach ($table as $key => $item) {
            expect($item)->toBeArray();
            expect($key)->toBeInt();
            expect($item["price"])->toBeGreaterThan(10);
        }


        foreach ($table->iterateBlock(true) as $key => $item) {
            expect($item)->toBeInstanceOf(Block::class);
            expect($key)->toBeInt();
            expect($item->get("price"))->toBeGreaterThan(10);
        }
        foreach ($table->iterateBlock(false) as $key => $item) {
            expect($item)->toBeArray();
            expect($key)->toBeInt();
            expect($item["price"])->toBeGreaterThan(10);
        }

    },
);

test(
    'group by',
    function () use ($dataTable): void {
        $table = Block::make($dataTable);
        $grouped = $table->groupBy("product");
        expect($grouped->getBlock("Door"))->tohaveCount(2);
        expect($grouped->getBlock("Desk"))->tohaveCount(1);
        expect($grouped->getBlock("NotExists"))->tohaveCount(0);

    },
);


test(
    'extract json by attribute',
    function (): void {
        $file = "./tests/data/stories.json";
        $block = Block::fromJsonFile($file);
        //$block->getBlock("rels")->dumpJson();
        $rel = $block
            ->getBlock("rels")
            ->where("uuid", Operator::EQUAL, "a6af7728-eadf-4428-8cf5-343304857374");
        expect($rel)->toHaveCount(1);
        expect($rel->get("4.name"))->toBe("Category C");
        expect($rel->get("4"))->toHaveCount(22);

        $rel = $block->getBlock("rels")->where(
            field: "uuid",
            operator: Operator::EQUAL,
            value: "a6af7728-eadf-4428-8cf5-343304857374",
            preseveKeys: false,
        );
        expect($rel)->toHaveCount(1);
        expect($rel->get("0.name"))->toBe("Category C");
        expect($rel->get("0"))->toHaveCount(22);

        $rel = $block->getBlock("rels")
            ->select("uuid", "name")
            ->where(
                field: "uuid",
                operator: Operator::EQUAL,
                value: "a6af7728-eadf-4428-8cf5-343304857374",
                preseveKeys: false,
            );
        expect($rel)->toHaveCount(1);
        expect($rel->get("0.name"))->toBe("Category C");
        expect($rel->get("0"))->toHaveCount(2);
    },
);

test(
    'Apply field',
    function (): void {
        $table = Block::make(
            [
                "title" => "Title",
                "number" => 11,
            ],
        );
        $table->applyField(
            "number",
            "newfield",
            fn($value): int|float => $value * 2,
        );
        expect($table)->toHaveCount(3);
        expect($table->get("newfield"))->toBe(22);
        expect($table->get("number"))->toBe(11);
    },
);

test(
    'Apply field2',
    function (): void {
        $object = Block::make();
        $object->set('name', 'John Doe')
            ->applyField('name', 'uppercase_name', fn($value): string => strtoupper((string) $value));
        expect($object)->toHaveCount(2);
        expect($object->get("uppercase_name"))->toBe("JOHN DOE");
        expect($object->get("name"))->toBe('John Doe');
    },
);
