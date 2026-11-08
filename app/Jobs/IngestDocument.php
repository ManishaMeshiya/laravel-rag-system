<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Services\ChunkEmbedder;
use App\Services\DocumentChunkStore;
use App\Services\DocumentTextExtractor;
use App\Services\TextChunker;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class IngestDocument implements ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(public Document $document) {}

    public function handle(
        DocumentTextExtractor $extractor,
        TextChunker $chunker,
        ChunkEmbedder $embedder,
        DocumentChunkStore $chunkStore,
    ): void {
        $chunks = $chunker->chunk($extractor->extract($this->document));
        $embeddings = $embedder->embed($chunks);

        $chunkStore->replace($this->document, $chunks, $embeddings);
        $this->document->update(['status' => DocumentStatus::Ready]);
    }

    public function failed(?Throwable $exception): void
    {
        $this->document->update(['status' => DocumentStatus::Failed]);
    }
}
