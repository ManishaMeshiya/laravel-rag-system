<?php

namespace Tests\Feature\Http\Controllers\Document;

use App\Models\Document;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
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
        ]);

        $this->get(route('documents.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Chat/Index')
                ->has('documents', 1)
                ->where('documents.0.title', 'Employee handbook')
                ->where('documents.0.original_filename', 'handbook.txt')
                ->missing('documents.0.path')
                ->missing('documents.0.body'));
    }

    public function test_store_uploads_a_text_file_and_persists_file_metadata(): void
    {
        Storage::fake();

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
        ]);

        $document = Document::query()->first();

        $this->assertSame('Vacation policy', $document->title);
        Storage::assertExists($document->path);
    }

    public function test_store_uses_the_original_filename_when_title_is_blank(): void
    {
        Storage::fake();

        $file = UploadedFile::fake()->createWithContent('onboarding.md', '# Welcome to the team');

        $this->post(route('documents.store'), [
            'title' => '',
            'file' => $file,
        ])->assertRedirectToRoute('documents.index');

        $this->assertDatabaseHas('documents', [
            'title' => 'onboarding.md',
            'original_filename' => 'onboarding.md',
        ]);
    }

    public function test_store_ignores_a_client_supplied_storage_path(): void
    {
        Storage::fake();

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
}
