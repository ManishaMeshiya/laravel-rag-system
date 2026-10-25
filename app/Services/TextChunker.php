<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\ChunkData;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TextChunker
{
    /**
     * @return list<ChunkData>
     *
     * @throws InvalidArgumentException
     */
    public function chunk(string $text): array
    {
        if ($text === '') {
            return [];
        }

        $size = (int) config('rag.chunk_size');
        $overlap = (int) config('rag.chunk_overlap');

        if ($size < 1) {
            throw new InvalidArgumentException('Chunk size must be at least 1.');
        }

        if ($overlap < 0 || $overlap >= $size) {
            throw new InvalidArgumentException('Chunk overlap must be smaller than chunk size.');
        }

        return $this->windows($text, $size, $size - $overlap);
    }

    /**
     * @return list<ChunkData>
     */
    private function windows(string $text, int $size, int $step): array
    {
        $chunks = [];
        $length = Str::length($text);

        for ($offset = 0; $offset < $length; $offset += $step) {
            $chunks[] = new ChunkData(
                content: Str::substr($text, $offset, $size),
                index: count($chunks),
            );

            if ($offset + $size >= $length) {
                break;
            }
        }

        return $chunks;
    }
}
