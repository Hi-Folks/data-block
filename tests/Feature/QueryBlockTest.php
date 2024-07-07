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
    expect($banners->get("0.component"))->toBe("hero-section");
    expect($banners->get("4.component"))->toBe("grid-section");

});

test('Query and select Block', function (): void {
    $jsonString = file_get_contents("./tests/data/story.json");

    $composerContent = Block::fromJsonString($jsonString);
    $banners = $composerContent->getBlock("story.content.body")->where(
        "component",
        "==",
        "banner",
    )->select("headline");
    expect($banners)->toHaveCount(2);
    expect($banners->get("0"))->toHaveCount(1);
    expect($banners->get("0.headline"))->toBe("New banner");
    expect($banners->get("1"))->toHaveCount(1);
    expect($banners->get("1.headline"))->toBe("Top Five Discoveries, Curiosity Rover at Mars");

    $composerContent = Block::fromJsonString($jsonString);
    $banners = $composerContent->getBlock("story.content.body")->where(
        "component",
        "!=",
        "banner",
        false,
    );
    expect($banners)->toHaveCount(8);
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


it('local dummyjson post', function (): void {

    $response = Block::fromJsonFile("./tests/data/dummy-posts-30.json");
    expect($response)->toBeInstanceOf(Block::class);
    expect($response)->toHaveCount(4);

    $posts = $response->getBlock("posts");

    expect($posts)->toBeInstanceOf(Block::class);
    expect($posts)->toHaveCount(30);
    $lovePosts = $posts->where("tags", "in", "love");
    expect($lovePosts)->toHaveCount(9);

    $mostViewedPosts = $posts->orderBy("views", "desc");
    //$mostViewedPosts->dumpJson();
    expect($mostViewedPosts)->toHaveCount(30);
    expect($mostViewedPosts->get("0.id"))->toBe(2);
    expect($mostViewedPosts->get("0.views"))->toBe(4884);

    $lessViewedPosts = $posts->orderBy("views"); //by default ascending
    //$lessViewedPosts->dumpJson();
    expect($lessViewedPosts)->toHaveCount(30);
    expect($lessViewedPosts->get("0.id"))->toBe(6);
    expect($lessViewedPosts->get("0.views"))->toBe(38);

    $mostLikedPosts = $posts->orderBy("reactions.likes", "desc");
    //$mostLikedPosts->dumpJson();
    expect($mostLikedPosts)->toHaveCount(30);
    expect($mostLikedPosts->get("0.id"))->toBe(3);
    expect($mostLikedPosts->get("0.reactions.likes"))->toBe(1448);

    expect($posts)->toHaveCount(30);
    expect($posts->get("0.id"))->toBe(1);
    expect($posts->get("0.reactions.likes"))->toBe(192);
});
