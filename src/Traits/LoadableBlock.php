<?php

namespace HiFolks\DataType\Traits;

use Symfony\Component\Yaml\Yaml;

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
