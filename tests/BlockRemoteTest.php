<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;

final class BlockRemoteTest extends TestCase
{
    #[Group("url")]
    public function testRemoteJson(): void
    {
        $url = "https://api.github.com/repos/hi-folks/data-block/commits";

        $commits = Block::fromJsonUrl($url);

        $this->assertInstanceOf(Block::class, $commits);
        $this->assertCount(30, $commits);

        $myCommits = $commits->where("commit.author.name", "like", "Roberto");

        foreach ($myCommits as $value) {
            $this->assertIsString($value->get("commit.message"));
        }
    }

    #[Group("url")]
    public function testRemoteJsonWithSymfonyHttpClient(): void
    {
        $url = "https://api.github.com/repos/hi-folks/data-block/commits";

        $commits = Block::fromHttpJsonUrl($url, HttpClient::create());

        $this->assertInstanceOf(Block::class, $commits);
        $this->assertCount(30, $commits);

        $myCommits = $commits->where("commit.author.name", "like", "Roberto");

        foreach ($myCommits as $value) {
            $this->assertIsString($value->get("commit.message"));
        }
    }

    #[Group("url")]
    public function testRemoteDummyJsonPost(): void
    {
        $url = "https://dummyjson.com/posts";

        $response = Block::fromJsonUrl($url);
        $posts = $response->getBlock("posts");

        $this->assertInstanceOf(Block::class, $posts);
        $this->assertCount(30, $posts);

        $lovePosts = $posts->where("tags", "has", "love");

        $this->assertCount(9, $lovePosts);
    }

    #[Group("url")]
    public function testRemoteForeach(): void
    {
        $url = "https://dummyjson.com/posts";

        $posts = Block::fromJsonUrl($url)
            ->getBlock("posts")
            ->where(
                field: "tags",
                operator: "has",
                value: "love",
                preseveKeys: false,
            )
            ->forEach(
                fn($element): array => [
                    "title" => strtoupper((string) $element->get("title")),
                    "tags" => count($element->get("tags")),
                ],
            );

        $this->assertInstanceOf(Block::class, $posts);
        $this->assertCount(9, $posts);

        $this->assertSame(
            "HOPES AND DREAMS WERE DASHED THAT DAY.",
            $posts->get("0.title"),
        );

        $this->assertSame(3, $posts->get("0.tags"));
    }
}
