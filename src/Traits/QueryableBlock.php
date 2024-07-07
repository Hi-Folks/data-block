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
            $operator = '===';
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
                'like' => self::like($elementToCheck->get($field), $value),
                'in' => in_array($value, $elementToCheck->get($field)),
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

}
