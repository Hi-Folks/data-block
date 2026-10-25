<?php

declare(strict_types=1);

namespace HiFolks\DataType\Traits;

use HiFolks\DataType\Block;
use HiFolks\DataType\Enums\Operator;

trait AggregatableBlock
{
    /**
     * @param callable(mixed, mixed, int|string): mixed $callback
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        $carry = $initial;

        foreach ($this as $key => $value) {
            $carry = $callback($carry, $value, $key);
        }

        return $carry;
    }

    public function sum(int|string|null $field = null): int|float
    {
        return array_sum($this->numericValues($field));
    }

    public function average(int|string|null $field = null): ?float
    {
        $values = $this->numericValues($field);

        if ($values === []) {
            return null;
        }

        return array_sum($values) / count($values);
    }

    public function min(int|string|null $field = null): int|float|null
    {
        $values = $this->numericValues($field);

        return $values === [] ? null : min($values);
    }

    public function max(int|string|null $field = null): int|float|null
    {
        $values = $this->numericValues($field);

        return $values === [] ? null : max($values);
    }

    /**
     * Count rows matching the same field/operator/value conditions as where().
     */
    public function countWhere(
        int|string $field,
        mixed $operator = Operator::EQUAL,
        mixed $value = null,
    ): int {
        $matching = match (func_num_args()) {
            1 => $this->where($field),
            2 => $this->where($field, $operator),
            default => $this->where($field, $operator, $value),
        };

        return $matching->count();
    }

    /**
     * Sum a numeric field for rows matching the same conditions as where().
     */
    public function sumWhere(
        int|string $valueField,
        int|string $conditionField,
        mixed $operator = Operator::EQUAL,
        mixed $value = null,
    ): int|float {
        $matching = match (func_num_args()) {
            2 => $this->where($conditionField),
            3 => $this->where($conditionField, $operator),
            default => $this->where($conditionField, $operator, $value),
        };

        return $matching->sum($valueField);
    }

    public function countBy(
        int|string $groupField,
        int|string|null $defaultGroup = null,
    ): self {
        return $this->groupBy($groupField, $defaultGroup)->map(
            fn(Block $group): int => $group->count(),
        );
    }

    public function sumBy(
        int|string $groupField,
        int|string $valueField,
        int|string|null $defaultGroup = null,
    ): self {
        return $this->groupBy($groupField, $defaultGroup)->map(
            fn(Block $group): int|float => $group->sum($valueField),
        );
    }

    public function averageBy(
        int|string $groupField,
        int|string $valueField,
        int|string|null $defaultGroup = null,
    ): self {
        return $this->groupBy($groupField, $defaultGroup)->map(
            fn(Block $group): ?float => $group->average($valueField),
        );
    }

    public function minBy(
        int|string $groupField,
        int|string $valueField,
        int|string|null $defaultGroup = null,
    ): self {
        return $this->groupBy($groupField, $defaultGroup)->map(
            fn(Block $group): int|float|null => $group->min($valueField),
        );
    }

    public function maxBy(
        int|string $groupField,
        int|string $valueField,
        int|string|null $defaultGroup = null,
    ): self {
        return $this->groupBy($groupField, $defaultGroup)->map(
            fn(Block $group): int|float|null => $group->max($valueField),
        );
    }

    /**
     * @return list<int|float>
     */
    private function numericValues(int|string|null $field): array
    {
        $values = [];

        foreach ($this->data as $item) {
            $value = $field === null ? $item : $this->valueForField($item, $field);

            if (is_int($value) || is_float($value)) {
                $values[] = $value;
            } elseif (is_string($value) && is_numeric($value)) {
                $values[] = $value + 0;
            }
        }

        return $values;
    }

    private function valueForField(mixed $item, int|string $field): mixed
    {
        if ($item instanceof Block) {
            return $item->get($field);
        }

        if (is_array($item)) {
            return Block::make($item)->get($field);
        }

        return null;
    }
}
