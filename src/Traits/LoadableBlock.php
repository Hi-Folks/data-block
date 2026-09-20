<?php

declare(strict_types=1);

namespace HiFolks\DataType\Traits;

use Generator;
use HiFolks\DataType\Block;
use HiFolks\DataType\CsvReader;
use HiFolks\DataType\Enums\CsvRowWidth;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\HttpClient\HttpClientInterface;

trait LoadableBlock
{
    /**
     * @param null|callable(Block, int): (array<int|string, mixed>|Block) $normalize
     */
    public static function fromCsvFile(
        string $csvFile,
        string $delimiter = ",",
        string $enclosure = '"',
        string $escape = "",
        string $encoding = "UTF-8",
        bool $header = true,
        bool $skipEmptyRows = true,
        CsvRowWidth $rowWidth = CsvRowWidth::STRICT,
        ?callable $normalize = null,
    ): self {
        $rows = iterator_to_array(CsvReader::rows(
            $csvFile,
            $delimiter,
            $enclosure,
            $escape,
            $encoding,
            $header,
            $skipEmptyRows,
            $rowWidth,
            $normalize,
        ));

        return self::make($rows);
    }

    /**
     * @param null|callable(Block, int): (array<int|string, mixed>|Block) $normalize
     * @return Generator<int, Block>
     */
    public static function streamCsvFile(
        string $csvFile,
        string $delimiter = ",",
        string $enclosure = '"',
        string $escape = "",
        string $encoding = "UTF-8",
        bool $header = true,
        bool $skipEmptyRows = true,
        CsvRowWidth $rowWidth = CsvRowWidth::STRICT,
        ?callable $normalize = null,
    ): Generator {
        foreach (CsvReader::rows(
            $csvFile,
            $delimiter,
            $enclosure,
            $escape,
            $encoding,
            $header,
            $skipEmptyRows,
            $rowWidth,
            $normalize,
        ) as $index => $row) {
            yield $index => self::make($row);
        }
    }

    /**
     * @param null|callable(Block, int): (array<int|string, mixed>|Block) $normalize
     * @return Generator<int, Block>
     */
    public static function chunkCsvFile(
        string $csvFile,
        int $chunkSize = 1000,
        string $delimiter = ",",
        string $enclosure = '"',
        string $escape = "",
        string $encoding = "UTF-8",
        bool $header = true,
        bool $skipEmptyRows = true,
        CsvRowWidth $rowWidth = CsvRowWidth::STRICT,
        ?callable $normalize = null,
    ): Generator {
        if ($chunkSize < 1) {
            throw new \InvalidArgumentException(
                "The CSV chunk size must be greater than zero",
            );
        }

        $chunk = [];
        $chunkIndex = 0;
        foreach (self::streamCsvFile(
            $csvFile,
            $delimiter,
            $enclosure,
            $escape,
            $encoding,
            $header,
            $skipEmptyRows,
            $rowWidth,
            $normalize,
        ) as $row) {
            $chunk[] = $row->toArray();
            if (count($chunk) === $chunkSize) {
                yield $chunkIndex++ => self::make($chunk);
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            yield $chunkIndex => self::make($chunk);
        }
    }

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
        $headers ??= [
            'Accept-language: en',
            'User-Agent: hi-folks/data-block',
        ];
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
