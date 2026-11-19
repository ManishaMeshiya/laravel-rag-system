<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\DocumentStatus;
use App\Jobs\IngestDocument;
use App\Models\Document;
use App\Models\DocumentChunk;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;
use RuntimeException;
use Tests\TestCase;

class IngestDocumentTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_text_file_is_stored_as_ordered_chunks_with_embeddings(): void
    {
        config([
            'rag.chunk_size' => 5,
            'rag.chunk_overlap' => 0,
            'rag.embedding_dimensions' => 1536,
        ]);

        $document = Document::factory()->create();

        Storage::fake();
        Storage::put($document->path, 'aaaaabbbbb');

        Embeddings::fake(function (EmbeddingsPrompt $prompt): array {
            return array_map(
                fn (string $input): array => $this->vector($input === 'aaaaa' ? 0.5 : 0.25),
                $prompt->inputs,
            );
        });

        $this->ingest($document);

        $chunks = $document->chunks()->orderBy('id')->get();

        $this->assertSame(['aaaaa', 'bbbbb'], $chunks->pluck('content')->all());
        $this->assertSame($this->vector(0.5), $chunks[0]->embedding);
        $this->assertSame($this->vector(0.25), $chunks[1]->embedding);
        $this->assertSame(DocumentStatus::Ready, $document->refresh()->status);
        Embeddings::assertGenerated(fn (EmbeddingsPrompt $prompt): bool => $prompt->dimensions === 1536
            && $prompt->inputs === ['aaaaa', 'bbbbb']);
    }

    public function test_running_twice_replaces_chunks_instead_of_duplicating_them(): void
    {
        config(['rag.embedding_dimensions' => 1536]);

        $document = Document::factory()->create();

        Storage::fake();
        Storage::put($document->path, 'alpha');

        Embeddings::fake([
            [$this->vector(0.5)],
        ]);

        $this->ingest($document);

        Storage::put($document->path, 'beta');

        Embeddings::fake([
            [$this->vector(0.25)],
        ]);

        $this->ingest($document);

        $chunks = $document->chunks()->orderBy('id')->get();

        $this->assertCount(1, $chunks);
        $this->assertSame('beta', $chunks[0]->content);
        $this->assertSame($this->vector(0.25), $chunks[0]->embedding);
    }

    public function test_empty_text_stores_no_chunks(): void
    {
        $document = Document::factory()->create();

        DocumentChunk::factory()->for($document)->create([
            'content' => 'stale',
        ]);

        Storage::fake();
        Storage::put($document->path, '');

        Embeddings::fake()->preventStrayEmbeddings();

        $this->ingest($document);

        $this->assertDatabaseCount('document_chunks', 0);
        Embeddings::assertNothingGenerated();
    }

    public function test_pdf_file_is_stored_as_chunks_with_embeddings(): void
    {
        config(['rag.embedding_dimensions' => 1536]);

        $document = Document::factory()->create([
            'mime_type' => 'application/pdf',
            'path' => 'documents/handbook.pdf',
        ]);

        Storage::fake();
        Storage::put($document->path, file_get_contents(base_path('tests/Fixtures/handbook.pdf')));

        Embeddings::fake([
            [$this->vector(0.5)],
        ]);

        $this->ingest($document);

        $chunks = $document->chunks()->orderBy('id')->get();

        $this->assertCount(1, $chunks);
        $this->assertSame('Employees receive 20 vacation days.', $chunks[0]->content);
        $this->assertSame($this->vector(0.5), $chunks[0]->embedding);
    }

    public function test_unsupported_mime_type_fails_and_stores_no_chunks(): void
    {
        $document = Document::factory()->create([
            'mime_type' => 'image/jpeg',
        ]);

        Storage::fake();
        Storage::put($document->path, 'Employees receive 20 vacation days.');

        Embeddings::fake()->preventStrayEmbeddings();

        try {
            $this->ingest($document);
            $this->fail('Ingestion was expected to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Text extraction is not supported for this document.', $exception->getMessage());
        }

        $this->assertDatabaseCount('document_chunks', 0);
        Embeddings::assertNothingGenerated();
    }

    public function test_missing_file_fails_and_stores_no_chunks(): void
    {
        $document = Document::factory()->create();

        Storage::fake();

        Embeddings::fake()->preventStrayEmbeddings();

        try {
            $this->ingest($document);
            $this->fail('Ingestion was expected to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The document file is missing.', $exception->getMessage());
        }

        $this->assertDatabaseCount('document_chunks', 0);
        Embeddings::assertNothingGenerated();
    }

    public function test_terminal_failure_marks_the_document_as_failed(): void
    {
        $document = Document::factory()->create();
        $job = new IngestDocument($document);

        $job->failed(new RuntimeException('Provider unavailable.'));

        $this->assertSame(DocumentStatus::Failed, $document->refresh()->status);
    }

    public function test_job_for_a_deleted_document_is_discarded_without_failing(): void
    {
        config(['queue.default' => 'database']);

        $document = Document::factory()->create();

        Embeddings::fake()->preventStrayEmbeddings();

        IngestDocument::dispatch($document);
        $document->delete();

        $this->artisan('queue:work', ['--once' => true, '--stop-when-empty' => true]);

        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('failed_jobs', 0);
        Embeddings::assertNothingGenerated();
    }

    private function ingest(Document $document): void
    {
        app()->call([new IngestDocument($document), 'handle']);
    }

    /**
     * @return list<float>
     */
    private function vector(float $value): array
    {
        return array_fill(0, 1536, $value);
    }
}
