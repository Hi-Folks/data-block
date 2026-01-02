<?php

use PHPUnit\Framework\TestCase;
use HiFolks\DataType\Block;

class LoadJsonObjectHttpTest extends TestCase
{
    public function testLoadJsonObjectHttp(): void
    {
        $jsonString = file_get_contents(__DIR__ . "/../data/story.json");

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
}
