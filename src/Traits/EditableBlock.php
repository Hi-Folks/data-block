<?php

declare(strict_types=1);

namespace HiFolks\DataType\Traits;

use HiFolks\DataType\Block;

trait EditableBlock
{
    /**
     * @param array<int|string, mixed>|Block $data
     * @return $this
     */
    public function append(array|Block $data, string|null $key = null): self
    {
        if ($data instanceof Block) {
            $this->data = array_merge($this->data, $data->toArray());
        } else {
            $this->data = array_merge($this->data, $data);
        }

        return $this;
    }

    public function appendItem(mixed $data, string|null $key = null): self
    {
        if (is_null($key)) {
            $this->data[] =  $data;
        } else {
            $this->data[$key] = $data;
        }


        return $this;
    }

    /**
     * @param array<int|string, mixed>|Block $value
     */
    private static function forceBlock(array|Block $value): Block
    {
        if (!$value instanceof Block) {
            return Block::make($value);
        }
        return $value;
    }
}
