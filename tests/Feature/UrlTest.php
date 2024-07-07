<?php

use HiFolks\DataType\Block;

it('remote json', function (): void {

    $url = "https://api.github.com/repos/hi-folks/data-block/commits";
    $commits = Block::fromJsonUrl($url);
    expect($commits)->toBeInstanceOf(Block::class);
    expect($commits)->toHaveCount(30);
    $myCommits = $commits->where("commit.author.name", "like", "Roberto");
    foreach ($myCommits as $value) {
        expect($value->get("commit.message"))->toBeString();
    }

})->group("url");

it('remote dummyjson post', function (): void {

    $url = "https://dummyjson.com/posts";
    $response = Block::fromJsonUrl($url);
    $posts = $response->getBlock("posts");

    expect($posts)->toBeInstanceOf(Block::class);
    expect($posts)->toHaveCount(30);
    $lovePosts = $posts->where("tags", "in", "love");
    expect($lovePosts)->toHaveCount(9);


})->group("url");
