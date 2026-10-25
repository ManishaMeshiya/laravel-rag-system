<?php

declare(strict_types=1);

namespace App\Data;

readonly class ChunkData
{
    public function __construct(
        public string $content,
        public int $index,
    ) {}
}
