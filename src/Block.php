<?php

declare(strict_types=1);

namespace HiFolks\DataType;

use ArrayAccess;
use Countable;
use HiFolks\DataType\Traits\EditableBlock;
use HiFolks\DataType\Traits\QueryableBlock;
use HiFolks\DataType\Traits\ExportableBlock;
use HiFolks\DataType\Traits\FormattableBlock;
use HiFolks\DataType\Traits\IteratableBlock;
use HiFolks\DataType\Traits\LoadableBlock;
use HiFolks\DataType\Traits\ValidableBlock;
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
    use QueryableBlock;
    use EditableBlock;
    use ExportableBlock;
    use LoadableBlock;
    use IteratableBlock;
    use ValidableBlock;
    use FormattableBlock;


    /** @var array<int|string, mixed> */
    private array $data;

    private bool $iteratorReturnsBlock = true;

    /** @param array<int|string, mixed> $data */
    public function __construct(array $data = [], bool $iteratorReturnsBlock = true)
    {
        $this->data = $data;
        $this->iteratorReturnsBlock = $iteratorReturnsBlock;
    }

    public function iterateBlock(bool $returnsBlock = true): self
    {
        $this->iteratorReturnsBlock = $returnsBlock;
        return $this;
    }




    /** @param array<int|string, mixed> $data */
    public static function make(array $data = [], bool $iteratorReturnsBlock = true): self
    {
        return new self($data, $iteratorReturnsBlock);
    }



    public function count(): int
    {
        return count($this->data);
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
                    } elseif ($nestedValue instanceof Block) {
                        $nestedValue = $nestedValue->get($nestedKey);
                    } else {
                        return $defaultValue;
                    }
                }
                return $nestedValue;
            }
        }
        return $this->data[$key] ?? $defaultValue;
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
    public function set(int|string $key, mixed $value, string $charNestedKey = "."): self
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
            return $this;
        }
        $this->data[$key] = $value;
        return $this;
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
     * @return Block object that contains the key/value pairs for each index in the array
     */
    public function values(): self
    {
        $pairs = $this->data;
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

    public function has(mixed $value): bool
    {
        return in_array($value, $this->values()->toArray());
    }

    public function hasKey(string|int $key): bool
    {
        /** @var array<int, int|string> $keys */
        $keys = $this->keys();
        return in_array($key, $keys);
    }

    /**
     * Applies a callable function to a field and sets the result to a target field.
     *
     * @param string|int $key The key of the field to be processed.
     * @param string|int $targetKey The key where the result should be stored.
     * @param callable $callable The function to apply to the field value.
     *
     * @return self Returns the instance of the class for method chaining.
     */
    public function applyField(
        string|int $key,
        string|int $targetKey,
        callable $callable,
    ): self {
        $this->set($targetKey, $callable($this->get($key)));
        return $this;
    }





}
