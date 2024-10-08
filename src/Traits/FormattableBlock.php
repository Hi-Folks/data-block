<?php

declare(strict_types=1);

namespace HiFolks\DataType\Traits;

trait FormattableBlock
{
    /**
     * Get and format as date the element with $key
     *
     * @param non-empty-string $charNestedKey
     */
    public function getFormattedData(
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
