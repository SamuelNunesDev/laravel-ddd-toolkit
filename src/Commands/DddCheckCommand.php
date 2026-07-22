<?php

namespace SamuelNunes\LaravelDddToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use SamuelNunes\LaravelDddToolkit\Discovery\ModuleDiscovery;
use SamuelNunes\LaravelDddToolkit\Support\ModulePaths;

class DddCheckCommand extends Command
{
    protected $signature = 'ddd:check {--module= : Validate a single module} {--preset=hexagonal : Architecture preset to validate}';

    protected $description = 'Check modules for basic DDD architecture violations.';

    public function handle(Filesystem $files): int
    {
        $preset = (string) $this->option('preset');

        if ($preset !== 'hexagonal') {
            $this->components->info("No checks are defined for preset [{$preset}].");

            return self::SUCCESS;
        }

        $modulePaths = new ModulePaths($this->laravel);

        try {
            $modules = $this->modulesToCheck($modulePaths);
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $violations = [];

        foreach ($modules as $modulePath) {
            $violations = array_merge($violations, $this->domainViolations($files, $modulePath));
            $violations = array_merge($violations, $this->applicationViolations($files, $modulePath));
        }

        foreach ($violations as $violation) {
            $this->line($violation);
        }

        if ($violations !== []) {
            $this->components->error(count($violations) . ' architecture violation(s) found.');

            return self::FAILURE;
        }

        $this->components->info('No architecture violations found.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function modulesToCheck(ModulePaths $modulePaths): array
    {
        $module = $this->option('module');

        if (is_string($module) && $module !== '') {
            $module = $modulePaths->moduleName($module);
            $path = $modulePaths->modulePath($module);

            if (! is_dir($path)) {
                throw new InvalidArgumentException("Module [{$module}] does not exist.");
            }

            return [$path];
        }

        return (new ModuleDiscovery($this->laravel))->modules();
    }

    /**
     * @return array<int, string>
     */
    private function domainViolations(Filesystem $files, string $modulePath): array
    {
        return $this->violationsForLayer($files, $modulePath, 'Domain', [
            'Illuminate\\' => 'Domain layer imports Laravel Illuminate classes.',
            'Laravel\\' => 'Domain layer imports Laravel classes.',
            'Symfony\\Component\\HttpFoundation' => 'Domain layer imports HTTP foundation classes.',
            'GuzzleHttp\\' => 'Domain layer imports Guzzle HTTP classes.',
            '\\Infrastructure\\' => 'Domain layer imports Infrastructure classes.',
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function applicationViolations(Filesystem $files, string $modulePath): array
    {
        return $this->violationsForLayer($files, $modulePath, 'Application', [
            '\\Infrastructure\\Http\\Controllers' => 'Application layer imports HTTP controllers.',
            '\\Infrastructure\\Http\\Requests' => 'Application layer imports HTTP requests.',
            '\\Infrastructure\\Persistence\\Models' => 'Application layer imports persistence models.',
        ]);
    }

    /**
     * @param array<string, string> $forbiddenReferences
     * @return array<int, string>
     */
    private function violationsForLayer(Filesystem $files, string $modulePath, string $layer, array $forbiddenReferences): array
    {
        $directory = $modulePath . DIRECTORY_SEPARATOR . $layer;

        if (! $files->isDirectory($directory)) {
            return [];
        }

        $violations = [];

        foreach ($files->allFiles($directory) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            try {
                $references = $this->referencesFromFile($files->get($file->getPathname()));
            } catch (Error $error) {
                $violations[] = "[{$this->relativePath($file->getPathname())}]\n\nViolation:\nCould not parse PHP file: {$error->getMessage()}\n\nRule:\nddd:check requires valid PHP files.\n";

                continue;
            }

            foreach ($forbiddenReferences as $forbiddenReference => $message) {
                if (! $this->referencesForbiddenNamespace($references, $forbiddenReference)) {
                    continue;
                }

                $violations[] = "[{$this->relativePath($file->getPathname())}]\n\nViolation:\n{$message}\n\nRule:\n{$layer} classes should respect the hexagonal dependency direction.\n";
            }
        }

        return $violations;
    }

    /**
     * @return array<int, string>
     */
    private function referencesFromFile(string $contents): array
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $statements = $parser->parse($contents) ?? [];
        $visitor = new class extends NodeVisitorAbstract {
            /**
             * @var array<int, string>
             */
            public array $references = [];

            public function enterNode(Node $node): ?int
            {
                if ($node instanceof Node\Stmt\Use_) {
                    foreach ($node->uses as $use) {
                        $this->references[] = $use->name->toString();
                    }

                    return null;
                }

                if ($node instanceof Node\Name\FullyQualified) {
                    $this->references[] = $node->toString();
                }

                return null;
            }
        };

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($statements);

        return array_values(array_unique($visitor->references));
    }

    /**
     * @param array<int, string> $references
     */
    private function referencesForbiddenNamespace(array $references, string $forbiddenReference): bool
    {
        $forbiddenReference = trim($forbiddenReference, '\\');

        foreach ($references as $reference) {
            $reference = trim($reference, '\\');

            if ($reference === $forbiddenReference || str_starts_with($reference, $forbiddenReference . '\\')) {
                return true;
            }

            if (str_contains($reference, '\\' . $forbiddenReference . '\\') || str_ends_with($reference, '\\' . $forbiddenReference)) {
                return true;
            }

            if ($forbiddenReference === 'Infrastructure' && str_contains($reference, '\\Infrastructure\\')) {
                return true;
            }
        }

        return false;
    }

    private function relativePath(string $path): string
    {
        $base = rtrim($this->laravel->basePath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        return str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
    }
}
