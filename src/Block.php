<?php

declare(strict_types=1);

namespace HiFolks\DataType;

use ArrayAccess;
use Countable;
use Iterator;

/**
 * Class Block
 * @package HiFolks\DataType
 *
 * @implements Iterator<int|string, mixed>
 * @implements ArrayAccess<int|string, mixed>
 */
final class Block implements Iterator, ArrayAccess, Countable
{
    /** @var array<int|string, mixed> */
    private array $data;

    /** @param array<int|string, mixed> $data */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function current(): mixed
    {
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

    /** @param array<int|string, mixed> $data */
    public static function make(array $data = []): self
    {
        return new self($data);
    }

    public function count(): int
    {
        return count($this->data);
    }

    /**
     * Get the array
     * @return array<int|string, mixed>
     */
    public function array(): array
    {
        return $this->data;
    }

    public static function fromJsonString(string $jsonString = "[]"): self
    {
        /** @var array<int|string, mixed> $json */
        $json = json_decode($jsonString, associative: true);
        return self::make($json);
    }

    public static function fromJsonFile(string $jsonFile): self
    {
        if (file_exists($jsonFile)) {
            $content = file_get_contents($jsonFile);
            if ($content === false) {
                return self::make([]);
            }
            return self::fromJsonString($content);
        }
        return self::make([]);
    }



    /** @param array<int|string, mixed> $encodedJson */
    public static function fromEncodedJson(mixed $encodedJson): self
    {
        return new self($encodedJson);
    }
    /**
     * Get the element with $key
     *
     * @param non-empty-string $charNestedKey
     */
    public function get(mixed $key, mixed $defaultValue = null, string $charNestedKey = "."): mixed
    {
        if (is_string($key)) {
            $keyString = strval($key);
            if (str_contains($keyString, $charNestedKey)) {
                $nestedValue = $this->data;
                foreach (explode($charNestedKey, $keyString) as $nestedKey) {
                    if (is_array($nestedValue) && array_key_exists($nestedKey, $nestedValue)) {
                        $nestedValue = $nestedValue[$nestedKey];
                    } else {
                        return $defaultValue;
                    }
                }
                return $nestedValue;
            }
        }
        return $this->data[$key] ?? $defaultValue;
    }

    public function json(): string|bool
    {
        return json_encode($this->data);
    }
    public function jsonObject(): mixed
    {
        $jsonString = json_encode($this->data);
        if ($jsonString === false) {
            return false;
        }

        return json_decode($jsonString, associative:false);
    }


    /**
     * Get the element with $key as Block object
     * This is helpful when the element is an array, and you
     * need to get the Block object instead of the classic array
     * In the case the $key doesn't exist, an empty Block can be returned
     * @param non-empty-string $charNestedKey
     */
    public function getBlock(mixed $key, mixed $defaultValue = null, string $charNestedKey = "."): self
    {
        $value = $this->getBlockNullable($key, $defaultValue, $charNestedKey);
        if (is_null($value)) {
            return Block::make([]);
        }
        return $value;
    }

    /**
     * Get the element with $key as Block object
     * This is helpful when the element is an array, and you
     * need to get the Block object instead of the classic array
     * In the case the $key doesn't exist, null can be returned
     * @param non-empty-string $charNestedKey
     */
    public function getBlockNullable(mixed $key, mixed $defaultValue = null, string $charNestedKey = "."): self|null
    {
        $value = $this->get($key, $defaultValue, $charNestedKey);
        if (is_null($value)) {
            return null;
        }
        if (is_scalar($value)) {
            return self::make([$value]);
        }
        if (is_array($value)) {
            return self::make($value);
        }
        if ($value instanceof Block) {
            return $value;
        }
        return Block::make([]);
    }


    /**
     * Set a value to a specific $key
     * You can use the dot notation for setting a nested value.
     * @param non-empty-string $charNestedKey
     */
    public function set(int|string $key, mixed $value, string $charNestedKey = "."): void
    {
        if (is_string($key)) {
            $array = &$this->data;
            $keys = explode($charNestedKey, $key);
            foreach ($keys as $i => $key) {
                if (count($keys) === 1) {
                    break;
                }
                unset($keys[$i]);

                if (!isset($array[$key]) || !is_array($array[$key])) {
                    $array[$key] = [];
                }

                $array = &$array[$key];
            }

            $array[array_shift($keys)] = $value;
            return;
        }
        $this->data[$key] = $value;
    }



    /**
     * @return Block object that contains the key/value pairs for each index in the array
     */
    public function entries(): self
    {
        $pairs = [];
        foreach ($this->data as $k => $v) {
            $pairs[] = [$k, $v];
        }

        return self::make($pairs);
    }

    /**
     * Returns a new array [] or a new Blcok object that contains the keys
     * for each index in the Block object
     * It returns Block or [] depending on $returnArrClass value
     *
     * @param bool $returnBlockClass true if you need Block object
     * @return int|string|array<int|string, mixed>|Block
     */
    public function keys(bool $returnBlockClass = false): int|string|array|Block
    {
        if ($returnBlockClass) {
            return self::make(array_keys($this->data));
        }

        return array_keys($this->data);
    }



    public function where(string|int $field, mixed $operator = null, mixed $value = null): self
    {
        if (func_num_args() === 1) {
            $value = true;
            $operator = '==';
        }
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '===';
        }

        $function = match ($operator) {
            '==' => fn($element): bool => $element[$field] == $value,
            '>' => fn($element): bool => $element->get($field) > $value,
            '<' => fn($element): bool => $element->get($field) < $value,
            '>=' => fn($element): bool => $element->get($field) >= $value,
            '<=' => fn($element): bool => $element->get($field) <= $value,
            '!=' => fn($element): bool => $element->get($field) != $value,
            '!==' => fn($element): bool => $element->get($field) !== $value,
            default => fn($element): bool => $element->get($field) === $value,
        };
        $filteredArray = array_filter($this->data, $function);
        return self::make($filteredArray);
    }

    public function orderBy(string|int $field, string $order = 'desc'): self
    {
        $array = $this->data;

        if ($order !== 'asc') {
            /** @var callable $closure */
            $closure = static fn(Block $item1, Block $item2): int => $item2->get($field) <=> $item1->get($field);
        } else {
            $closure = static fn($item1, $item2): int => $item1->get($field) <=> $item2->get($field);
        }

        usort($array, $closure);
        return self::make($array);
    }

}
