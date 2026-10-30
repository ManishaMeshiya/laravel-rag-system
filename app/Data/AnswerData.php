<?php

declare(strict_types=1);

namespace App\Data;

readonly class AnswerData
{
    /**
     * @param  list<RetrievedContext>  $sources
     */
    public function __construct(
        public string $text,
        public array $sources,
    ) {}
}
