<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Data\ChunkData;
use App\Services\ChunkEmbedder;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;
use Tests\TestCase;

class ChunkEmbedderTest extends TestCase
{
    public function test_empty_list_returns_no_vectors(): void
    {
        Embeddings::fake()->preventStrayEmbeddings();

        $embeddings = (new ChunkEmbedder)->embed([]);

        $this->assertSame([], $embeddings);
        Embeddings::assertNothingGenerated();
    }

    public function test_one_chunk_returns_its_embedding(): void
    {
        config(['rag.embedding_dimensions' => 2]);

        $vector = [0.1, 0.2];

        Embeddings::fake([[$vector]]);

        $embeddings = (new ChunkEmbedder)->embed([
            new ChunkData(content: 'Employees receive 20 vacation days.', index: 0),
        ]);

        $this->assertSame([$vector], $embeddings);
        Embeddings::assertGenerated(fn (EmbeddingsPrompt $prompt): bool => $prompt->contains('Employees receive 20 vacation days.')
            && $prompt->dimensions === 2);
    }

    public function test_chunks_keep_their_input_order(): void
    {
        config(['rag.embedding_dimensions' => 1]);

        Embeddings::fake(function (EmbeddingsPrompt $prompt): array {
            return array_map(
                fn (string $input): array => $input === 'alpha' ? [0.1] : [0.9],
                $prompt->inputs,
            );
        });

        $embeddings = (new ChunkEmbedder)->embed([
            new ChunkData(content: 'alpha', index: 0),
            new ChunkData(content: 'beta', index: 1),
        ]);

        $this->assertSame([[0.1], [0.9]], $embeddings);
        Embeddings::assertGenerated(fn (EmbeddingsPrompt $prompt): bool => $prompt->inputs === ['alpha', 'beta']);
    }

    public function test_query_returns_one_embedding_at_the_configured_dimensions(): void
    {
        config(['rag.embedding_dimensions' => 2]);

        Embeddings::fake([[[0.3, 0.4]]]);

        $embedding = (new ChunkEmbedder)->embedQuery('How many vacation days?');

        $this->assertSame([0.3, 0.4], $embedding);
        Embeddings::assertGenerated(fn (EmbeddingsPrompt $prompt): bool => $prompt->inputs === ['How many vacation days?']
            && $prompt->dimensions === 2);
    }
}
