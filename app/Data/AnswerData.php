<?php

declare(strict_types=1);

namespace App\Data;

readonly class AnswerData
{
    public function __construct(
        public string $text,
    ) {}
}
