<?php

namespace HiFolks\DataType\Traits;

use Swaggest\JsonSchema\Schema;

trait ValidableBlock
{
    public function validateJsonViaUrl(string $url): bool
    {
        try {
            $schema =
                Schema::import($url);
            $schema->in($this->toJsonObject());
        } catch (\Exception) {
            return false;
        }
        return true;

    }

    public function validateJsonSchemaGithubWorkflow(): bool
    {
        return $this->validateJsonViaUrl('https://json.schemastore.org/github-workflow');
    }
}