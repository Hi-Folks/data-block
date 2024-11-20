<?php

use HiFolks\DataType\Block;

test('load JSON object HTTP', function (): void {
    $jsonString = file_get_contents("./tests/data/story.json");

    $composerContent = Block::fromJsonString($jsonString);
    expect($composerContent->get("story.name"))->toBe("Home");
    expect($composerContent->getBlock("story.content"))->toBeInstanceOf(Block::class);
    expect($composerContent->get("story.content"))->toHaveKey("body");
    $bodyComponents = $composerContent->getBlock("story.content.body");
    expect($bodyComponents)->toHaveCount(10);
    expect($bodyComponents->get("0.headline"))->toBe("New banner");
    expect($bodyComponents->get("1.headline"))->toBe("Hello Everyone");
    expect($bodyComponents->get("2.headline"))->toBe("We don't know what we don't know.");
    expect($composerContent->get("cv"))->toBe(1717763755);
});


it('load JSON object', function (): void {
    $file = "./composer.json";
    $composerContent = Block::fromJsonFile($file);
    expect($composerContent->get("name"))->toBe("hi-folks/data-block");
    expect($composerContent->get("authors.0.name"))->toBe("Roberto B.");
    $composerContent->set("authors.0.name", "Test");
    expect($composerContent->get("authors.0.name"))->toBe("Test");
});

it('export to array', function (): void {
    $file = "./composer.json";
    $composerContent = Block::fromJsonFile($file);
    $array = $composerContent->toArray();
    expect($array)->toBeArray();
    expect($array)->toHaveKeys(["name","authors"]);
    expect($array["authors"])->toHaveKeys([0]);
    expect($array["authors"][0])->toHaveKeys(["name"]);
    expect($array["authors"][0]["name"])->toBe("Roberto B.");

});


it('load YAML object', function (): void {
    $file = "./.github/workflows/run-tests.yml";
    $workflow = Block::fromYamlFile($file);
    expect($workflow->get("on"))->toBeArray();
    expect($workflow->get("on"))->toHaveCount(2);
    expect($workflow->get("on.0"))->toBe("push");
    expect($workflow->get("on.1"))->toBe("pull_request");
    expect($workflow->get("jobs.test.runs-on"))->toBe('${{ matrix.os }}');


});

it('Convert Json to Yaml', function (): void {
    $file = "./composer.json";
    $composer1 = Block::fromJsonFile($file);
    $yaml = $composer1->toYaml();
    $composer2 = Block::fromYamlString($yaml);
    expect($composer2->get("name"))->toBe("hi-folks/data-block");
    expect($composer2->get("authors.0.name"))->toBe("Roberto B.");


});

it('has some value', function (): void {
    $file = "./composer.json";
    $composer = Block::fromJsonFile($file);
    expect($composer->getBlock("require"))->toBeInstanceOf(Block::class);
    expect($composer->getBlock("require.php"))->toBeInstanceOf(Block::class);
    expect($composer->get("require.php"))->toBeString();

    expect($composer->getBlock("require")->has("^8.1|^8.2|^8.3|^8.4"))->toBeTrue();
    expect($composer->getBlock("require")->hasKey("php"))->toBeTrue();
    expect($composer->getBlock("require-dev")->hasKey("pestphp/pest"))->toBeTrue();
});

it('tests some value for composer.lock', function (): void {
    $file = "./composer.lock";
    $composer = Block::fromJsonFile($file);
    expect($composer->getBlock("packages"))->toBeInstanceOf(Block::class);
    expect($composer->getBlock("packages"))->toHaveCount(6);
    expect($composer->getBlock("packages")->where("dist.type", "zip"))->toHaveCount(6);
    expect($composer->getBlock("packages")->where("dist.type", "zip"))->toHaveCount(6);
    expect($composer->getBlock("packages")->where("source.type", "git"))->toHaveCount(6);
});
