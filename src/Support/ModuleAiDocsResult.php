<?php

namespace SamuelNunes\LaravelDddToolkit\Support;

final readonly class ModuleAiDocsResult
{
    /**
     * @param array<int, ModuleAiDocsFileResult> $files
     */
    public function __construct(
        public array $files,
        public bool $usedPlaceholder = false,
    ) {
    }
}
