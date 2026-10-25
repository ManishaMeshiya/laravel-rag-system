<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\ChunkData;
use Laravel\Ai\Embeddings;

class ChunkEmbedder
{
    /**
     * @param  list<ChunkData>  $chunks
     * @return list<array<int, float>>
     */
    public function embed(array $chunks): array
    {
        if ($chunks === []) {
            return [];
        }

        $contents = array_map(
            fn (ChunkData $chunk): string => $chunk->content,
            $chunks,
        );

        $response = Embeddings::for($contents)
            ->dimensions((int) config('rag.embedding_dimensions'))
            ->generate();

        return $response->embeddings;
    }
}
