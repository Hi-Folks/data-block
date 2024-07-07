<?php

use HiFolks\DataType\Block;

it('validate YAML object via URL', function (): void {
    $file = "./.github/workflows/run-tests.yml";
    $workflow = Block::fromYamlFile($file);
    expect($workflow->validateJsonViaUrl('https://json.schemastore.org/github-workflow'))->toBeTrue();


})->group("url");

it('validate YAML object Github Workflow', function (): void {
    $file = "./.github/workflows/run-tests.yml";
    $workflow = Block::fromYamlFile($file);
    expect($workflow->validateJsonSchemaGithubWorkflow())->toBeTrue();
})->group("url");
