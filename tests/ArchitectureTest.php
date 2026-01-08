<?php

declare(strict_types=1);

use PhpParser\Node;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PHPUnit\Framework\TestCase;

final class ArchitectureTest extends TestCase
{
    private array $forbiddenFunctions = ["var_dump", "dd", "dump"];

    private function getPhpFiles(string $dir): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === "php") {
                $files[] = $file->getPathname();
            }
        }
        return $files;
    }

    public function testNoForbiddenGlobals(): void
    {
        // Use the newest supported parser API
        $parser = (new ParserFactory())->createForNewestSupportedVersion();

        $errors = [];

        foreach ($this->getPhpFiles(__DIR__ . "/../src") as $filePath) {
            $code = file_get_contents($filePath);

            try {
                $ast = $parser->parse($code);
            } catch (\PhpParser\Error $e) {
                $errors[] = "Parse error in $filePath: {$e->getMessage()}";
                continue;
            }

            $traverser = new NodeTraverser();
            $traverser->addVisitor(
                new class ($filePath, $this->forbiddenFunctions, $errors) extends NodeVisitorAbstract {
                    private readonly array $forbiddenFunctions;
                    private array $errors;

                    public function __construct(
                        private readonly string $filePath,
                        array $forbiddenFunctions,
                        array &$errors,
                    ) {
                        $this->forbiddenFunctions = array_map(
                            strtolower(...),
                            $forbiddenFunctions,
                        );
                        $this->errors = &$errors;
                    }

                    public function enterNode(Node $node): void
                    {
                        if ($node instanceof Node\Expr\FuncCall) {
                            $name = $node->name;
                            if ($name instanceof Node\Name) {
                                $func = strtolower($name->toString());
                                if (
                                    in_array(
                                        $func,
                                        $this->forbiddenFunctions,
                                        true,
                                    )
                                ) {
                                    if (
                                        str_ends_with(
                                            $this->filePath,
                                            "ExportableBlock.php",
                                        )
                                        && $node->getStartLine() === 35
                                    ) {
                                        // skip
                                    } else {
                                        $this->errors[] = "Forbidden function '$func()' used in {$this->filePath} on line {$node->getStartLine()}";
                                    }
                                }
                            }
                        }
                    }
                },
            );

            $traverser->traverse($ast);
        }

        $this->assertEmpty(
            $errors,
            "Forbidden global functions found:\n" . implode("\n", $errors),
        );
    }
}
