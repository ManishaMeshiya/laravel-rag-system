<?php

declare(strict_types=1);

namespace App\Services;

use App\Ai\Agents\KnowledgeBase;
use App\Data\AnswerData;
use App\Data\AnswerSourceData;
use App\Data\RetrievedContext;
use App\Models\ChatMessage;
use Illuminate\Database\Eloquent\Collection;

class AnswerService
{
    public function __construct(private ChunkRetriever $retriever) {}

    public function answer(string $question): AnswerData
    {
        $sources = $this->retriever->retrieve($question);

        if ($sources === []) {
            return $this->storeAnswer($question, new AnswerData(text: "I don't know"));
        }

        $response = (new KnowledgeBase)->prompt($this->prompt($question, $sources));

        return $this->storeAnswer($question, new AnswerData(
            text: $response->text,
            sources: array_map(
                fn (RetrievedContext $source): AnswerSourceData => new AnswerSourceData(
                    documentId: $source->documentId,
                    documentTitle: $source->documentTitle,
                    excerpt: $source->content,
                ),
                $sources,
            ),
        ));
    }

    /**
     * @return Collection<int, ChatMessage>
     */
    public function getHistory(): Collection
    {
        return ChatMessage::query()
            ->select('id', 'question', 'answer', 'sources', 'created_at')
            ->latest()
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();
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

    private function storeAnswer(string $question, AnswerData $answer): AnswerData
    {
        ChatMessage::create([
            'question' => $question,
            'answer' => $answer->text,
            'sources' => array_map(
                fn (AnswerSourceData $source): array => $source->toArray(),
                $answer->sources,
            ),
        ]);

        return $answer;
    }
}
