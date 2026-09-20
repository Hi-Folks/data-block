<?php

declare(strict_types=1);

namespace HiFolks\DataType\Enums;

enum CsvRowWidth: string
{
    case STRICT = "strict";
    case PAD = "pad";
    case SKIP = "skip";
}
