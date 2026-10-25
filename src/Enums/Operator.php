<?php

declare(strict_types=1);

namespace HiFolks\DataType\Enums;

class Operator
{
    public const EQUAL = '==';
    public const STRICT_EQUAL = '===';
    public const GREATER_THAN = '>';
    public const LESS_THAN = '<';
    public const GREATER_THAN_OR_EQUAL = '>=';
    public const LESS_THAN_OR_EQUAL = '<=';
    public const NOT_EQUAL = '!=';
    public const STRICT_NOT_EQUAL = '!==';
    public const IN = 'in';
    public const HAS = 'has';
    public const LIKE = 'like';

    /** @return list<string> */
    public static function values(): array
    {
        return [
            self::EQUAL,
            self::STRICT_EQUAL,
            self::GREATER_THAN,
            self::LESS_THAN,
            self::GREATER_THAN_OR_EQUAL,
            self::LESS_THAN_OR_EQUAL,
            self::NOT_EQUAL,
            self::STRICT_NOT_EQUAL,
            self::IN,
            self::HAS,
            self::LIKE,
        ];
    }
}
