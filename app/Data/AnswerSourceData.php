<?php

declare(strict_types=1);

namespace App\Data;

readonly class AnswerSourceData
{
    public function __construct(
        public int $documentId,
        public string $documentTitle,
        public string $excerpt,
    ) {}

    /**
     * @return array{document_id: int, document_title: string, excerpt: string}
     */
    public function toArray(): array
    {
        return [
            'document_id' => $this->documentId,
            'document_title' => $this->documentTitle,
            'excerpt' => $this->excerpt,
        ];
    }
}
