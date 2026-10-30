<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\RetrievedContext;
use App\Models\DocumentChunk;

class ChunkRetriever
{
    public function __construct(private ChunkEmbedder $embedder) {}

    /**
     * @return list<RetrievedContext>
     */
    public function retrieve(string $question): array
    {
        $chunks = DocumentChunk::query()
            ->with('document:id,title')
            ->whereVectorSimilarTo(
                'embedding',
                $this->embedder->embedQuery($question),
                minSimilarity: (float) config('rag.min_similarity'),
            )
            ->limit((int) config('rag.retrieval_limit'))
            ->get();

        return $chunks
            ->map(fn (DocumentChunk $chunk): RetrievedContext => new RetrievedContext(
                chunkId: $chunk->id,
                content: $chunk->content,
                documentId: $chunk->document_id,
                documentTitle: $chunk->document->title,
            ))
            ->values()
            ->all();
    }
}
