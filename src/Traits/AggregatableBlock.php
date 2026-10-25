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

    /**
     * Build a flat report containing named metrics for every group.
     *
     * Each metric receives an independent snapshot of the grouped rows followed
     * by the group value. Groups retain the deterministic order from groupBy().
     *
     * @param array<int|string, callable(Block, int|string): mixed> $metrics
     */
    public function summarizeBy(
        int|string $groupField,
        array $metrics,
        int|string|null $defaultGroup = null,
        int|string|null $groupAs = null,
    ): self {
        self::validateSummaryMetrics($metrics);

        $groupColumn = $groupAs ?? $groupField;
        if (array_key_exists($groupColumn, $metrics)) {
            throw new \InvalidArgumentException(
                "Summary group column '{$groupColumn}' conflicts with a metric name",
            );
        }

        $summary = [];

        foreach ($this->groupBy($groupField, $defaultGroup) as $groupValue => $group) {
            if (!$group instanceof Block) {
                continue;
            }

            $groupData = $group->toArray();
            $row = [$groupColumn => $groupValue];

            foreach ($metrics as $name => $metric) {
                $metricGroup = Block::make(
                    $groupData,
                    $this->iteratorReturnsBlock,
                );
                $row[$name] = $metric($metricGroup, $groupValue);
            }

            $summary[] = $row;
        }

        return self::make($summary, $this->iteratorReturnsBlock);
    }

    /**
     * Build a flat report grouped by a collision-free combination of fields.
     *
     * @param list<int|string> $groupFields
     * @param array<int|string, callable(Block, array<int|string, mixed>): mixed> $metrics
     * @param array<int|string, mixed> $defaultGroups Defaults keyed by grouping field.
     */
    public function summarizeByMany(
        array $groupFields,
        array $metrics,
        array $defaultGroups = [],
    ): self {
        self::validateSummaryGroupFields($groupFields);
        self::validateSummaryMetrics($metrics);
        self::validateSummaryDefaults($groupFields, $defaultGroups);
        self::validateSummaryColumnNames($groupFields, $metrics);

        /**
         * @var array<string, array{
         *     values: array<int|string, mixed>,
         *     rows: array<int, array<int|string, mixed>>
         * }> $groups
         */
        $groups = [];

        foreach ($this->data as $item) {
            if ($item instanceof Block) {
                $rowData = $item->toArray();
            } elseif (is_array($item)) {
                $rowData = $item;
            } else {
                continue;
            }

            $row = Block::make($rowData, $this->iteratorReturnsBlock);
            $groupValues = self::summaryGroupValues(
                $row,
                $groupFields,
                $defaultGroups,
            );
            if ($groupValues === null) {
                continue;
            }

            $identity = serialize($groupValues);
            if (!array_key_exists($identity, $groups)) {
                $groups[$identity] = [
                    "values" => $groupValues,
                    "rows" => [],
                ];
            }
            $groups[$identity]["rows"][] = $rowData;
        }

        $summary = [];

        foreach ($groups as $group) {
            $summaryRow = $group["values"];

            foreach ($metrics as $name => $metric) {
                $metricGroup = Block::make(
                    $group["rows"],
                    $this->iteratorReturnsBlock,
                );
                $summaryRow[$name] = $metric(
                    $metricGroup,
                    $group["values"],
                );
            }

            $summary[] = $summaryRow;
        }

        return self::make($summary, $this->iteratorReturnsBlock);
    }

    /**
     * Count rows for every collision-free combination of grouping fields.
     *
     * @param list<int|string> $groupFields
     * @param array<int|string, mixed> $defaultGroups Defaults keyed by grouping field.
     */
    public function countByMany(
        array $groupFields,
        array $defaultGroups = [],
        int|string $countAs = "count",
    ): self {
        return $this->summarizeByMany(
            $groupFields,
            [$countAs => fn(Block $group): int => $group->count()],
            $defaultGroups,
        );
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

    /** @param array<int|string, mixed> $metrics */
    private static function validateSummaryMetrics(array $metrics): void
    {
        foreach ($metrics as $name => $metric) {
            if (!is_callable($metric)) {
                throw new \InvalidArgumentException(
                    "Summary metric '{$name}' must be defined by a callable",
                );
            }
        }
    }

    /** @param array<int|string, mixed> $groupFields */
    private static function validateSummaryGroupFields(array $groupFields): void
    {
        if (!array_is_list($groupFields) || count($groupFields) < 2) {
            throw new \InvalidArgumentException(
                "Summary grouping fields must be a list containing at least two fields",
            );
        }

        $seen = [];
        foreach ($groupFields as $field) {
            if ((!is_int($field) && !is_string($field)) || $field === "") {
                throw new \InvalidArgumentException(
                    "Summary grouping fields must be non-empty strings or integers",
                );
            }
            if (array_key_exists($field, $seen)) {
                throw new \InvalidArgumentException(
                    "Summary grouping fields must be unique",
                );
            }

            $seen[$field] = true;
        }
    }

    /**
     * @param list<int|string> $groupFields
     * @param array<int|string, mixed> $defaultGroups
     */
    private static function validateSummaryDefaults(
        array $groupFields,
        array $defaultGroups,
    ): void {
        foreach ($defaultGroups as $field => $default) {
            if (!in_array($field, $groupFields, true)) {
                throw new \InvalidArgumentException(
                    "Default group field '{$field}' is not a configured grouping field",
                );
            }
            if ($default !== null && !is_scalar($default)) {
                throw new \InvalidArgumentException(
                    "Default group for '{$field}' must be scalar or null",
                );
            }
        }
    }

    /**
     * @param list<int|string> $groupFields
     * @param array<int|string, mixed> $metrics
     */
    private static function validateSummaryColumnNames(
        array $groupFields,
        array $metrics,
    ): void {
        foreach ($groupFields as $field) {
            if (array_key_exists($field, $metrics)) {
                throw new \InvalidArgumentException(
                    "Summary group column '{$field}' conflicts with a metric name",
                );
            }
        }
    }

    /**
     * @param list<int|string> $groupFields
     * @param array<int|string, mixed> $defaultGroups
     * @return array<int|string, mixed>|null
     */
    private static function summaryGroupValues(
        Block $row,
        array $groupFields,
        array $defaultGroups,
    ): ?array {
        $values = [];
        $missing = new \stdClass();

        foreach ($groupFields as $field) {
            $value = $row->get($field, $missing);
            if (
                $value === $missing
                || $value === null
                || (!is_scalar($value))
            ) {
                if (!array_key_exists($field, $defaultGroups)) {
                    return null;
                }

                $value = $defaultGroups[$field];
            }

            $values[$field] = $value;
        }

        return $values;
    }
}
