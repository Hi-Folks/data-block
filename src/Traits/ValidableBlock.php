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

    public function validateJsonWithSchema(string $schemaJson): bool
    {
        try {
            $schema =
                Schema::import(json_decode($schemaJson));
            $schema->in($this->toJsonObject());
        } catch (\Exception $e) {
            var_dump($e);
            return false;
        }
        return true;
    }
}
