<?php

declare(strict_types=1);

namespace HiFolks\DataType;

use Generator;
use HiFolks\DataType\Enums\CsvRowWidth;

final class CsvReader
{
    /**
     * @param null|callable(Block, int): (array<int|string, mixed>|Block) $normalize
     * @param list<string> $requiredHeaders
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function rows(
        string $filename,
        string $delimiter = ",",
        string $enclosure = '"',
        string $escape = "",
        string $encoding = "UTF-8",
        bool $header = true,
        bool $skipEmptyRows = true,
        CsvRowWidth $rowWidth = CsvRowWidth::STRICT,
        ?callable $normalize = null,
        array $requiredHeaders = [],
    ): Generator {
        self::validateCharacters($delimiter, $enclosure, $escape);
        self::validateRequiredHeaderConfiguration($requiredHeaders, $header);

        $handle = @fopen($filename, "rb");
        if ($handle === false) {
            throw new \RuntimeException("Unable to open CSV file: " . $filename);
        }

        try {
            $headers = null;
            $recordNumber = 0;
            $resultIndex = 0;

            while (($record = fgetcsv($handle, null, $delimiter, $enclosure, $escape)) !== false) {
                $recordNumber++;

                if ($skipEmptyRows && self::isEmptyRecord($record)) {
                    continue;
                }

                $record = self::convertRecordEncoding($record, $encoding, $recordNumber);

                if ($header && $headers === null) {
                    $headers = self::validateHeaders($record);
                    self::validateRequiredHeaders($headers, $requiredHeaders);
                    continue;
                }

                $row = $headers === null
                    ? $record
                    : self::combineRow($headers, $record, $rowWidth, $recordNumber);
                if ($row === null) {
                    continue;
                }

                if ($normalize !== null) {
                    $normalized = $normalize(Block::make($row), $recordNumber);
                    if ($normalized instanceof Block) {
                        $row = $normalized->toArray();
                    } elseif (is_array($normalized)) {
                        $row = $normalized;
                    } else {
                        throw new \UnexpectedValueException(
                            "The CSV normalization callback must return an array or Block",
                        );
                    }
                }

                yield $resultIndex++ => $row;
            }

            if ($header && $headers === null && $requiredHeaders !== []) {
                self::throwMissingRequiredHeaders($requiredHeaders);
            }
        } finally {
            fclose($handle);
        }
    }

    private static function validateCharacters(
        string $delimiter,
        string $enclosure,
        string $escape,
    ): void {
        if (strlen($delimiter) !== 1) {
            throw new \InvalidArgumentException(
                "The CSV delimiter must be one byte",
            );
        }
        if (strlen($enclosure) !== 1) {
            throw new \InvalidArgumentException(
                "The CSV enclosure must be one byte",
            );
        }
        if ($escape !== "" && strlen($escape) !== 1) {
            throw new \InvalidArgumentException(
                "The CSV escape must be empty or one byte",
            );
        }
    }

    /**
     * @param array<int, string|null> $record
     */
    private static function isEmptyRecord(array $record): bool
    {
        return count($record) === 1 && $record[0] === null;
    }

    /**
     * @param array<int, string|null> $record
     * @return array<int, string|null>
     */
    private static function convertRecordEncoding(
        array $record,
        string $encoding,
        int $recordNumber,
    ): array {
        if (strcasecmp($encoding, "UTF-8") === 0) {
            return $record;
        }
        if (!function_exists("iconv")) {
            throw new \RuntimeException(
                "CSV encoding conversion requires the iconv extension",
            );
        }

        foreach ($record as $index => $value) {
            if ($value === null) {
                continue;
            }

            $converted = iconv($encoding, "UTF-8", $value);
            if ($converted === false) {
                throw new \UnexpectedValueException(
                    "Unable to convert CSV record {$recordNumber} from {$encoding} to UTF-8",
                );
            }
            $record[$index] = $converted;
        }

        return $record;
    }

    /**
     * @param array<int, string|null> $record
     * @return list<string>
     */
    private static function validateHeaders(array $record): array
    {
        $headers = array_map(
            static fn(?string $header): string => $header ?? "",
            $record,
        );
        if (isset($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', "", $headers[0]) ?? $headers[0];
        }

        if (in_array("", $headers, true)) {
            throw new \UnexpectedValueException(
                "CSV headers must not be empty",
            );
        }
        if (count(array_unique($headers)) !== count($headers)) {
            throw new \UnexpectedValueException(
                "CSV headers must be unique",
            );
        }

        return array_values($headers);
    }

    /** @param list<string> $requiredHeaders */
    private static function validateRequiredHeaderConfiguration(
        array $requiredHeaders,
        bool $header,
    ): void {
        if ($requiredHeaders !== [] && !$header) {
            throw new \InvalidArgumentException(
                "Required CSV headers cannot be used when header is false",
            );
        }
        if (in_array("", $requiredHeaders, true)) {
            throw new \InvalidArgumentException(
                "Required CSV headers must not be empty",
            );
        }
        if (count(array_unique($requiredHeaders)) !== count($requiredHeaders)) {
            throw new \InvalidArgumentException(
                "Required CSV headers must be unique",
            );
        }
    }

    /**
     * @param list<string> $headers
     * @param list<string> $requiredHeaders
     */
    private static function validateRequiredHeaders(
        array $headers,
        array $requiredHeaders,
    ): void {
        $missing = array_values(array_diff($requiredHeaders, $headers));

        if ($missing !== []) {
            self::throwMissingRequiredHeaders($missing);
        }
    }

    /** @param list<string> $headers */
    private static function throwMissingRequiredHeaders(array $headers): never
    {
        throw new \UnexpectedValueException(
            "CSV is missing required headers: " . implode(", ", $headers),
        );
    }

    /**
     * @param list<string> $headers
     * @param array<int, string|null> $record
     * @return array<string, string|null>|null
     */
    private static function combineRow(
        array $headers,
        array $record,
        CsvRowWidth $rowWidth,
        int $recordNumber,
    ): ?array {
        $headerCount = count($headers);
        $recordCount = count($record);

        if ($recordCount !== $headerCount) {
            if ($rowWidth === CsvRowWidth::SKIP) {
                return null;
            }
            if ($rowWidth === CsvRowWidth::PAD && $recordCount < $headerCount) {
                $record = array_pad($record, $headerCount, null);
            } else {
                throw new \UnexpectedValueException(
                    "CSV record {$recordNumber} has {$recordCount} fields; expected {$headerCount}",
                );
            }
        }

        return array_combine($headers, $record);
    }
}
