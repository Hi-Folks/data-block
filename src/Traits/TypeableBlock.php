<?php

declare(strict_types=1);

namespace HiFolks\DataType\Traits;

trait TypeableBlock
{
    /**
     * Return a forced string value from the get() method
     * @param int|string $key the field key , can be nested for example "commits.0.name"
     * @param string|null $defaultValue the default value returned if no value is found
     * @param non-empty-string $charNestedKey for nested field the . character is the default
     */
    public function getString(
        int|string $key,
        ?string $defaultValue = null,
        string $charNestedKey = ".",
    ): string {
        return (string) $this->get($key, $defaultValue, $charNestedKey);
    }

    /**
     * Return a forced integer value from the get() method
     * @param int|string $key the field key, can be nested for example "0.author.id"
     * @param int|null $defaultValue the default integer value returned if no value is found
     * @param non-empty-string $charNestedKey for nested field the . character is the default
     */
    public function getInt(int|string $key, ?int $defaultValue = null, string $charNestedKey = "."): ?int
    {
        $returnValue = $this->get($key, null, $charNestedKey);

        if (is_scalar($returnValue)) {
            return intval($returnValue);
        }

        return $defaultValue;
    }

    /**
     * Return a forced integer value from the get() method
     * @param int|string $key the field key, can be nested for example "0.author.id"
     * @param int $defaultValue the default integer value returned if no value is found
     * @param non-empty-string $charNestedKey for nested field the . character is the default
     */
    public function getIntStrict(int|string $key, int $defaultValue = 0, string $charNestedKey = "."): int
    {
        $returnValue = $this->get($key, $defaultValue, $charNestedKey);

        if ($returnValue === null) {
            return $defaultValue;
        }

        if (is_scalar($returnValue)) {
            return intval($returnValue);
        }

        return $defaultValue;
    }
    /**
     * Return a forced boolean value from the get() method
     * @param int|string $key the filed key , can be nested for example "commits.0.editable"
     * @param bool|null $defaultValue the default value returned if no value is found
     * @param non-empty-string $charNestedKey for nested field the . character is the default
     */
    public function getBoolean(
        int|string $key,
        ?bool $defaultValue = null,
        string $charNestedKey = ".",
    ): ?bool {
        $returnValue = $this->get($key, $defaultValue, $charNestedKey);

        if (is_scalar($returnValue)) {
            return boolval($returnValue);
        }

        return $defaultValue;
    }

    /**
     * Return a forced boolean value from the get() method
     * @param int|string $key the filed key , can be nested for example "commits.0.editable"
     * @param bool $defaultValue the default value returned if no value is found
     * @param non-empty-string $charNestedKey for nested field the . character is the default
     */
    public function getBooleanStrict(
        int|string $key,
        bool $defaultValue = false,
        string $charNestedKey = ".",
    ): ?bool {
        $returnValue = $this->get($key, $defaultValue, $charNestedKey);

        if (is_scalar($returnValue)) {
            return boolval($returnValue);
        }

        return $defaultValue;
    }
}
