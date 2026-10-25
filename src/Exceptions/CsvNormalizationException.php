<?php

declare(strict_types=1);

namespace HiFolks\DataType\Exceptions;

final class CsvNormalizationException extends \RuntimeException
{
    public function __construct(
        public readonly string $source,
        public readonly int $recordNumber,
        \Throwable $previous,
    ) {
        parent::__construct(
            "CSV normalization failed at record {$recordNumber} in '{$source}': "
            . $previous->getMessage(),
            previous: $previous,
        );
    }
}
