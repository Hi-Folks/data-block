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
     * Return a required, non-blank string without coercing another scalar type.
     *
     * @param non-empty-string $charNestedKey
     *
     * @throws \OutOfBoundsException When the field does not exist.
     * @throws \UnexpectedValueException When the field is null, blank, or not a string.
     */
    public function requireString(
        int|string $key,
        string $charNestedKey = ".",
    ): string {
        $value = $this->requiredValue($key, $charNestedKey);

        if (!is_string($value) || trim($value) === "") {
            throw self::invalidRequiredValue($key, "a non-blank string");
        }

        return $value;
    }

    /**
     * Return a required integer or safely parse an integer-formatted string.
     *
     * Integral floats are accepted, but fractional and out-of-range values are
     * rejected instead of being silently truncated.
     *
     * @param non-empty-string $charNestedKey
     *
     * @throws \OutOfBoundsException When the field does not exist.
     * @throws \UnexpectedValueException When the field is null or not a valid integer.
     */
    public function requireInt(
        int|string $key,
        string $charNestedKey = ".",
    ): int {
        $value = $this->requiredValue($key, $charNestedKey);

        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            if (is_finite($value) && floor($value) === $value) {
                $integer = (int) $value;
                if ((float) $integer === $value) {
                    return $integer;
                }
            }

            throw self::invalidRequiredValue($key, "an integer");
        }

        if (is_string($value)) {
            $integer = self::parseIntegerString($value);
            if ($integer !== null) {
                return $integer;
            }
        }

        throw self::invalidRequiredValue($key, "an integer");
    }

    /**
     * Return a required finite number as a float.
     *
     * @param non-empty-string $charNestedKey
     *
     * @throws \OutOfBoundsException When the field does not exist.
     * @throws \UnexpectedValueException When the field is null or not a valid finite number.
     */
    public function requireFloat(
        int|string $key,
        string $charNestedKey = ".",
    ): float {
        $value = $this->requiredValue($key, $charNestedKey);

        if (
            (is_int($value) || is_float($value) || is_string($value))
            && is_numeric($value)
        ) {
            $float = (float) $value;
            if (is_finite($float)) {
                return $float;
            }
        }

        throw self::invalidRequiredValue($key, "a finite number");
    }

    /**
     * Return a required boolean using an explicit, case-insensitive vocabulary.
     *
     * Accepted values are booleans, integers 1 and 0, and the strings true,
     * false, 1, 0, yes, no, on, and off. Surrounding whitespace is ignored.
     *
     * @param non-empty-string $charNestedKey
     *
     * @throws \OutOfBoundsException When the field does not exist.
     * @throws \UnexpectedValueException When the field is null or cannot be parsed as a boolean.
     */
    public function requireBoolean(
        int|string $key,
        string $charNestedKey = ".",
    ): bool {
        $value = $this->requiredValue($key, $charNestedKey);

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) && ($value === 0 || $value === 1)) {
            return $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            if (in_array($normalized, ["true", "1", "yes", "on"], true)) {
                return true;
            }
            if (in_array($normalized, ["false", "0", "no", "off"], true)) {
                return false;
            }
        }

        throw self::invalidRequiredValue($key, "a boolean");
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

    /** @param non-empty-string $charNestedKey */
    private function requiredValue(
        int|string $key,
        string $charNestedKey,
    ): mixed {
        $missing = new \stdClass();
        $value = $this->get($key, $missing, $charNestedKey);

        if ($value === $missing) {
            throw new \OutOfBoundsException("Required field '{$key}' is missing");
        }
        if ($value === null) {
            throw new \UnexpectedValueException("Required field '{$key}' is null");
        }

        return $value;
    }

    private static function invalidRequiredValue(
        int|string $key,
        string $expected,
    ): \UnexpectedValueException {
        return new \UnexpectedValueException(
            "Required field '{$key}' must contain {$expected}",
        );
    }

    private static function parseIntegerString(string $value): ?int
    {
        if (preg_match('/^[+-]?\d+$/D', $value) !== 1) {
            return null;
        }

        $negative = str_starts_with($value, "-");
        $digits = ltrim($value, "+-");
        $digits = ltrim($digits, "0");
        $digits = $digits === "" ? "0" : $digits;
        $canonical = $negative && $digits !== "0" ? "-{$digits}" : $digits;
        $integer = (int) $value;

        return (string) $integer === $canonical ? $integer : null;
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
