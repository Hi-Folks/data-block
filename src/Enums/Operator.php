<?php

declare(strict_types=1);

namespace HiFolks\DataType\Enums;

class Operator
{
    public const EQUAL = '==';
    public const GREATER_THAN = '>';
    public const LESS_THAN = '<';
    public const GREATER_THAN_OR_EQUAL = '>=';
    public const LESS_THAN_OR_EQUAL = '<=';
    public const NOT_EQUAL = '!=';
    public const STRICT_NOT_EQUAL = '!==';
    public const IN = 'in';
    public const HAS = 'has';
}
