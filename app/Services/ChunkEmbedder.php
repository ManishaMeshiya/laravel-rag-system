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

        $batchSize = max(1, (int) config('rag.embedding_batch_size'));

        return array_merge(...array_map(
            fn (array $batch): array => $this->generate($batch),
            array_chunk($contents, $batchSize),
        ));
    }

    /**
     * @return list<float>
     */
    public function embedQuery(string $text): array
    {
        return $this->generate([$this->normalize($text)])[0];
    }

    /**
     * @param  list<string>  $inputs
     * @return list<array<int, float>>
     */
    private function generate(array $inputs): array
    {
        return Embeddings::for($inputs)
            ->dimensions((int) config('rag.embedding_dimensions'))
            ->generate()
            ->embeddings;
    }

    private function normalize(string $text): string
    {
        return mb_strtolower($text, 'UTF-8');
    }
}
