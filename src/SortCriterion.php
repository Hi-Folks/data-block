<?php

declare(strict_types=1);

namespace HiFolks\DataType;

use HiFolks\DataType\Enums\SortDirection;

final readonly class SortCriterion
{
    private function __construct(
        public string|int $field,
        public SortDirection $direction,
    ) {}

    public static function asc(string|int $field): self
    {
        return new self($field, SortDirection::ASC);
    }

    public static function desc(string|int $field): self
    {
        return new self($field, SortDirection::DESC);
    }
}
