<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\TestCase;

final class BlockTest extends TestCase
{
    public function testLoadJsonObjectHttp(): void
    {
        $jsonString = file_get_contents(__DIR__ . "/data/story.json");

        $composerContent = Block::fromJsonString($jsonString);

        $this->assertSame("Home", $composerContent->get("story.name"));

        $this->assertInstanceOf(
            Block::class,
            $composerContent->getBlock("story.content"),
        );

        $this->assertArrayHasKey(
            "body",
            $composerContent->get("story.content"),
        );

        $bodyComponents = $composerContent->getBlock("story.content.body");

        $this->assertCount(10, $bodyComponents);

        $this->assertSame("New banner", $bodyComponents->get("0.headline"));
        $this->assertSame("Hello Everyone", $bodyComponents->get("1.headline"));
        $this->assertSame(
            "We don't know what we don't know.",
            $bodyComponents->get("2.headline"),
        );

        $this->assertSame(1717763755, $composerContent->get("cv"));
    }

    public function testLoadJsonObject(): void
    {
        $file = __DIR__ . "/../composer.json";

        $composerContent = Block::fromJsonFile($file);

        $this->assertSame("hi-folks/data-block", $composerContent->get("name"));
        $this->assertSame(
            "Roberto B.",
            $composerContent->get("authors.0.name"),
        );

        $composerContent->set("authors.0.name", "Test");

        $this->assertSame("Test", $composerContent->get("authors.0.name"));
    }

    public function testExportToArray(): void
    {
        $file = __DIR__ . "/../composer.json";

        $composerContent = Block::fromJsonFile($file);

        $array = $composerContent->toArray();

        $this->assertIsArray($array);

        $this->assertArrayHasKey("name", $array);
        $this->assertArrayHasKey("authors", $array);

        $this->assertArrayHasKey(0, $array["authors"]);
        $this->assertArrayHasKey("name", $array["authors"][0]);

        $this->assertSame("Roberto B.", $array["authors"][0]["name"]);
    }

    public function testLoadYamlObject(): void
    {
        $file = __DIR__ . "/../.github/workflows/run-tests.yml";

        $workflow = Block::fromYamlFile($file);

        $this->assertIsArray($workflow->get("on"));
        $this->assertCount(2, $workflow->get("on"));

        $this->assertSame("push", $workflow->get("on.0"));
        $this->assertSame("pull_request", $workflow->get("on.1"));

        $this->assertSame(
            '${{ matrix.os }}',
            $workflow->get("jobs.test.runs-on"),
        );
    }

    public function testConvertJsonToYaml(): void
    {
        $file = __DIR__ . "/../composer.json";

        $composer1 = Block::fromJsonFile($file);

        $yaml = $composer1->toYaml();

        $composer2 = Block::fromYamlString($yaml);

        $this->assertSame("hi-folks/data-block", $composer2->get("name"));
        $this->assertSame("Roberto B.", $composer2->get("authors.0.name"));
    }

    public function testHasSomeValue(): void
    {
        $file = __DIR__ . "/../composer.json";

        $composer = Block::fromJsonFile($file);

        $this->assertInstanceOf(Block::class, $composer->getBlock("require"));
        $this->assertInstanceOf(
            Block::class,
            $composer->getBlock("require.php"),
        );
        $this->assertIsString($composer->get("require.php"));

        $this->assertTrue(
            $composer->getBlock("require")->has("^8.3|^8.4|^8.5"),
        );

        $this->assertTrue($composer->getBlock("require")->hasKey("php"));

        $this->assertTrue(
            $composer->getBlock("require-dev")->hasKey("phpunit/phpunit"),
        );
    }

    public function testSomeValueForComposerLock(): void
    {
        $file = __DIR__ . "/data/dummy-composer.lock";

        $composer = Block::fromJsonFile($file);

        $this->assertInstanceOf(Block::class, $composer->getBlock("packages"));
        $this->assertCount(7, $composer->getBlock("packages"));

        $this->assertCount(
            7,
            $composer->getBlock("packages")->where("dist.type", "zip"),
        );

        $this->assertCount(
            7,
            $composer->getBlock("packages")->where("source.type", "git"),
        );
    }
}
