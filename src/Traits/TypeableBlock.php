<?php

declare(strict_types=1);

namespace HiFolks\DataType\Traits;

trait TypeableBlock
{
    /**
     *
     * @param non-empty-string $charNestedKey
     */
    public function getInt(int|string $key, ?int $defaultValue = null, string $charNestedKey = "."): ?int
    {
        $returnValue = $this->get($key, null, $charNestedKey);

        if (is_scalar($returnValue)) {
            return intval($returnValue);
        }

        return $defaultValue;
    }
}
