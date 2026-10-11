<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class DocumentService
{
    /**
     * @return Collection<int, Document>
     */
    public function getDocuments(): Collection
    {
        return Document::query()
            ->select('id', 'title', 'body')
            ->latest()
            ->get();
    }

    /**
     * @param  array{title: string, body: string}  $data
     *
     * @throws Throwable
     */
    public function storeDocument(array $data): Document
    {
        return Document::create($data);
    }
}
