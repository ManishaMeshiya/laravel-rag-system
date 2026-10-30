<?php

declare(strict_types=1);

namespace App\Services;

use App\Ai\Agents\KnowledgeBase;
use App\Data\AnswerData;
use App\Data\RetrievedContext;

class AnswerService
{
    public function __construct(private ChunkRetriever $retriever) {}

    public function answer(string $question): AnswerData
    {
        $sources = $this->retriever->retrieve($question);

        if ($sources === []) {
            return new AnswerData(text: "I don't know", sources: []);
        }

        $response = (new KnowledgeBase)->prompt($this->prompt($question, $sources));

        return new AnswerData(text: $response->text, sources: $sources);
    }

    /**
     * @param  list<RetrievedContext>  $sources
     */
    private function prompt(string $question, array $sources): string
    {
        $context = implode("\n\n", array_map(
            fn (RetrievedContext $source): string => $source->content,
            $sources,
        ));

        return "Context:\n{$context}\n\nQuestion: {$question}";
    }
}
