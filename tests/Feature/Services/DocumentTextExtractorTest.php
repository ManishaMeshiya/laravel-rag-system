<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Document;
use App\Services\DocumentTextExtractor;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

class DocumentTextExtractorTest extends TestCase
{
    public function test_plain_text_file_returns_its_contents(): void
    {
        Storage::fake();

        $document = Document::factory()->make([
            'path' => 'documents/handbook.txt',
            'mime_type' => 'text/plain',
        ]);

        Storage::put($document->path, 'Employees receive 20 vacation days.');

        $contents = $this->extractor()->extract($document);

        $this->assertSame('Employees receive 20 vacation days.', $contents);
    }

    #[DataProvider('markdownMimeTypes')]
    public function test_markdown_file_returns_its_contents(string $mimeType): void
    {
        Storage::fake();

        $document = Document::factory()->make([
            'path' => 'documents/handbook.md',
            'mime_type' => $mimeType,
        ]);

        Storage::put($document->path, '# Welcome');

        $contents = $this->extractor()->extract($document);

        $this->assertSame('# Welcome', $contents);
    }

    public function test_missing_file_throws(): void
    {
        Storage::fake();

        $document = Document::factory()->make([
            'path' => 'documents/missing.txt',
            'mime_type' => 'text/plain',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The document file is missing.');

        $this->extractor()->extract($document);
    }

    public function test_pdf_returns_its_text(): void
    {
        Storage::fake();

        $document = Document::factory()->make([
            'path' => 'documents/handbook.pdf',
            'mime_type' => 'application/pdf',
        ]);

        Storage::put($document->path, $this->handbookPdf());

        $contents = $this->extractor()->extract($document);

        $this->assertSame('Employees receive 20 vacation days.', $contents);
    }

    public function test_unreadable_pdf_throws(): void
    {
        Storage::fake();

        $document = Document::factory()->make([
            'path' => 'documents/broken.pdf',
            'mime_type' => 'application/pdf',
        ]);

        Storage::put($document->path, '%PDF-1.4');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The PDF could not be read.');

        $this->extractor()->extract($document);
    }

    private function extractor(): DocumentTextExtractor
    {
        return new DocumentTextExtractor(new Parser);
    }

    private function handbookPdf(): string
    {
        $contents = file_get_contents(base_path('tests/Fixtures/handbook.pdf'));

        $this->assertIsString($contents);

        return $contents;
    }

    /**
     * @return array<string, array{string}>
     */
    public static function markdownMimeTypes(): array
    {
        return [
            'text/markdown' => ['text/markdown'],
            'text/x-markdown' => ['text/x-markdown'],
        ];
    }
}
