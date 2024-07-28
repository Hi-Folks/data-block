<?php

declare(strict_types=1);

namespace HiFolks\DataType\Traits;

trait IteratableBlock
{
    public function current(): mixed
    {
        if ($this->iteratorReturnsBlock) {
            $current = current($this->data);
            if (is_array($current)) {
                return self::make($current);
            }
            return $current;
        }
        return current($this->data);
    }

    public function next(): void
    {
        next($this->data);
    }

    /**
     * Return the key of the current element
     *
     * @link https://php.net/manual/en/iterator.key.php
     *
     * @return string|int|null scalar on success, or null on failure.
     */
    public function key(): string|int|null
    {
        return key($this->data);
    }

    public function valid(): bool
    {
        return !is_null($this->key());
    }

    public function rewind(): void
    {
        reset($this->data);
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }

    /**
     * It executes a provided function ($callback) once for each element.
     * @param callable $callback the function to call for each element
     */
    public function forEach(callable $callback): self
    {
        $result = [];
        foreach ($this as $key => $item) {
            $result[$key] = $callback($item);
        }
        return self::make($result);
    }
}
