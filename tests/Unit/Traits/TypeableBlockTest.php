<?php

use HiFolks\DataType\Block;

test("Testing getString()", function (): void {
    $data1 = Block::fromJsonFile(
        __DIR__ . "/../../data/commits-json/commits-10-p1.json",
    );
    $data2 = Block::fromJsonFile(
        __DIR__ . "/../../data/commits-json/commits-10-p2.json",
    );
    $data3 = Block::fromJsonFile(
        __DIR__ . "/../../data/commits-json/commits-10-p3.json",
    );
    $data1->append($data2)->append($data3);
    expect($data1)->toHaveCount(30);
    expect($data2)->toHaveCount(10);
    expect($data1->getString("0.commit.author.date"))->toBeString();
    expect($data1->getString("0.commit.author.notexist"))->toBeNull();
    expect($data1->getString("0.commit.author.notexist", "AA"))
        ->toBeString()
        ->toEqual("AA");
    expect($data1->getString("0.commit.comment_count"))
        ->toBeString()
        ->toEqual("0");
    expect($data1->getString("0.commit.comment_count", 1))
        ->toBeString()
        ->toEqual("0");
    expect($data1->getString("0.commit.comment_countnotexists", 1))
        ->toBeString()
        ->toEqual("1");
});

test("Testing getStringStrict()", function (): void {
    $data1 = Block::fromJsonFile(
        __DIR__ . "/../../data/commits-json/commits-10-p1.json",
    );
    $data2 = Block::fromJsonFile(
        __DIR__ . "/../../data/commits-json/commits-10-p2.json",
    );
    $data3 = Block::fromJsonFile(
        __DIR__ . "/../../data/commits-json/commits-10-p3.json",
    );
    $data1->append($data2)->append($data3);
    expect($data1)->toHaveCount(30);
    expect($data2)->toHaveCount(10);
    expect($data1->getStringStrict("0.commit.author.date"))->toBeString();
    expect($data1->getStringStrict("0.commit.author.notexist"))
        ->toBeString()
        ->toBe("");
    expect($data1->getStringStrict("0.commit.author.notexist", "AA"))
        ->toBeString()
        ->toEqual("AA");
    expect($data1->getStringStrict("0.commit.comment_count"))
        ->toBeString()
        ->toEqual("0");
    expect($data1->getStringStrict("0.commit.comment_count", 1))
        ->toBeString()
        ->toEqual("0");
    expect($data1->getStringStrict("0.commit.comment_countnotexists", 1))
        ->toBeString()
        ->toEqual("1");
});

test("Testing getInt()", function (): void {
    $data1 = Block::fromJsonFile(
        __DIR__ . "/../../data/commits-json/commits-10-p1.json",
    );
    // $data2 = Block::fromJsonFile(__DIR__ . "/../../data/commits-json/commits-10-p2.json");
    // $data3 = Block::fromJsonFile(__DIR__ . "/../../data/commits-json/commits-10-p3.json");

    expect($data1->getInt("0.author.id"))->toBeInt()->toBe(678434);
    expect($data1->getInt("0.author.idx"))->toBeNull();
    expect($data1->getInt("0.author.idx", 44))->toBeInt()->toBe(44);
});

test("Testing getIntStrict()", function (): void {
    $data1 = Block::fromJsonFile(
        __DIR__ . "/../../data/commits-json/commits-10-p1.json",
    );
    // $data2 = Block::fromJsonFile(__DIR__ . "/../../data/commits-json/commits-10-p2.json");
    // $data3 = Block::fromJsonFile(__DIR__ . "/../../data/commits-json/commits-10-p3.json");

    expect($data1->getIntStrict("0.author.id"))->toBeInt()->toBe(678434);
    expect($data1->getIntStrict("0.author.idx"))->toBeInt()->toBe(0);
    expect($data1->getIntStrict("0.author.idx", 44))->toBeInt()->toBe(44);
    expect($data1->getIntStrict("0.commit.author.date"))->toBeInt()->toBe(2024);
});

test("Testing getBoolean()", function (): void {
    $data1 = Block::fromJsonFile(
        __DIR__ . "/../../data/commits-json/commits-10-p1.json",
    );
    $data2 = Block::fromJsonFile(
        __DIR__ . "/../../data/commits-json/commits-10-p2.json",
    );
    $data3 = Block::fromJsonFile(
        __DIR__ . "/../../data/commits-json/commits-10-p3.json",
    );
    $data1->append($data2)->append($data3);
    expect($data1)->toHaveCount(30);
    expect($data2)->toHaveCount(10);
    expect($data1->getBoolean("0.author.site_admin"))->toBeBool();
    expect($data1->getBoolean("0.author.notexist"))->toBeNull();
    expect($data1->getBoolean("0.author.notexist"))->toBeNull();
    expect($data1->getBoolean("0.author.notexist", true))
        ->toBeBool()
        ->toEqual(true);
    expect($data1->getBoolean("0.author.notexist", false))
        ->toBeBool()
        ->toEqual(false);
    expect($data1->getBoolean("0.author.site_admin"))
        ->toBeBool()
        ->toEqual(false);
    expect($data1->getBoolean("0.author.site_admin", true))
        ->toBeBool()
        ->toEqual(false);
    expect($data1->getBoolean("0.commit.comment_countnotexists", true))
        ->toBeBool()
        ->toEqual(true);
});

test("Testing getBooleanStrict()", function (): void {
    $data1 = Block::fromJsonFile(
        __DIR__ . "/../../data/commits-json/commits-10-p1.json",
    );
    $data2 = Block::fromJsonFile(
        __DIR__ . "/../../data/commits-json/commits-10-p2.json",
    );
    $data3 = Block::fromJsonFile(
        __DIR__ . "/../../data/commits-json/commits-10-p3.json",
    );
    $data1->append($data2)->append($data3);
    expect($data1)->toHaveCount(30);
    expect($data2)->toHaveCount(10);
    expect($data1->getBooleanStrict("0.author.site_admin"))->toBeBool();
    expect($data1->getBooleanStrict("0.author.notexist"))
        ->toBeBool()
        ->toBeFalse();
    expect($data1->getBooleanStrict("0.author.notexist"))->toBeBool();
    expect($data1->getBooleanStrict("0.author.notexist", true))
        ->toBeBool()
        ->toEqual(true);
    expect($data1->getBooleanStrict("0.author.notexist", false))
        ->toBeBool()
        ->toEqual(false);
    expect($data1->getBooleanStrict("0.author.site_admin"))
        ->toBeBool()
        ->toEqual(false);
    expect($data1->getBooleanStrict("0.author.site_admin", true))
        ->toBeBool()
        ->toEqual(false);
    expect($data1->getBooleanStrict("0.commit.comment_countnotexists", true))
        ->toBeBool()
        ->toEqual(true);
});

$fruitsArray = [
    "avocado" => [
        "name" => "Avocado",
        "fruit" => "🥑",
        "wikipedia" => "https://en.wikipedia.org/wiki/Avocado",
        "color" => "green",
        "rating" => 8,
    ],
    "apple" => [
        "name" => "Apple",
        "fruit" => "🍎",
        "wikipedia" => "https://en.wikipedia.org/wiki/Apple",
        "color" => "red",
        "rating" => 7,
    ],
    "banana" => [
        "name" => "Banana",
        "fruit" => "🍌",
        "wikipedia" => "https://en.wikipedia.org/wiki/Banana",
        "color" => "yellow",
        "rating" => 8.5,
    ],
    "cherry" => [
        "name" => "Cherry",
        "fruit" => "🍒",
        "wikipedia" => "https://en.wikipedia.org/wiki/Cherry",
        "color" => "red",
        "rating" => 9,
    ],
];

test("Testing getFloat() and float strict", function () use (
    $fruitsArray,
): void {
    $data = Block::make($fruitsArray);
    expect($data)->toBeInstanceOf(Block::class);
    expect($data)->toHaveLength(4);
    expect($data->getFloat("banana.rating"))->toBe(8.5);
    expect($data->getFloat("apple.rating"))->toBe(7.0);
    expect($data->getFloat("foo", 1.2))->toBe(1.2);
    expect($data->getFloatStrict("missing"))->toBe(0.0);
});
