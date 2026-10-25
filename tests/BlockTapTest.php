<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\TestCase;

final class BlockTapTest extends TestCase
{
    public function testTapReceivesAndReturnsTheSameBlockInstance(): void
    {
        $rows = Block::make([
            "first" => ["active" => true],
            "second" => ["active" => false],
        ]);
        $received = null;

        $result = $rows->tap(function (Block $block) use (&$received): void {
            $received = $block;
        });

        $this->assertSame($rows, $received);
        $this->assertSame($rows, $result);
    }

    public function testTapIsCalledExactlyOnceForAnEmptyBlock(): void
    {
        $rows = Block::make([]);
        $calls = 0;

        $result = $rows->tap(function (Block $block) use (&$calls): void {
            ++$calls;
            $this->assertSame([], $block->toArray());
        });

        $this->assertSame(1, $calls);
        $this->assertSame($rows, $result);
    }

    public function testTapIgnoresTheCallbackReturnValue(): void
    {
        $rows = Block::make(["value" => 10]);

        $result = $rows->tap(fn(Block $block): string => "ignored");

        $this->assertSame($rows, $result);
        $this->assertSame(["value" => 10], $result->toArray());
    }

    public function testTapCanObserveAnIntermediatePipelineResult(): void
    {
        $observed = null;

        $result = Block::make([
            ["name" => "Ada", "active" => true],
            ["name" => "Grace", "active" => false],
            ["name" => "Linus", "active" => true],
        ])
            ->filter(
                fn(Block $row): bool => $row->get("active") === true,
            )
            ->tap(function (Block $rows) use (&$observed): void {
                $observed = [
                    "count" => $rows->count(),
                    "keys" => $rows->keys(),
                ];
            })
            ->map(fn(Block $row): string => $row->getStringStrict("name"));

        $this->assertSame(
            ["count" => 2, "keys" => [0, 2]],
            $observed,
        );
        $this->assertSame([0 => "Ada", 2 => "Linus"], $result->toArray());
    }

    public function testTapPropagatesCallbackExceptions(): void
    {
        $rows = Block::make(["value" => 10]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Unable to record the metric");

        $rows->tap(function (): never {
            throw new RuntimeException("Unable to record the metric");
        });
    }
}
