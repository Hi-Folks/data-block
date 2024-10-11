<?php

declare(strict_types=1);

namespace HiFolks\DataType\Traits;

use HiFolks\DataType\Block;

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
        mixed $operator = null,
        mixed $value = null,
        bool $preseveKeys = true,
    ): self {

        if (func_num_args() === 1) {
            $value = true;
            $operator = '==';
        }
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '==';
        }

        $returnData = [];

        foreach ($this as $key => $element) {
            $elementToCheck = $element;
            if (is_array($element)) {
                $elementToCheck = Block::make($element, $this->iteratorReturnsBlock);
            }
            if (! $elementToCheck instanceof Block) {
                return Block::make([], $this->iteratorReturnsBlock);
            }
            $found = match ($operator) {
                '==' => ($elementToCheck->get($field) == $value),
                '>' => $elementToCheck->get($field) > $value,
                '<' => $elementToCheck->get($field) < $value,
                '>=' => $elementToCheck->get($field) >= $value,
                '<=' => $elementToCheck->get($field) <= $value,
                '!=' => $elementToCheck->get($field) != $value,
                '!==' => $elementToCheck->get($field) !== $value,
                'in' => in_array($value, $elementToCheck->get($field)),
                'has' => in_array($elementToCheck->get($field), $value),
                default => $elementToCheck->get($field) === $value,
            };
            if ($found) {

                if ($preseveKeys) {
                    $returnData[$key] = $element instanceof Block ? $element->toArray() : $element;
                } else {
                    $returnData[] = $element instanceof Block ? $element->toArray() : $element;
                    ;
                }

            }
        }
        return self::make($returnData, $this->iteratorReturnsBlock);
    }


    public function orderBy(string|int $field, string $order = 'asc'): self
    {
        $map = [];
        $array = $this->data;

        foreach ($this as $key => $item) {
            $map[$key] = $item->get($field);
        }
        if ($order === 'desc') {
            array_multisort(
                $map,
                SORT_DESC,
                $array,
            );

        } else {
            array_multisort($map, $array) ;
        }

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
     * @return self A new Block instance with the grouped elements.
     *
     */
    public function groupBy(string|int $field): self
    {
        $result = [];

        foreach ($this as $value) {
            $property = $value->get($field);
            $property = self::castVariableForStrval($property);
            if (!$property) {
                continue;
            }
            if (! array_key_exists(strval($property), $result)) {
                $result[$property] = [];
            }
            $result[$property][] = $value->toArray();
        }

        return self::make($result);
    }

    private static function castVariableForStrval(mixed $property): bool|float|int|string|null
    {
        return match (gettype($property)) {
            'boolean' => $property,
            'double' => $property,
            'integer' => $property,
            'string' => $property,
            default => null,
        };
    }

}
