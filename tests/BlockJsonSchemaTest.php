<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\TestCase;

final class BlockJsonSchemaTest extends TestCase
{
    private array $fruitsArray = [
        [
            "name" => "Avocado",
            "fruit" => "🥑",
            "wikipedia" => "https://en.wikipedia.org/wiki/Avocado",
            "color" => "green",
            "rating" => 8,
        ],
        [
            "name" => "Apple",
            "fruit" => "🍎",
            "wikipedia" => "https://en.wikipedia.org/wiki/Apple",
            "color" => "red",
            "rating" => 7,
        ],
        [
            "name" => "Banana",
            "fruit" => "🍌",
            "wikipedia" => "https://en.wikipedia.org/wiki/Banana",
            "color" => "yellow",
            "rating" => 8.5,
        ],
        [
            "name" => "Cherry",
            "fruit" => "🍒",
            "wikipedia" => "https://en.wikipedia.org/wiki/Cherry",
            "color" => "red",
            "rating" => 9,
        ],
    ];

    private string $schemaJson = <<<'JSON'
    {
      "type": "array",
        "items" : {
            "type": "object",
            "properties": {
                "name": {
                    "type": "string"
                },
                "fruit": {
                    "type": "string"
                },
                "wikipedia": {
                    "type": "string"
                },
                "color": {
                    "type": "string"
                },
                "rating": {
                    "type": "number"
                }
            }
        }
    }
    JSON;

    public function testValidateJson(): void
    {
        $data = Block::make($this->fruitsArray);

        // validate against the correct schema
        $this->assertTrue($data->validateJsonWithSchema($this->schemaJson));

        // modify schema to require integer rating
        $schemaBlock = Block::fromJsonString($this->schemaJson);
        $schemaBlock->set("items.properties.rating.type", "integer");

        $this->assertFalse(
            $data->validateJsonWithSchema($schemaBlock->toJson()),
        );
    }
}
