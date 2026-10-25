<?php

declare(strict_types=1);

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
     * Returns the JSON string, pretty-printed by default.
     *
     * Pass JSON_THROW_ON_ERROR in $flags when encoding failures should throw a
     * JsonException instead of returning false.
     */
    public function toJson(
        int $flags = JSON_PRETTY_PRINT,
        int $depth = 512,
    ): string|false {
        if ($depth < 1) {
            throw new \ValueError("JSON depth must be greater than zero");
        }

        return json_encode($this->data, $flags, $depth);
    }

    public function dumpJson(): void
    {
        echo $this->toJson();
    }
    public function dump(): void
    {
        var_dump($this);
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

    /**
     * Saves the JSON string to a file.
     *
     * Returns false when the destination already exists without overwrite
     * permission, JSON encoding fails, or the encoded data cannot be written.
     * Pass JSON_THROW_ON_ERROR in $flags to make encoding failures throw.
     * Atomic writes use a temporary file in the destination directory and only
     * replace the destination after the complete JSON has been written.
     *
     * @param string $filename file name for example "./file.json"
     * @param bool $overwrite if the file already exists you can force overwriting
     */
    public function saveToJson(
        string $filename,
        bool $overwrite = false,
        int $flags = JSON_PRETTY_PRINT,
        int $depth = 512,
        bool $atomic = false,
    ): bool {
        if (file_exists($filename) && !$overwrite) {
            return false;
        }

        $json = $this->toJson($flags, $depth);
        if ($json === false) {
            return false;
        }

        if ($atomic) {
            return self::writeJsonAtomically($filename, $json);
        }

        return @file_put_contents($filename, $json) !== false;
    }

    private static function writeJsonAtomically(
        string $filename,
        string $json,
    ): bool {
        $directory = dirname($filename);
        if (!is_dir($directory)) {
            return false;
        }

        $temporaryFile = @tempnam(
            $directory,
            "." . basename($filename) . ".tmp-",
        );
        if ($temporaryFile === false) {
            return false;
        }

        try {
            $written = @file_put_contents($temporaryFile, $json, LOCK_EX);
            if ($written === false || $written !== strlen($json)) {
                return false;
            }

            if (!@rename($temporaryFile, $filename)) {
                return false;
            }

            $temporaryFile = null;

            return true;
        } finally {
            if (
                $temporaryFile !== null
                && (is_file($temporaryFile) || is_link($temporaryFile))
            ) {
                @unlink($temporaryFile);
            }
        }
    }
}
