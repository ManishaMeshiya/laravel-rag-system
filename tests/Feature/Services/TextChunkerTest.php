<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Data\ChunkData;
use App\Services\TextChunker;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TextChunkerTest extends TestCase
{
    public function test_short_string_returns_one_chunk(): void
    {
        config([
            'rag.chunk_size' => 100,
            'rag.chunk_overlap' => 20,
        ]);

        $chunks = (new TextChunker)->chunk('hello');

        $this->assertCount(1, $chunks);
        $this->assertInstanceOf(ChunkData::class, $chunks[0]);
        $this->assertSame('hello', $chunks[0]->content);
        $this->assertSame(0, $chunks[0]->index);
    }

    public function test_string_equal_to_chunk_size_returns_one_chunk(): void
    {
        config([
            'rag.chunk_size' => 5,
            'rag.chunk_overlap' => 2,
        ]);

        $chunks = (new TextChunker)->chunk('abcde');

        $this->assertCount(1, $chunks);
        $this->assertSame('abcde', $chunks[0]->content);
        $this->assertSame(0, $chunks[0]->index);
    }

    public function test_longer_string_overlaps_by_the_configured_overlap(): void
    {
        config([
            'rag.chunk_size' => 5,
            'rag.chunk_overlap' => 2,
        ]);

        $chunks = (new TextChunker)->chunk('abcdefghij');

        $this->assertCount(3, $chunks);
        $this->assertSame('abcde', $chunks[0]->content);
        $this->assertSame(0, $chunks[0]->index);
        $this->assertSame('defgh', $chunks[1]->content);
        $this->assertSame(1, $chunks[1]->index);
        $this->assertSame('ghij', $chunks[2]->content);
        $this->assertSame(2, $chunks[2]->index);
    }

    public function test_empty_string_returns_no_chunks(): void
    {
        config([
            'rag.chunk_size' => 5,
            'rag.chunk_overlap' => 2,
        ]);

        $this->assertSame([], (new TextChunker)->chunk(''));
    }

    #[DataProvider('invalidOverlaps')]
    public function test_overlap_greater_than_or_equal_to_size_throws(int $overlap): void
    {
        config([
            'rag.chunk_size' => 5,
            'rag.chunk_overlap' => $overlap,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Chunk overlap must be smaller than chunk size.');

        (new TextChunker)->chunk('abcdefghij');
    }

    /**
     * @return array<string, array{int}>
     */
    public static function invalidOverlaps(): array
    {
        return [
            'equal to size' => [5],
            'greater than size' => [6],
        ];
    }
}
