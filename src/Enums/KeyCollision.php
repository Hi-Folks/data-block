<?php

declare(strict_types=1);

namespace HiFolks\DataType\Enums;

enum KeyCollision: string
{
    case THROW = "throw";
    case KEEP_FIRST = "first";
    case KEEP_LAST = "last";
}
