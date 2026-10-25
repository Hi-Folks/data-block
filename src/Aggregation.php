<?php

declare(strict_types=1);

namespace HiFolks\DataType;

use HiFolks\DataType\Enums\AggregationType;
use HiFolks\DataType\Enums\Operator;

/**
 * Immutable description of one built-in aggregation.
 *
 * Create specifications through the named static constructors and pass them to
 * Block::aggregate() or Block::aggregateBy().
 */
final readonly class Aggregation
{
    private function __construct(
        private AggregationType $type,
        private int|string|null $valueField = null,
        private int|string|null $conditionField = null,
        private ?string $operator = null,
        private mixed $expectedValue = null,
    ) {}

    public static function count(): self
    {
        return new self(AggregationType::COUNT);
    }

    public static function countWhere(
        int|string $field,
        mixed $operator = Operator::EQUAL,
        mixed $value = null,
    ): self {
        self::validateField($field);

        [$normalizedOperator, $expectedValue] = self::normalizeCondition(
            $operator,
            $value,
            func_num_args(),
            1,
        );

        return new self(
            AggregationType::COUNT_WHERE,
            conditionField: $field,
            operator: $normalizedOperator,
            expectedValue: $expectedValue,
        );
    }

    public static function sum(int|string $field): self
    {
        self::validateField($field);

        return new self(AggregationType::SUM, valueField: $field);
    }

    public static function sumWhere(
        int|string $valueField,
        int|string $conditionField,
        mixed $operator = Operator::EQUAL,
        mixed $value = null,
    ): self {
        self::validateField($valueField);
        self::validateField($conditionField);

        [$normalizedOperator, $expectedValue] = self::normalizeCondition(
            $operator,
            $value,
            func_num_args(),
            2,
        );

        return new self(
            AggregationType::SUM_WHERE,
            valueField: $valueField,
            conditionField: $conditionField,
            operator: $normalizedOperator,
            expectedValue: $expectedValue,
        );
    }

    public static function min(int|string $field): self
    {
        self::validateField($field);

        return new self(AggregationType::MIN, valueField: $field);
    }

    public static function max(int|string $field): self
    {
        self::validateField($field);

        return new self(AggregationType::MAX, valueField: $field);
    }

    public static function average(int|string $field): self
    {
        self::validateField($field);

        return new self(AggregationType::AVERAGE, valueField: $field);
    }

    /** @internal Used by the Block aggregation engine. */
    public function type(): AggregationType
    {
        return $this->type;
    }

    /** @internal Used by the Block aggregation engine. */
    public function valueField(): int|string|null
    {
        return $this->valueField;
    }

    /** @internal Used by the Block aggregation engine. */
    public function conditionField(): int|string|null
    {
        return $this->conditionField;
    }

    /** @internal Used by the Block aggregation engine. */
    public function operator(): ?string
    {
        return $this->operator;
    }

    /** @internal Used by the Block aggregation engine. */
    public function expectedValue(): mixed
    {
        return $this->expectedValue;
    }

    private static function validateField(int|string $field): void
    {
        if ($field === "") {
            throw new \InvalidArgumentException(
                "Aggregation fields must not be empty",
            );
        }
    }

    /**
     * @return array{string, mixed}
     */
    private static function normalizeCondition(
        mixed $operator,
        mixed $value,
        int $argumentCount,
        int $requiredArgumentCount,
    ): array {
        if ($argumentCount === $requiredArgumentCount) {
            return [Operator::EQUAL, true];
        }
        if ($argumentCount === $requiredArgumentCount + 1) {
            return [Operator::EQUAL, $operator];
        }
        if (!is_string($operator) || !in_array($operator, Operator::values(), true)) {
            throw new \InvalidArgumentException("Unsupported query operator");
        }
        if ($operator === Operator::IN && !is_array($value)) {
            throw new \InvalidArgumentException(
                "The IN operator expects an array of values",
            );
        }

        return [$operator, $value];
    }
}
