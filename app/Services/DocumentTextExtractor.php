<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Document;
use Exception;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Smalot\PdfParser\Parser;

class DocumentTextExtractor
{
    /**
     * @var list<string>
     */
    private const array SUPPORTED_MIME_TYPES = [
        'text/plain',
        'text/markdown',
        'text/x-markdown',
        'application/pdf',
    ];

    public function __construct(private Parser $parser) {}

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

        if ($document->mime_type !== 'application/pdf') {
            return $contents;
        }

        return $this->extractPdf($contents);
    }

    /**
     * @throws RuntimeException
     */
    private function extractPdf(string $contents): string
    {
        try {
            return trim($this->parser->parseContent($contents)->getText());
        } catch (Exception $exception) {
            throw new RuntimeException('The PDF could not be read.', previous: $exception);
        }
    }
}
