<?php

use HiFolks\DataType\Block;

test('Query Block', function (): void {
    $jsonString = file_get_contents("./tests/data/story.json");

    $composerContent = Block::fromJsonString($jsonString);
    $banners = $composerContent->getBlock("story.content.body")->where(
        "component",
        "==",
        "banner",
    );
    expect($banners)->toHaveCount(2);
    expect($banners->get("0.headline"))->toBe("New banner");
    expect($banners->get("4.headline"))->toBe("Top Five Discoveries, Curiosity Rover at Mars");

    $composerContent = Block::fromJsonString($jsonString);
    $banners = $composerContent->getBlock("story.content.body")->where(
        "component",
        "!=",
        "banner",
        false,
    );
    expect($banners)->toHaveCount(8);
    var_dump($banners);
    expect($banners->get("0.component"))->toBe("hero-section");
    expect($banners->get("4.component"))->toBe("grid-section");

});

test('Order Block', function (): void {
    $jsonString = file_get_contents("./tests/data/story.json");

    $composerContent = Block::fromJsonString($jsonString);
    $bodyComponents = $composerContent->getBlock("story.content.body")->orderBy(
        "component",
        "asc",
    );
    expect($bodyComponents)->toHaveCount(10);
    expect($bodyComponents->get("0.component"))->toBe("banner");
    expect($bodyComponents->get("9.component"))->toBe("text-section");

    $bodyComponents = $composerContent->getBlock("story.content.body")->orderBy(
        "component",
        "desc",
    );
    expect($bodyComponents)->toHaveCount(10);
    expect($bodyComponents->get("9.component"))->toBe("banner");
    expect($bodyComponents->get("0.component"))->toBe("text-section");

});
