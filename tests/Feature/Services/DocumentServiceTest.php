<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Data\StoreDocumentData;
use App\Jobs\IngestDocument;
use App\Services\DocumentService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_failed_create_deletes_the_stored_file_and_rethrows(): void
    {
        Storage::fake();
        Queue::fake([IngestDocument::class]);

        $file = UploadedFile::fake()->createWithContent('notes.txt', 'A private fact.');

        try {
            (new DocumentService)->storeDocument(new StoreDocumentData(
                title: str_repeat('a', 256),
                file: $file,
            ));

            $this->fail('The document create was expected to fail.');
        } catch (QueryException) {
            Storage::assertDirectoryEmpty('documents');
        }

        Queue::assertNothingPushed();
    }
}
