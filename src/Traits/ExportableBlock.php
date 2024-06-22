<?php

namespace HiFolks\DataType\Traits;

trait ExportableBlock
{
    /**
    * Returns the native array
    * @return array<int|string, mixed>
        */
    public function toArray(): array
    {
        return $this->data;
    }


    /**
     * Returns the JSON String (pretty format by default)
     * @return string|false
     */
    public function toJson(): string|false
    {
        return json_encode($this->data, JSON_PRETTY_PRINT);
    }
}
