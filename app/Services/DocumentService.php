<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
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
            ->select('id', 'title', 'original_filename')
            ->latest()
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  array{title?: string|null, file: UploadedFile}  $data
     *
     * @throws Throwable
     */
    public function storeDocument(array $data): Document
    {
        $file = $data['file'];
        $originalFilename = $file->getClientOriginalName();
        $path = $file->store('documents');

        if ($path === false) {
            throw new RuntimeException('Failed to store the uploaded document.');
        }

        return Document::create([
            'title' => filled($data['title'] ?? null) ? $data['title'] : $originalFilename,
            'original_filename' => $originalFilename,
            'path' => $path,
            'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
        ]);
    }
}
