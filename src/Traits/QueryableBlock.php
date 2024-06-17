<?php

namespace HiFolks\DataType\Traits;

use HiFolks\DataType\Block;

trait QueryableBlock
{
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
                $elementToCheck = Block::make($element);
            }
            if (! $elementToCheck instanceof Block) {
                return Block::make();
            }
            $found = match ($operator) {
                '==' => ($elementToCheck->get($field) == $value),
                '>' => $elementToCheck->get($field) > $value,
                '<' => $elementToCheck->get($field) < $value,
                '>=' => $elementToCheck->get($field) >= $value,
                '<=' => $elementToCheck->get($field) <= $value,
                '!=' => $elementToCheck->get($field) != $value,
                '!==' => $elementToCheck->get($field) !== $value,
                default => $elementToCheck->get($field) === $value,
            };
            if ($found) {
                if ($preseveKeys) {
                    $returnData[$key] = $element;
                } else {
                    $returnData[] = $element;
                }

            }
        }

        return self::make($returnData);
    }

    public function orderBy(string|int $field, string $order = 'asc'): self
    {
        $array = $this->data;

        if ($order !== 'asc') {
            /** @var callable $closure */
            $closure = static fn($item1, $item2): int => $item2[$field] <=> $item1[$field];
        } else {
            $closure = static fn($item1, $item2): int => $item1[$field] <=> $item2[$field];
        }

        usort($array, $closure);
        return self::make($array);
    }

    public function select(int|string ...$columns): self
    {
        $table = self::make();

        foreach ($this->data as $row) {
            if (is_array($row)) {
                /** @var Block $row */
                $row = self::make($row);
            }
            /** @var Block $row */

            $newRow = [];
            foreach ($columns as $column) {
                $value = $row->get($column);
                $newRow[$column] = $value;
            }

            $table->append($newRow);
        }

        return $table;
    }

}
