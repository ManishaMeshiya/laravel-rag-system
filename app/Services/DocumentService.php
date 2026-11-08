<?php

namespace App\Services;

use App\Data\StoreDocumentData;
use App\Jobs\IngestDocument;
use App\Models\Document;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class DocumentService
{
    /**
     * @return Collection<int, Document>
     */
    public function getDocuments(): Collection
    {
        return Document::query()
            ->select('id', 'title', 'original_filename', 'status')
            ->latest()
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @throws Throwable
     */
    public function storeDocument(StoreDocumentData $data): Document
    {
        $file = $data->file;
        $originalFilename = $file->getClientOriginalName();
        $path = $file->store('documents');

        if ($path === false) {
            throw new RuntimeException('Failed to store the uploaded document.');
        }

        try {
            $document = Document::create([
                'title' => filled($data->title) ? $data->title : $originalFilename,
                'original_filename' => $originalFilename,
                'path' => $path,
                'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
            ]);
        } catch (Throwable $exception) {
            Storage::delete($path);

            throw $exception;
        }

        IngestDocument::dispatch($document);

        return $document;
    }
}
