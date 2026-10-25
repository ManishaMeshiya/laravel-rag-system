<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DocumentTextExtractor
{
    /**
     * @var list<string>
     */
    private const array SUPPORTED_MIME_TYPES = [
        'text/plain',
        'text/markdown',
        'text/x-markdown',
    ];

    /**
     * @throws RuntimeException
     */
    public function extract(Document $document): string
    {
        if (! in_array($document->mime_type, self::SUPPORTED_MIME_TYPES, true)) {
            throw new RuntimeException('Text extraction is not supported for this document.');
        }

        $contents = Storage::get($document->path);

        if ($contents === null) {
            throw new RuntimeException('The document file is missing.');
        }

        return $contents;
    }
}
