<?php

namespace HiFolks\DataType\Traits;

use Symfony\Component\Yaml\Yaml;

trait ExportableBlock
{
    /**
    * Returns the native array
    * @return array<int|string, mixed>
        */
    public function toArray(): array
    {
        return $this->data;
    }


    /**
     * Returns the JSON String (pretty format by default)
     * @return string|false
     */
    public function toJson(): string|false
    {
        return json_encode($this->data, JSON_PRETTY_PRINT);
    }

    public function toJsonObject(): mixed
    {
        $jsonString = json_encode($this->data);
        if ($jsonString === false) {
            return false;
        }
        return json_decode($jsonString, associative: false);
    }


    /**
     * Returns the YAML String
     */
    public function toYaml(): string
    {
        return Yaml::dump($this->data, 3, 2);
    }
}
