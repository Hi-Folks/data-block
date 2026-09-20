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
     * @param callable(mixed, int|string): mixed $callback
     */
    public function forEach(callable $callback): self
    {
        foreach ($this as $key => $item) {
            $callback($item, $key);
        }

        return $this;
    }

    /**
     * @param callable(mixed, int|string): mixed $callback
     */
    public function map(callable $callback): self
    {
        $result = [];

        foreach ($this as $key => $item) {
            $result[$key] = $callback($item, $key);
        }

        return self::make($result, $this->iteratorReturnsBlock);
    }

    /**
     * @param callable(mixed, int|string): mixed $callback
     */
    public function filter(callable $callback): self
    {
        $result = [];

        foreach ($this as $key => $item) {
            $keep = $callback($item, $key);
            if (!is_bool($keep)) {
                throw new \UnexpectedValueException(
                    "The filter callback must return a boolean value",
                );
            }

            if ($keep) {
                $result[$key] = $item instanceof self ? $item->toArray() : $item;
            }
        }

        return self::make($result, $this->iteratorReturnsBlock);
    }

    /**
     * Split the items into matching and non-matching Blocks in one pass.
     *
     * @param callable(mixed, int|string): mixed $callback
     * @return array{0: self, 1: self}
     */
    public function partition(callable $callback): array
    {
        $matching = [];
        $nonMatching = [];

        foreach ($this as $key => $item) {
            $matches = $callback($item, $key);
            if (!is_bool($matches)) {
                throw new \UnexpectedValueException(
                    "The partition callback must return a boolean value",
                );
            }

            $value = $item instanceof self ? $item->toArray() : $item;
            if ($matches) {
                $matching[$key] = $value;
            } else {
                $nonMatching[$key] = $value;
            }
        }

        return [
            self::make($matching, $this->iteratorReturnsBlock),
            self::make($nonMatching, $this->iteratorReturnsBlock),
        ];
    }
}
