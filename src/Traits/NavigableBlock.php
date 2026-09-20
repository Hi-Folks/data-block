<?php

declare(strict_types=1);

namespace HiFolks\DataType\Traits;

trait NavigableBlock
{
    public function take(int $limit): self
    {
        $offset = $limit < 0 ? $limit : 0;
        $length = $limit < 0 ? null : $limit;

        return $this->slice($offset, $length);
    }

    public function skip(int $count): self
    {
        if ($count < 0) {
            throw new \InvalidArgumentException(
                "The number of items to skip must be zero or greater",
            );
        }

        return $this->slice($count);
    }

    public function slice(int $offset, ?int $length = null): self
    {
        return self::make(
            array_slice($this->data, $offset, $length, true),
            $this->iteratorReturnsBlock,
        );
    }

    public function first(): mixed
    {
        $key = array_key_first($this->data);
        if ($key === null) {
            throw new \UnderflowException("Cannot get the first item of an empty Block");
        }

        return $this->navigationItem($this->data[$key]);
    }

    public function firstOrNull(): mixed
    {
        $key = array_key_first($this->data);

        return $key === null ? null : $this->navigationItem($this->data[$key]);
    }

    public function last(): mixed
    {
        $key = array_key_last($this->data);
        if ($key === null) {
            throw new \UnderflowException("Cannot get the last item of an empty Block");
        }

        return $this->navigationItem($this->data[$key]);
    }

    public function lastOrNull(): mixed
    {
        $key = array_key_last($this->data);

        return $key === null ? null : $this->navigationItem($this->data[$key]);
    }

    private function navigationItem(mixed $item): mixed
    {
        if ($this->iteratorReturnsBlock && is_array($item)) {
            return self::make($item, $this->iteratorReturnsBlock);
        }

        return $item;
    }
}
