<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Document;
use App\Models\DocumentChunk;
use App\Services\ChunkRetriever;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;
use Tests\TestCase;

class ChunkRetrieverTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_the_matching_chunk_and_its_document_title(): void
    {
        config([
            'rag.embedding_dimensions' => 1536,
            'rag.min_similarity' => 0.4,
            'rag.retrieval_limit' => 5,
        ]);

        $match = Document::factory()->create(['title' => 'Vacation policy']);
        $other = Document::factory()->create(['title' => 'Office map']);

        $chunk = DocumentChunk::factory()->for($match)->create([
            'content' => 'Employees receive 20 vacation days.',
            'embedding' => $this->vector(0),
        ]);

        DocumentChunk::factory()->for($other)->create([
            'content' => 'The office is on the third floor.',
            'embedding' => $this->vector(1),
        ]);

        Embeddings::fake([[$this->vector(0)]]);

        $contexts = app(ChunkRetriever::class)->retrieve('How many vacation days?');

        $this->assertCount(1, $contexts);
        $this->assertSame($chunk->id, $contexts[0]->chunkId);
        $this->assertSame('Employees receive 20 vacation days.', $contexts[0]->content);
        $this->assertSame($match->id, $contexts[0]->documentId);
        $this->assertSame('Vacation policy', $contexts[0]->documentTitle);
        Embeddings::assertGenerated(fn (EmbeddingsPrompt $prompt): bool => $prompt->inputs === ['How many vacation days?']
            && $prompt->dimensions === 1536);
    }

    public function test_returns_the_closest_chunks_up_to_the_configured_limit(): void
    {
        config([
            'rag.embedding_dimensions' => 1536,
            'rag.min_similarity' => 0.4,
            'rag.retrieval_limit' => 1,
        ]);

        $document = Document::factory()->create(['title' => 'Handbook']);

        $closer = DocumentChunk::factory()->for($document)->create([
            'content' => 'Employees receive 20 vacation days.',
            'embedding' => $this->vector(0),
        ]);

        $farther = $this->vector(0);
        $farther[1] = 1.0;

        DocumentChunk::factory()->for($document)->create([
            'content' => 'Vacation requests need manager approval.',
            'embedding' => $farther,
        ]);

        Embeddings::fake([[$this->vector(0)]]);

        $contexts = app(ChunkRetriever::class)->retrieve('How many vacation days?');

        $this->assertCount(1, $contexts);
        $this->assertSame($closer->id, $contexts[0]->chunkId);
        $this->assertSame('Employees receive 20 vacation days.', $contexts[0]->content);
    }

    /**
     * @return list<float>
     */
    private function vector(int $axis): array
    {
        $vector = array_fill(0, 1536, 0.0);
        $vector[$axis] = 1.0;

        return $vector;
    }
}
