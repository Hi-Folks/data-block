<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use HiFolks\DataType\Enums\Operator;
use PHPUnit\Framework\TestCase;

final class BlockQueryTest extends TestCase
{
    public function testQueryBlock(): void
    {
        $jsonString = file_get_contents(__DIR__ . "/data/story.json");

        $composerContent = Block::fromJsonString($jsonString);

        $banners = $composerContent
            ->getBlock("story.content.body")
            ->where("component", "==", "banner");

        $this->assertCount(2, $banners);
        $this->assertSame("New banner", $banners->get("0.headline"));
        $this->assertSame(
            "Top Five Discoveries, Curiosity Rover at Mars",
            $banners->get("4.headline"),
        );

        $composerContent = Block::fromJsonString($jsonString);

        $banners = $composerContent
            ->getBlock("story.content.body")
            ->where("component", Operator::NOT_EQUAL, "banner", false);

        $this->assertCount(8, $banners);
        $this->assertSame("hero-section", $banners->get("0.component"));
        $this->assertSame("grid-section", $banners->get("4.component"));
    }

    public function testQueryAndSelectBlock(): void
    {
        $jsonString = file_get_contents(__DIR__ . "/data/story.json");

        $composerContent = Block::fromJsonString($jsonString);

        $banners = $composerContent
            ->getBlock("story.content.body")
            ->where("component", Operator::EQUAL, "banner")
            ->select("headline");

        $this->assertCount(2, $banners);

        $this->assertCount(1, $banners->get("0"));
        $this->assertSame("New banner", $banners->get("0.headline"));

        $this->assertCount(1, $banners->get("1"));
        $this->assertSame(
            "Top Five Discoveries, Curiosity Rover at Mars",
            $banners->get("1.headline"),
        );

        $composerContent = Block::fromJsonString($jsonString);

        $banners = $composerContent
            ->getBlock("story.content.body")
            ->where("component", Operator::NOT_EQUAL, "banner", false);

        $this->assertCount(8, $banners);
        $this->assertSame("hero-section", $banners->get("0.component"));
        $this->assertSame("grid-section", $banners->get("4.component"));
    }

    public function testOrderBlock(): void
    {
        $jsonString = file_get_contents(__DIR__ . "/data/story.json");

        $composerContent = Block::fromJsonString($jsonString);

        $bodyComponents = $composerContent
            ->getBlock("story.content.body")
            ->orderBy("component", "asc");

        $this->assertCount(10, $bodyComponents);
        $this->assertSame("banner", $bodyComponents->get("0.component"));
        $this->assertSame("text-section", $bodyComponents->get("9.component"));

        $bodyComponents = $composerContent
            ->getBlock("story.content.body")
            ->orderBy("component", "desc");

        $this->assertCount(10, $bodyComponents);
        $this->assertSame("banner", $bodyComponents->get("9.component"));
        $this->assertSame("text-section", $bodyComponents->get("0.component"));
    }

    public function testLocalDummyJsonPost(): void
    {
        $response = Block::fromJsonFile(__DIR__ . "/data/dummy-posts-30.json");

        $this->assertInstanceOf(Block::class, $response);
        $this->assertCount(4, $response);

        $posts = $response->getBlock("posts");

        $this->assertInstanceOf(Block::class, $posts);
        $this->assertCount(30, $posts);

        $lovePosts = $posts->where("tags", Operator::HAS, "love");
        $this->assertCount(9, $lovePosts);

        $mostViewedPosts = $posts->orderBy("views", "desc");
        $this->assertCount(30, $mostViewedPosts);
        $this->assertSame(2, $mostViewedPosts->get("0.id"));
        $this->assertSame(4884, $mostViewedPosts->get("0.views"));

        $lessViewedPosts = $posts->orderBy("views"); // asc default
        $this->assertCount(30, $lessViewedPosts);
        $this->assertSame(6, $lessViewedPosts->get("0.id"));
        $this->assertSame(38, $lessViewedPosts->get("0.views"));

        $mostLikedPosts = $posts->orderBy("reactions.likes", "desc");
        $this->assertCount(30, $mostLikedPosts);
        $this->assertSame(3, $mostLikedPosts->get("0.id"));
        $this->assertSame(1448, $mostLikedPosts->get("0.reactions.likes"));

        // Ensure original collection is not mutated
        $this->assertCount(30, $posts);
        $this->assertSame(1, $posts->get("0.id"));
        $this->assertSame(192, $posts->get("0.reactions.likes"));
    }

    public function testQueryBlockWithHas(): void
    {
        $jsonString = file_get_contents(__DIR__ . "/data/story.json");

        $composerContent = Block::fromJsonString($jsonString);

        $has = $composerContent
            ->getBlock("story.content.body")
            ->where("component", Operator::EQUAL, "banner")
            ->exists();

        $this->assertTrue($has);

        $has = $composerContent
            ->getBlock("story.content.body")
            ->where("component", Operator::NOT_EQUAL, "banner")
            ->exists();

        $this->assertTrue($has);

        $has = $composerContent
            ->getBlock("story.content.body")
            ->where("component", Operator::EQUAL, "bannerXXX")
            ->exists();

        $this->assertFalse($has);

        $has = $composerContent
            ->getBlock("story.content.body")
            ->where("component", "banner")
            ->exists();

        $this->assertTrue($has);
    }

    public function testQueryBlockExtractWhere(): void
    {
        $jsonString = file_get_contents(__DIR__ . "/data/story.json");

        $story = Block::fromJsonString($jsonString);

        $assets = $story->extractWhere("fieldtype", "asset");

        $this->assertCount(16, $assets);

        $this->assertStringStartsWith(
            "https://a.story",
            $assets->get("3.filename"),
        );
    }
}
