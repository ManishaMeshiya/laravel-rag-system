<?php

namespace Tests\Feature\Http\Controllers\Document;

use App\Enums\DocumentStatus;
use App\Jobs\IngestDocument;
use App\Models\ChatMessage;
use App\Models\Document;
use App\Models\DocumentChunk;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DocumentControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_index_lists_document_titles_and_original_filenames(): void
    {
        Document::factory()->create([
            'title' => 'Employee handbook',
            'original_filename' => 'handbook.txt',
            'path' => 'documents/secret-handbook.txt',
            'status' => DocumentStatus::Ready,
        ]);
        ChatMessage::factory()->create([
            'question' => 'How many vacation days?',
            'answer' => 'Employees receive 20 vacation days.',
            'sources' => [[
                'document_id' => 1,
                'document_title' => 'Employee handbook',
                'excerpt' => 'Employees receive 20 vacation days.',
            ]],
        ]);

        $this->get(route('documents.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Chat/Index')
                ->has('documents', 1)
                ->where('documents.0.title', 'Employee handbook')
                ->where('documents.0.original_filename', 'handbook.txt')
                ->where('documents.0.status', DocumentStatus::Ready->value)
                ->missing('documents.0.path')
                ->missing('documents.0.body')
                ->has('messages', 1)
                ->where('messages.0.question', 'How many vacation days?')
                ->where('messages.0.answer', 'Employees receive 20 vacation days.')
                ->where('messages.0.sources.0.document_title', 'Employee handbook'));
    }

    public function test_store_uploads_a_text_file_and_persists_file_metadata(): void
    {
        Storage::fake();
        Queue::fake([IngestDocument::class]);

        $file = UploadedFile::fake()->createWithContent('vacation-policy.txt', 'Employees receive 20 vacation days.');

        $this->post(route('documents.store'), [
            'title' => 'Vacation policy',
            'file' => $file,
        ])
            ->assertRedirectToRoute('documents.index')
            ->assertSessionHas('success', 'Document uploaded successfully');

        $this->assertDatabaseHas('documents', [
            'title' => 'Vacation policy',
            'original_filename' => 'vacation-policy.txt',
            'mime_type' => 'text/plain',
            'status' => DocumentStatus::Pending->value,
        ]);

        $document = Document::query()->first();

        $this->assertSame('Vacation policy', $document->title);
        Storage::assertExists($document->path);
        Queue::assertPushed(IngestDocument::class, fn (IngestDocument $job): bool => $job->document->is($document));
        $this->assertDatabaseCount('document_chunks', 0);
    }

    public function test_store_uploads_a_pdf_and_persists_file_metadata(): void
    {
        Storage::fake();
        Queue::fake([IngestDocument::class]);

        $file = UploadedFile::fake()->createWithContent(
            'handbook.pdf',
            file_get_contents(base_path('tests/Fixtures/handbook.pdf')),
        );

        $this->post(route('documents.store'), [
            'title' => 'Employee handbook',
            'file' => $file,
        ])->assertRedirectToRoute('documents.index');

        $this->assertDatabaseHas('documents', [
            'title' => 'Employee handbook',
            'original_filename' => 'handbook.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $document = Document::query()->first();

        Storage::assertExists($document->path);
        Queue::assertPushed(IngestDocument::class, fn (IngestDocument $job): bool => $job->document->is($document));
    }

    public function test_store_uses_the_original_filename_when_title_is_blank(): void
    {
        Storage::fake();
        Queue::fake([IngestDocument::class]);

        $file = UploadedFile::fake()->createWithContent('onboarding.md', '# Welcome to the team');

        $this->post(route('documents.store'), [
            'title' => '',
            'file' => $file,
        ])->assertRedirectToRoute('documents.index');

        $this->assertDatabaseHas('documents', [
            'title' => 'onboarding.md',
            'original_filename' => 'onboarding.md',
        ]);

        $document = Document::query()->first();

        Queue::assertPushed(IngestDocument::class, fn (IngestDocument $job): bool => $job->document->is($document));
        $this->assertDatabaseCount('document_chunks', 0);
    }

    public function test_store_ignores_a_client_supplied_storage_path(): void
    {
        Storage::fake();
        Queue::fake([IngestDocument::class]);

        $file = UploadedFile::fake()->createWithContent('notes.txt', 'A private fact.');

        $this->post(route('documents.store'), [
            'title' => 'Notes',
            'file' => $file,
            'path' => '../evil.txt',
        ])->assertRedirectToRoute('documents.index');

        $this->assertDatabaseMissing('documents', [
            'path' => '../evil.txt',
        ]);

        $document = Document::query()->first();

        $this->assertNotSame('../evil.txt', $document->path);
        Storage::assertExists($document->path);
        Queue::assertPushed(IngestDocument::class, fn (IngestDocument $job): bool => $job->document->is($document));
        $this->assertDatabaseCount('document_chunks', 0);
    }

    public function test_store_rejects_a_request_without_a_file(): void
    {
        $this->from(route('documents.index'))
            ->post(route('documents.store'), [
                'title' => 'Missing file',
            ])
            ->assertRedirectToRoute('documents.index')
            ->assertInvalid([
                'file' => 'The file field is required.',
            ]);

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_store_rejects_a_disallowed_file_type(): void
    {
        Storage::fake();

        $this->from(route('documents.index'))
            ->post(route('documents.store'), [
                'file' => UploadedFile::fake()->create('photo.jpg', 20, 'image/jpeg'),
            ])
            ->assertRedirectToRoute('documents.index')
            ->assertInvalid(['file']);

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_store_rejects_a_file_larger_than_ten_megabytes(): void
    {
        Storage::fake();

        $this->from(route('documents.index'))
            ->post(route('documents.store'), [
                'file' => UploadedFile::fake()->create('notes.txt', 10241, 'text/plain'),
            ])
            ->assertRedirectToRoute('documents.index')
            ->assertInvalid([
                'file' => 'The file field must not be greater than 10240 kilobytes.',
            ]);

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_destroy_deletes_the_document_chunks_and_stored_file(): void
    {
        Storage::fake();

        $document = Document::factory()->create();
        DocumentChunk::factory()->for($document)->create();
        Storage::put($document->path, 'Document contents');

        $this->delete(route('documents.destroy', $document))
            ->assertRedirectToRoute('documents.index')
            ->assertSessionHas('success', 'Document deleted successfully');

        $this->assertModelMissing($document);
        $this->assertDatabaseCount('document_chunks', 0);
        Storage::assertMissing($document->path);
    }

    public function test_store_is_rate_limited(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('documents.store'), [])->assertInvalid('file');
        }

        $this->post(route('documents.store'), [])->assertTooManyRequests();

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_destroy_is_rate_limited(): void
    {
        $document = Document::factory()->create();

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->delete(route('documents.destroy', 999999))->assertNotFound();
        }

        $this->delete(route('documents.destroy', $document))->assertTooManyRequests();

        $this->assertModelExists($document);
    }
}
