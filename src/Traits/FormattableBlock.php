<?php

declare(strict_types=1);

namespace HiFolks\DataType\Traits;

trait FormattableBlock
{
    /**
     * Get and format as date the element with $key
     * @param mixed $key the key, can be nested for example "some.datetime"
     * @param string $format the format default is "Y-m-d H:i:s"
     * @param mixed $defaultValue, the value returned in the case the key not exists
     * @param non-empty-string $charNestedKey the separator for nested keys, default is "."
     */
    public function getFormattedDateTime(
        mixed $key,
        string $format = "Y-m-d H:i:s",
        mixed $defaultValue = null,
        string $charNestedKey = ".",
    ): string|null {
        $value =  $this->get($key, $defaultValue, $charNestedKey);
        if (is_null($value)) {
            return null;
        }
        $date = new \DateTimeImmutable($value);

        return $date->format($format);
    }

    /**
     * Get and format a byte value in KB, MB, GB, etc.
     * @param mixed $key the key, can be nested for example "some.filesize"
     * @param mixed $defaultValue the value returned if the key does not exist
     * @param non-empty-string $charNestedKey the separator for nested keys, default is "."
     * @param int $precision the number of decimal points for the formatted output, default is 2
     */
    public function getFormattedByte(
        mixed $key,
        mixed $defaultValue = null,
        string $charNestedKey = ".",
        int $precision = 2,
    ): string {
        $bytes = $this->get($key, $defaultValue, $charNestedKey);
        if (is_null($bytes)) {
            return '0 B';
        }

        $kilobyte = 1024;
        $megabyte = $kilobyte * 1024;
        $gigabyte = $megabyte * 1024;
        $terabyte = $gigabyte * 1024;

        if ($bytes < $kilobyte) {
            return $bytes . ' B';
        }

        if ($bytes < $megabyte) {
            return number_format($bytes / $kilobyte, $precision) . ' KB';
        }

        if ($bytes < $gigabyte) {
            return number_format($bytes / $megabyte, $precision) . ' MB';
        }

        if ($bytes < $terabyte) {
            return number_format($bytes / $gigabyte, $precision) . ' GB';
        }

        return number_format($bytes / $terabyte, $precision) . ' TB';
    }
}
