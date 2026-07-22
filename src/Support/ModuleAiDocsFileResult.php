<?php

namespace SamuelNunes\LaravelDddToolkit\Support;

final readonly class ModuleAiDocsFileResult
{
    public function __construct(
        public string $status,
        public string $message,
    ) {
    }
}
