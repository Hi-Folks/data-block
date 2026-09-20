<?php

declare(strict_types=1);

namespace HiFolks\DataType\Traits;

trait TypeableBlock
{
    /** @param non-empty-string $charNestedKey */
    public function getDate(
        int|string $key,
        ?string $inputFormat = null,
        ?\DateTimeZone $timezone = null,
        string $charNestedKey = ".",
    ): ?\DateTimeImmutable {
        $value = $this->get($key, null, $charNestedKey);

        return self::parseDateValue($value, $inputFormat, $timezone);
    }

    /** @param non-empty-string $charNestedKey */
    public function requireDate(
        int|string $key,
        ?string $inputFormat = null,
        ?\DateTimeZone $timezone = null,
        string $charNestedKey = ".",
    ): \DateTimeImmutable {
        $date = $this->getDate(
            $key,
            $inputFormat,
            $timezone,
            $charNestedKey,
        );

        if ($date === null) {
            throw new \UnexpectedValueException(
                "Field '{$key}' does not contain a valid date",
            );
        }

        return $date;
    }

    /**
     * Return a string value from the get() method
     * @param int|string $key the field key , can be nested for example "commits.0.name"
     * @param string|null $defaultValue the default value returned if no value is found
     * @param non-empty-string $charNestedKey for nested field the . character is the default
     */
    public function getString(
        int|string $key,
        ?string $defaultValue = null,
        string $charNestedKey = ".",
    ): ?string {
        $returnValue = $this->get($key, $defaultValue, $charNestedKey);
        if ($returnValue === null) {
            return $defaultValue;
        }

        if (is_scalar($returnValue)) {
            return strval($returnValue);
        }

        return $defaultValue;
    }

    /**
     * Return a forced string value from the get() method
     * @param int|string $key the field key , can be nested for example "commits.0.name"
     * @param string $defaultValue the default value returned if no value is found, by default is ""
     * @param non-empty-string $charNestedKey for nested field the . character is the default
     */
    public function getStringStrict(
        int|string $key,
        string $defaultValue = "",
        string $charNestedKey = ".",
    ): string {
        $returnValue = $this->get($key, $defaultValue, $charNestedKey);
        if ($returnValue === null) {
            return $defaultValue;
        }

        if (is_scalar($returnValue)) {
            return strval($returnValue);
        }

        return $defaultValue;
    }

    /**
     * Return a forced integer value from the get() method
     * @param int|string $key the field key, can be nested for example "0.author.id"
     * @param int|null $defaultValue the default integer value returned if no value is found
     * @param non-empty-string $charNestedKey for nested field the . character is the default
     */
    public function getInt(
        int|string $key,
        ?int $defaultValue = null,
        string $charNestedKey = ".",
    ): ?int {
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
    public function getIntStrict(
        int|string $key,
        int $defaultValue = 0,
        string $charNestedKey = ".",
    ): int {
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

    /**
     * Return a forced float value from the get() method
     * @param int|string $key the field key, can be nested for example "0.author.score"
     * @param float|null $defaultValue the default float value returned if no value is found
     * @param non-empty-string $charNestedKey for nested field the . character is the default
     */
    public function getFloat(
        int|string $key,
        ?float $defaultValue = null,
        string $charNestedKey = ".",
    ): ?float {
        $returnValue = $this->get($key, null, $charNestedKey);

        if (is_scalar($returnValue)) {
            return floatval($returnValue);
        }

        return $defaultValue;
    }

    /**
     * Return a forced float value from the get() method
     * @param int|string $key the field key, can be nested for example "0.author.score"
     * @param float $defaultValue the default float value returned if no value is found
     * @param non-empty-string $charNestedKey for nested field the . character is the default
     */
    public function getFloatStrict(
        int|string $key,
        float $defaultValue = 0.0,
        string $charNestedKey = ".",
    ): float {
        $returnValue = $this->get($key, $defaultValue, $charNestedKey);

        if ($returnValue === null) {
            return $defaultValue;
        }

        if (is_scalar($returnValue)) {
            return floatval($returnValue);
        }

        return $defaultValue;
    }

    private static function parseDateValue(
        mixed $value,
        ?string $inputFormat,
        ?\DateTimeZone $timezone,
    ): ?\DateTimeImmutable {
        if ($value instanceof \DateTimeInterface) {
            $date = \DateTimeImmutable::createFromInterface($value);

            return $timezone instanceof \DateTimeZone
                ? $date->setTimezone($timezone)
                : $date;
        }

        if (!is_string($value) || trim($value) === "") {
            return null;
        }

        try {
            if ($inputFormat === null) {
                return new \DateTimeImmutable($value, $timezone);
            }

            $date = \DateTimeImmutable::createFromFormat(
                $inputFormat,
                $value,
                $timezone,
            );
            $errors = \DateTimeImmutable::getLastErrors();

            if (
                $date === false
                || ($errors !== false
                    && ($errors["warning_count"] > 0
                        || $errors["error_count"] > 0))
            ) {
                return null;
            }

            return $date;
        } catch (\Throwable) {
            return null;
        }
    }
}
