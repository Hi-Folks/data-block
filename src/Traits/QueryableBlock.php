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

        return self::make($returnData, $this->iteratorReturnsBlock);
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

            $table->append($newRow);
        }

        return $table;
    }

}
