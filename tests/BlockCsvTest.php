<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use HiFolks\DataType\Enums\CsvRowWidth;
use PHPUnit\Framework\TestCase;

final class BlockCsvTest extends TestCase
{
    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function testEagerLoadingUsesHeadersAndHandlesQuotedMultilineFields(): void
    {
        $file = $this->csvFile(
            "Name,Stage,Notes\n"
            . '"Acme, Inc.",Proposal,"First line' . "\n"
            . 'Second line"' . "\n\n"
            . "Globex,Negotiation,Simple\n",
        );

        $rows = Block::fromCsvFile($file);

        $this->assertCount(2, $rows);
        $this->assertSame("Acme, Inc.", $rows->get("0.Name"));
        $this->assertSame("First line\nSecond line", $rows->get("0.Notes"));
        $this->assertSame("Globex", $rows->get("1.Name"));
    }

    public function testStreamingYieldsOneBlockPerRow(): void
    {
        $file = $this->csvFile("Name,Amount\nAcme,10\nGlobex,20\n");
        $rows = Block::streamCsvFile($file);

        $this->assertInstanceOf(Generator::class, $rows);

        $names = [];
        foreach ($rows as $row) {
            $this->assertInstanceOf(Block::class, $row);
            $names[] = $row->getStringStrict("Name");
        }

        $this->assertSame(["Acme", "Globex"], $names);
    }

    public function testChunkingKeepsRowsInBoundedBlocks(): void
    {
        $file = $this->csvFile("Name,Amount\nA,10\nB,20\nC,30\nD,40\nE,50\n");
        $chunks = iterator_to_array(Block::chunkCsvFile($file, chunkSize: 2));

        $this->assertCount(3, $chunks);
        $this->assertCount(2, $chunks[0]);
        $this->assertCount(2, $chunks[1]);
        $this->assertCount(1, $chunks[2]);
        $this->assertSame(30, $chunks[0]->sum("Amount"));
        $this->assertSame("E", $chunks[2]->get("0.Name"));
    }

    public function testChunkingRejectsInvalidSizes(): void
    {
        $file = $this->csvFile("Name\nAcme\n");

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("greater than zero");

        Block::chunkCsvFile($file, chunkSize: 0)->current();
    }

    public function testCsvCanBeLoadedWithoutAHeader(): void
    {
        $file = $this->csvFile("Acme,10\nGlobex,20\n");
        $rows = Block::fromCsvFile($file, header: false);

        $this->assertSame("Acme", $rows->get("0.0"));
        $this->assertSame("10", $rows->get("0.1"));
    }

    public function testCustomDelimiterIsSupported(): void
    {
        $file = $this->csvFile("Name;Amount\nAcme;10\n");
        $rows = Block::fromCsvFile($file, delimiter: ";");

        $this->assertSame("Acme", $rows->get("0.Name"));
    }

    public function testWindowsEncodingIsConvertedToUtf8(): void
    {
        $utf8 = "Name\nCaffè\n";
        $encoded = iconv("UTF-8", "Windows-1252", $utf8);
        $this->assertNotFalse($encoded);

        $file = $this->csvFile($encoded);
        $rows = Block::fromCsvFile($file, encoding: "Windows-1252");

        $this->assertSame("Caffè", $rows->get("0.Name"));
    }

    public function testUtf8BomIsRemovedFromTheFirstHeader(): void
    {
        $file = $this->csvFile("\xEF\xBB\xBFName,Amount\nAcme,10\n");
        $rows = Block::fromCsvFile($file);

        $this->assertSame("Acme", $rows->get("0.Name"));
    }

    public function testStrictRowWidthRejectsMalformedRowsWithTheRecordNumber(): void
    {
        $file = $this->csvFile("Name,Amount\nAcme\n");

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("record 2 has 1 fields; expected 2");

        Block::fromCsvFile($file);
    }

    public function testPadPolicyAddsNullsToShortRows(): void
    {
        $file = $this->csvFile("Name,Amount\nAcme\n");
        $rows = Block::fromCsvFile($file, rowWidth: CsvRowWidth::PAD);

        $this->assertNull($rows->get("0.Amount"));
    }

    public function testPadPolicyStillRejectsRowsWiderThanTheHeader(): void
    {
        $file = $this->csvFile("Name,Amount\nAcme,10,Unexpected\n");

        $this->expectException(UnexpectedValueException::class);

        Block::fromCsvFile($file, rowWidth: CsvRowWidth::PAD);
    }

    public function testSkipPolicyIgnoresRowsWithTheWrongWidth(): void
    {
        $file = $this->csvFile("Name,Amount\nInvalid\nAcme,10\nToo,Many,Fields\n");
        $rows = Block::fromCsvFile($file, rowWidth: CsvRowWidth::SKIP);

        $this->assertCount(1, $rows);
        $this->assertSame("Acme", $rows->get("0.Name"));
    }

    public function testHeadersMustBePresentAndUnique(): void
    {
        $emptyHeader = $this->csvFile("Name,\nAcme,10\n");
        $duplicateHeader = $this->csvFile("Name,Name\nAcme,Alias\n");

        try {
            Block::fromCsvFile($emptyHeader);
            $this->fail("An empty CSV header should be rejected");
        } catch (UnexpectedValueException $exception) {
            $this->assertStringContainsString("must not be empty", $exception->getMessage());
        }

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("must be unique");

        Block::fromCsvFile($duplicateHeader);
    }

    public function testNormalizationCanReturnAnArrayOrBlock(): void
    {
        $file = $this->csvFile("Name,Amount\nAcme,10.5\nGlobex,20\n");

        $rows = Block::fromCsvFile(
            $file,
            normalize: fn(Block $row, int $record): array|Block => $record === 2
                ? [
                    ...$row->toArray(),
                    "Amount" => $row->getFloatStrict("Amount"),
                ]
                : $row->set("Amount", $row->getFloatStrict("Amount")),
        );

        $this->assertSame(10.5, $rows->get("0.Amount"));
        $this->assertSame(20.0, $rows->get("1.Amount"));
    }

    public function testNormalizationRejectsUnsupportedReturnValues(): void
    {
        $file = $this->csvFile("Name\nAcme\n");

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("must return an array or Block");

        Block::fromCsvFile($file, normalize: fn(): string => "invalid");
    }

    public function testMissingFilesAndInvalidCsvCharactersAreRejected(): void
    {
        try {
            Block::fromCsvFile(__DIR__ . "/does-not-exist.csv");
            $this->fail("A missing CSV file should be rejected");
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString("Unable to open", $exception->getMessage());
        }

        $file = $this->csvFile("Name\nAcme\n");

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("delimiter");

        Block::fromCsvFile($file, delimiter: "||");
    }

    public function testEnclosureAndEscapeCharactersAreValidated(): void
    {
        $file = $this->csvFile("Name\nAcme\n");

        try {
            Block::fromCsvFile($file, enclosure: "");
            $this->fail("An empty enclosure should be rejected");
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString("enclosure", $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("escape");

        Block::fromCsvFile($file, escape: "//");
    }

    private function csvFile(string $contents): string
    {
        $file = tempnam(sys_get_temp_dir(), "data-block-csv-");
        if ($file === false) {
            throw new RuntimeException("Unable to create temporary CSV file");
        }
        file_put_contents($file, $contents);
        $this->temporaryFiles[] = $file;

        return $file;
    }
}
