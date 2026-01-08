<?php

declare(strict_types=1);

use HiFolks\DataType\Block;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

final class BlockSchemaValidationTest extends TestCase
{
    #[Group("url")]
    public function testValidateYamlObjectViaUrl(): void
    {
        $file = __DIR__ . "/../.github/workflows/run-tests.yml";

        $workflow = Block::fromYamlFile($file);

        $this->assertTrue(
            $workflow->validateJsonViaUrl(
                "https://json.schemastore.org/github-workflow",
            ),
        );
    }

    #[Group("url")]
    public function testValidateYamlObjectGithubWorkflow(): void
    {
        $file = __DIR__ . "/../.github/workflows/run-tests.yml";

        $workflow = Block::fromYamlFile($file);

        $this->assertTrue($workflow->validateJsonSchemaGithubWorkflow());
    }
}
