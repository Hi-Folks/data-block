<?php

declare(strict_types=1);

namespace HiFolks\DataType\Traits;

use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\HttpClient\HttpClientInterface;

trait LoadableBlock
{
    public static function fromJsonString(string $jsonString = "[]"): self
    {
        /** @var array<int|string, mixed> $json */
        $json = json_decode($jsonString, associative: true);
        return self::make($json);
    }

    public static function fromJsonFile(string $jsonFile): self
    {
        if (file_exists($jsonFile)) {
            $content = file_get_contents($jsonFile);
            if ($content === false) {
                return self::make([]);
            }
            return self::fromJsonString($content);
        }
        return self::make([]);
    }

    /**
     * Load Block object from a remote JSON.
     * @param $jsonUrl the URL for loading the JSON, for example https://dummyjson.com/posts
     * @param null|array<int, string> $headers the optional headers, set [] if you want to avoid headers
     */
    public static function fromJsonUrl(string $jsonUrl, ?array $headers = null): self
    {
        if (is_null($headers)) {
            $headers = [
                'Accept-language: en',
                'User-Agent: hi-folks/data-block',
            ];
        }
        $options = [
            'http' => [
                'method' => "GET",
                'header' => $headers,
            ],
        ];
        $context = stream_context_create($options);
        $content = file_get_contents($jsonUrl, context: $context);
        //var_dump($jsonUrl, $content);
        if ($content === false) {
            return self::make([]);
        }
        return self::fromJsonString($content);

    }

    /**
     * @param array<string, mixed> $options Symfony client's request options
     */
    public static function fromHttpJsonUrl(string $jsonUrl, HttpClientInterface $client, array $options = []): self
    {
        $content = $client->request('GET', $jsonUrl, $options)->getContent(false);

        if ('' === $content) {
            return self::make([]);
        }

        return self::fromJsonString($content);
    }

    public static function fromYamlFile(string $yamlFile): self
    {
        if (file_exists($yamlFile)) {
            $content = file_get_contents($yamlFile);
            if ($content === false) {
                return self::make([]);
            }
            return self::fromYamlString($content);
        }
        return self::make([]);
    }

    public static function fromYamlString(string $yamlString = ""): self
    {
        /** @var array<int|string, mixed> $yaml */
        $yaml = Yaml::parse($yamlString);
        return self::make($yaml);
    }
}
