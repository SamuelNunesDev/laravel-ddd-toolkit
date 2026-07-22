<?php

namespace SamuelNunes\LaravelDddToolkit\Support;

final readonly class AgentsPublishResult
{
    public function __construct(
        public string $status,
        public string $message,
    ) {
    }
}
