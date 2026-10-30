<?php

declare(strict_types=1);

namespace App\Data;

readonly class RetrievedContext
{
    public function __construct(
        public int $chunkId,
        public string $content,
        public int $documentId,
        public string $documentTitle,
    ) {}
}
