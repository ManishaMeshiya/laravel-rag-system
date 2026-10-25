<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\ChunkData;
use App\Models\Document;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DocumentChunkStore
{
    /**
     * @param  list<ChunkData>  $chunks
     * @param  list<array<int, float>>  $embeddings
     */
    public function replace(Document $document, array $chunks, array $embeddings): void
    {
        if (count($embeddings) !== count($chunks)) {
            throw new RuntimeException('The embedding count does not match the chunk count.');
        }

        if ($chunks === []) {
            $document->chunks()->delete();

            return;
        }

        DB::transaction(fn () => $this->writeChunks($document, $chunks, $embeddings));
    }

    /**
     * @param  list<ChunkData>  $chunks
     * @param  list<array<int, float>>  $embeddings
     */
    private function writeChunks(Document $document, array $chunks, array $embeddings): void
    {
        $document->chunks()->delete();

        foreach ($chunks as $index => $chunk) {
            $document->chunks()->create([
                'content' => $chunk->content,
                'embedding' => $embeddings[$index],
            ]);
        }
    }
}
