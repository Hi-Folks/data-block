<?php

declare(strict_types=1);

namespace HiFolks\DataType\Traits;

use HiFolks\DataType\Block;
use HiFolks\DataType\Enums\Operator;
use HiFolks\DataType\Enums\SortDirection;
use HiFolks\DataType\SortCriterion;

trait QueryableBlock
{
    public static function like(mixed $value1, mixed $value2): bool
    {
        $strValue1 = strval($value1);
        $strValue2 = strval($value2);
        return str_contains($strValue1, $strValue2);
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function where(
        string|int $field,
        mixed $operator = Operator::EQUAL,
        mixed $value = null,
        bool $preserveKeys = true,
    ): self {
        if (func_num_args() === 1) {
            $value = true;
            $operator = Operator::EQUAL;
        }
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = Operator::EQUAL;
        }

        if (!is_string($operator) || !in_array($operator, self::queryOperators(), true)) {
            throw new \InvalidArgumentException("Unsupported query operator");
        }

        if ($operator === Operator::IN && !is_array($value)) {
            throw new \InvalidArgumentException(
                "The IN operator expects an array of values",
            );
        }

        return $this->wherePredicate(
            $field,
            fn(bool $exists, mixed $actual): bool => $exists
                && $this->matchesQuery($actual, $operator, $value),
            $preserveKeys,
        );
    }

    public function whereNull(
        int|string $field,
        bool $preserveKeys = true,
    ): self {
        return $this->wherePredicate(
            $field,
            fn(bool $exists, mixed $value): bool => !$exists || $value === null,
            $preserveKeys,
        );
    }

    public function whereNotNull(
        int|string $field,
        bool $preserveKeys = true,
    ): self {
        return $this->wherePredicate(
            $field,
            fn(bool $exists, mixed $value): bool => $exists && $value !== null,
            $preserveKeys,
        );
    }

    public function whereBetween(
        int|string $field,
        mixed $start,
        mixed $end,
        bool $preserveKeys = true,
    ): self {
        return $this->wherePredicate(
            $field,
            fn(bool $exists, mixed $value): bool => $exists
                && self::isRelationalValue($value)
                && self::isRelationalValue($start)
                && self::isRelationalValue($end)
                && $value >= $start
                && $value <= $end,
            $preserveKeys,
        );
    }

    /**
     * @param array<mixed> $values
     */
    public function whereIn(
        int|string $field,
        array $values,
        bool $strict = true,
        bool $preserveKeys = true,
    ): self {
        return $this->wherePredicate(
            $field,
            fn(bool $exists, mixed $value): bool => $exists
                && in_array($value, $values, $strict),
            $preserveKeys,
        );
    }

    public function orderBy(
        SortCriterion|string|int $field,
        SortDirection|string $order = SortDirection::ASC,
    ): self {
        if ($field instanceof SortCriterion) {
            if (func_num_args() > 1) {
                throw new \InvalidArgumentException(
                    "A SortCriterion already defines its sort direction",
                );
            }

            return $this->orderByMany([$field]);
        }

        $direction = self::normalizeSortDirection($order);

        return $this->orderByMany([
            $direction === SortDirection::ASC
                ? SortCriterion::asc($field)
                : SortCriterion::desc($field),
        ]);
    }

    /**
     * Sort by multiple fields, applying criteria from left to right.
     *
     * @param list<SortCriterion> $criteria
     */
    public function orderByMany(array $criteria): self
    {
        self::validateSortCriteria($criteria);

        $array = $this->data;
        if ($criteria === []) {
            return self::make($array, $this->iteratorReturnsBlock);
        }

        uasort(
            $array,
            function (mixed $left, mixed $right) use ($criteria): int {
                foreach ($criteria as $criterion) {
                    $leftValue = $this->sortFieldValue(
                        $left,
                        $criterion->field,
                    );
                    $rightValue = $this->sortFieldValue(
                        $right,
                        $criterion->field,
                    );
                    $leftIsSortable = is_scalar($leftValue);
                    $rightIsSortable = is_scalar($rightValue);

                    if (!$leftIsSortable && !$rightIsSortable) {
                        continue;
                    }
                    if (!$leftIsSortable) {
                        return 1;
                    }
                    if (!$rightIsSortable) {
                        return -1;
                    }

                    $comparison = $leftValue <=> $rightValue;
                    if ($comparison === 0) {
                        continue;
                    }
                    return $criterion->direction === SortDirection::DESC
                        ? -$comparison
                        : $comparison;
                }

                return 0;
            },
        );

        return self::make($array, $this->iteratorReturnsBlock);
    }

    /**
     * Sort with a custom comparator.
     *
     * The comparator receives items in the Block's configured iteration form.
     *
     * @param callable(mixed, mixed): mixed $comparator
     */
    public function sort(callable $comparator): self
    {
        $array = $this->data;

        uasort(
            $array,
            function (mixed $left, mixed $right) use ($comparator): int {
                $result = $comparator(
                    $this->sortableItem($left),
                    $this->sortableItem($right),
                );

                if (!is_int($result)) {
                    throw new \UnexpectedValueException(
                        "Sort comparator must return an integer",
                    );
                }

                return $result;
            },
        );

        return self::make($array, $this->iteratorReturnsBlock);
    }

    public function select(int|string ...$columns): self
    {
        $table = self::make([], $this->iteratorReturnsBlock);

        foreach ($this->data as $row) {
            if (is_array($row)) {
                /** @var Block $row */
                $row = self::make($row, $this->iteratorReturnsBlock);
            }
            $newRow = [];
            foreach ($columns as $column) {
                /** @var Block $row */
                $value = $row->get($column);
                $newRow[$column] = $value;
            }

            $table->appendItem($newRow);
        }

        return $table;
    }

    /**
     * Groups the elements of the Block by a specified field.
     *
     * This method takes a field name as an argument and groups the elements of the
     * Block object based on the values of that field. Each element is grouped into
     * an associative array where the keys are the values of the specified field
     * and the values are arrays of elements that share that key.
     *
     * @param string|int $field The field name to group by.
     * @param string|int|null $defaultGroup The group for missing, null, or unsupported values.
     * @return self A new Block instance with the grouped elements.
     *
     */
    public function groupBy(
        string|int $field,
        string|int|null $defaultGroup = null,
    ): self {
        $result = [];

        foreach ($this->data as $value) {
            if (is_array($value)) {
                $value = self::make($value, $this->iteratorReturnsBlock);
            }

            if (!$value instanceof Block) {
                continue;
            }

            $groupKey = self::castForArrayKey($value->get($field));
            $groupKey ??= $defaultGroup;
            if ($groupKey === null) {
                continue;
            }

            if (!array_key_exists($groupKey, $result)) {
                $result[$groupKey] = [];
            }
            $result[$groupKey][] = $value->toArray();
        }

        return self::make($result);
    }

    /**
     * @param callable(mixed, int|string): (int|string) $groupFunction
     */
    public function groupByFunction(callable $groupFunction): self
    {
        $result = [];

        foreach ($this as $key => $item) {
            $groupKey = self::castForArrayKey($groupFunction($item, $key));
            if ($groupKey === null) {
                continue;
            }

            if (!array_key_exists($groupKey, $result)) {
                $result[$groupKey] = [];
            }

            $result[$groupKey][] = $item instanceof Block
                ? $item->toArray()
                : $item;
        }

        return self::make($result, $this->iteratorReturnsBlock);
    }

    public function extractWhere(string $property, mixed $value): self
    {
        $results = [];

        $scan = function ($item) use (&$results, &$scan, $property, $value): void {
            if (is_array($item)) {
                // Match the property/value pair
                if (
                    array_key_exists($property, $item)
                    && $item[$property] === $value
                ) {
                    $results[] = $item;
                }

                // Scan deeper
                foreach ($item as $subItem) {
                    $scan($subItem);
                }
            }
        };

        $scan($this->data);

        return self::make($results);
    }

    private static function castVariableForStrval(
        mixed $property,
    ): bool|float|int|string|null {
        return match (gettype($property)) {
            "boolean" => $property,
            "double" => $property,
            "integer" => $property,
            "string" => $property,
            default => null,
        };
    }
    private static function castForArrayKey(
        mixed $property,
    ): int|string|null {
        return match (gettype($property)) {
            "boolean" => (int) $property,
            "double" => strval($property),
            "integer" => $property,
            "string" => $property,
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    private static function queryOperators(): array
    {
        return [
            Operator::EQUAL,
            Operator::STRICT_EQUAL,
            Operator::GREATER_THAN,
            Operator::LESS_THAN,
            Operator::GREATER_THAN_OR_EQUAL,
            Operator::LESS_THAN_OR_EQUAL,
            Operator::NOT_EQUAL,
            Operator::STRICT_NOT_EQUAL,
            Operator::IN,
            Operator::HAS,
            Operator::LIKE,
        ];
    }

    private function matchesQuery(
        mixed $actual,
        string $operator,
        mixed $expected,
    ): bool {
        return match ($operator) {
            Operator::EQUAL => $actual == $expected,
            Operator::STRICT_EQUAL => $actual === $expected,
            Operator::NOT_EQUAL => $actual != $expected,
            Operator::STRICT_NOT_EQUAL => $actual !== $expected,
            Operator::GREATER_THAN => self::isRelationalValue($actual)
                && self::isRelationalValue($expected)
                && $actual > $expected,
            Operator::LESS_THAN => self::isRelationalValue($actual)
                && self::isRelationalValue($expected)
                && $actual < $expected,
            Operator::GREATER_THAN_OR_EQUAL => self::isRelationalValue($actual)
                && self::isRelationalValue($expected)
                && $actual >= $expected,
            Operator::LESS_THAN_OR_EQUAL => self::isRelationalValue($actual)
                && self::isRelationalValue($expected)
                && $actual <= $expected,
            Operator::IN => is_array($expected)
                && in_array($actual, $expected, true),
            Operator::HAS => self::queryValueHas($actual, $expected),
            Operator::LIKE => self::queryValueContains($actual, $expected),
            default => throw new \InvalidArgumentException(
                "Unsupported query operator",
            ),
        };
    }

    private static function isRelationalValue(mixed $value): bool
    {
        return is_int($value)
            || is_float($value)
            || (is_string($value) && $value !== "");
    }

    private function sortFieldValue(mixed $item, int|string $field): mixed
    {
        if ($item instanceof Block) {
            $item = $item->toArray();
        }

        if (is_array($item)) {
            $item = Block::make($item, false);
        }

        if (!$item instanceof Block) {
            return null;
        }

        return $item->get($field);
    }

    private static function normalizeSortDirection(
        SortDirection|string $direction,
    ): SortDirection {
        if ($direction instanceof SortDirection) {
            return $direction;
        }

        $direction = SortDirection::tryFrom(strtolower($direction));

        return $direction ?? throw new \InvalidArgumentException(
            "Sort direction must be 'asc' or 'desc'",
        );
    }

    /** @param array<array-key, mixed> $criteria */
    private static function validateSortCriteria(array $criteria): void
    {
        if (!array_is_list($criteria)) {
            throw new \InvalidArgumentException(
                "Sort criteria must be provided as a list of SortCriterion objects",
            );
        }

        foreach ($criteria as $criterion) {
            if (!$criterion instanceof SortCriterion) {
                throw new \InvalidArgumentException(
                    "Sort criteria must be provided as a list of SortCriterion objects",
                );
            }
        }
    }

    private function sortableItem(mixed $item): mixed
    {
        if ($this->iteratorReturnsBlock && is_array($item)) {
            return Block::make($item, $this->iteratorReturnsBlock);
        }

        return $item;
    }

    private static function queryValueHas(mixed $actual, mixed $expected): bool
    {
        if ($actual instanceof Block) {
            $actual = $actual->toArray();
        }

        return is_array($actual) && in_array($expected, $actual, true);
    }

    private static function queryValueContains(mixed $actual, mixed $expected): bool
    {
        if (!is_scalar($actual) || !is_scalar($expected)) {
            return false;
        }

        return str_contains((string) $actual, (string) $expected);
    }

    /**
     * @param callable(bool, mixed): bool $predicate
     */
    private function wherePredicate(
        int|string $field,
        callable $predicate,
        bool $preserveKeys,
    ): self {
        $result = [];
        $missing = new \stdClass();

        foreach ($this->data as $key => $element) {
            $row = is_array($element)
                ? Block::make($element, $this->iteratorReturnsBlock)
                : $element;
            if (!$row instanceof Block) {
                continue;
            }

            $value = $row->get($field, $missing);
            $matches = $predicate($value !== $missing, $value);
            if (!$matches) {
                continue;
            }

            $item = $element instanceof Block ? $element->toArray() : $element;
            if ($preserveKeys) {
                $result[$key] = $item;
            } else {
                $result[] = $item;
            }
        }

        return self::make($result, $this->iteratorReturnsBlock);
    }
}
