<?php

declare(strict_types=1);

namespace HiFolks\DataType\Enums;

enum AggregationType: string
{
    case COUNT = "count";
    case COUNT_WHERE = "count_where";
    case SUM = "sum";
    case SUM_WHERE = "sum_where";
    case MIN = "min";
    case MAX = "max";
    case AVERAGE = "average";
}
