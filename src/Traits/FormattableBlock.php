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
}
