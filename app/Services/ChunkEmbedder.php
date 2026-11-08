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
            fn (ChunkData $chunk): string => $this->normalize($chunk->content),
            $chunks,
        );

        $response = Embeddings::for($contents)
            ->dimensions((int) config('rag.embedding_dimensions'))
            ->generate();

        return $response->embeddings;
    }

    /**
     * @return list<float>
     */
    public function embedQuery(string $text): array
    {
        $response = Embeddings::for([$this->normalize($text)])
            ->dimensions((int) config('rag.embedding_dimensions'))
            ->generate();

        return $response->embeddings[0];
    }

    private function normalize(string $text): string
    {
        return mb_strtolower($text, 'UTF-8');
    }
}
