<?php

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
        $this->data[] = $data;
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
