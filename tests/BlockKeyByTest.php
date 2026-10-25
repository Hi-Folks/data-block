<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use HiFolks\DataType\Enums\KeyCollision;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BlockKeyByTest extends TestCase
{
    public function testKeyByBuildsALookupAndPreservesCompleteRows(): void
    {
        $users = Block::make([
            ["id" => "user-1", "name" => "Ada"],
            ["id" => "user-2", "name" => "Grace"],
        ]);

        $indexed = $users->keyBy("id");

        $this->assertSame([
            "user-1" => ["id" => "user-1", "name" => "Ada"],
            "user-2" => ["id" => "user-2", "name" => "Grace"],
        ], $indexed->toArray());
        $this->assertSame("Grace", $indexed->get("user-2.name"));
    }

    public function testKeyBySupportsNestedFields(): void
    {
        $rows = Block::make([
            ["metadata" => ["id" => "a"], "value" => 10],
            ["metadata" => ["id" => "b"], "value" => 20],
        ]);

        $this->assertSame(
            ["a", "b"],
            $rows->keyBy("metadata.id")->keys(),
        );
    }

    public function testDuplicateKeysThrowByDefaultWithSourceContext(): void
    {
        $rows = Block::make([
            "first-row" => ["id" => "duplicate", "value" => 10],
            "second-row" => ["id" => "duplicate", "value" => 20],
        ]);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            "Duplicate key 'duplicate' for field 'id' at source rows 'first-row' and 'second-row'",
        );

        $rows->keyBy("id");
    }

    public function testKeepFirstResolvesDuplicateKeysExplicitly(): void
    {
        $rows = Block::make([
            ["id" => "duplicate", "value" => 10],
            ["id" => "duplicate", "value" => 20],
        ]);

        $indexed = $rows->keyBy("id", KeyCollision::KEEP_FIRST);

        $this->assertSame(10, $indexed->get("duplicate.value"));
    }

    public function testKeepLastResolvesDuplicateKeysExplicitly(): void
    {
        $rows = Block::make([
            ["id" => "duplicate", "value" => 10],
            ["id" => "duplicate", "value" => 20],
        ]);

        $indexed = $rows->keyBy("id", KeyCollision::KEEP_LAST);

        $this->assertSame(20, $indexed->get("duplicate.value"));
    }

    public function testDuplicateDetectionUsesPhpArrayKeySemantics(): void
    {
        $rows = Block::make([
            "integer" => ["id" => 0],
            "string" => ["id" => "0"],
            "boolean" => ["id" => false],
        ]);

        $indexed = $rows->keyBy("id", KeyCollision::KEEP_LAST);

        $this->assertSame([0], $indexed->keys());
        $this->assertFalse($indexed->get("0.id"));
    }

    #[DataProvider("invalidKeyValues")]
    public function testInvalidKeyValuesAreRejected(mixed $value): void
    {
        $rows = Block::make(["source" => ["id" => $value]]);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            "Cannot index source row 'source' by field 'id': the value must be a non-null scalar",
        );

        $rows->keyBy("id");
    }

    /** @return iterable<string, array{mixed}> */
    public static function invalidKeyValues(): iterable
    {
        yield "null" => [null];
        yield "array" => [["nested"]];
        yield "object" => [new stdClass()];
    }

    public function testMissingKeyFieldIsReportedSeparately(): void
    {
        $rows = Block::make(["source" => ["name" => "Ada"]]);

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage(
            "Cannot index source row 'source' by field 'id': the field is missing",
        );

        $rows->keyBy("id");
    }

    public function testScalarRowsAreRejectedWithTheirSourceKey(): void
    {
        $rows = Block::make(["valid" => ["id" => "a"], "invalid" => "scalar"]);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            "Cannot index source row 'invalid': every row must be an array or Block",
        );

        $rows->keyBy("id");
    }

    public function testBlockRowsAreSupportedWithoutBeingModified(): void
    {
        $row = Block::make(["id" => "a", "value" => 10]);

        $indexed = Block::make([$row])->keyBy("id");

        $this->assertSame(10, $indexed->get("a.value"));
        $this->assertSame(["id" => "a", "value" => 10], $row->toArray());
    }

    public function testKeyByDoesNotModifyTheSourceAndPreservesIterationMode(): void
    {
        $rows = Block::make([
            ["id" => "a", "value" => 10],
            ["id" => "b", "value" => 20],
        ], false);
        $original = $rows->toArray();

        $indexed = $rows->keyBy("id");

        $this->assertSame($original, $rows->toArray());
        $this->assertIsArray($indexed->first());
    }

    public function testKeyBySupportsEmptyBlocks(): void
    {
        $this->assertSame([], Block::make([])->keyBy("id")->toArray());
    }
}
