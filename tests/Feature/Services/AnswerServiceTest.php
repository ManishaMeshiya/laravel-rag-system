<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Ai\Agents\KnowledgeBase;
use App\Data\RetrievedContext;
use App\Models\ChatMessage;
use App\Services\AnswerService;
use App\Services\ChunkRetriever;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Ai\Prompts\AgentPrompt;
use Tests\TestCase;

class AnswerServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_answer_is_generated_from_the_retrieved_chunks(): void
    {
        $vacation = new RetrievedContext(
            chunkId: 1,
            content: 'Employees receive 20 vacation days.',
            documentId: 2,
            documentTitle: 'Vacation policy',
        );
        $approval = new RetrievedContext(
            chunkId: 3,
            content: 'Vacation requests need manager approval.',
            documentId: 2,
            documentTitle: 'Vacation policy',
        );

        $this->mock(ChunkRetriever::class)
            ->shouldReceive('retrieve')
            ->once()
            ->with('How many vacation days?')
            ->andReturn([$vacation, $approval]);

        KnowledgeBase::fake(['Employees receive 20 vacation days.']);

        $answer = app(AnswerService::class)->answer('How many vacation days?');

        $this->assertSame('Employees receive 20 vacation days.', $answer->text);
        $this->assertCount(2, $answer->sources);
        $this->assertSame('Vacation policy', $answer->sources[0]->documentTitle);
        $this->assertSame('Employees receive 20 vacation days.', $answer->sources[0]->excerpt);
        $this->assertDatabaseHas('chat_messages', [
            'question' => 'How many vacation days?',
            'answer' => 'Employees receive 20 vacation days.',
        ]);
        $this->assertSame([
            'document_id' => 2,
            'document_title' => 'Vacation policy',
            'excerpt' => 'Employees receive 20 vacation days.',
        ], ChatMessage::query()->sole()->sources[0]);
        KnowledgeBase::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Employees receive 20 vacation days.')
            && $prompt->contains('Vacation requests need manager approval.')
            && $prompt->contains('How many vacation days?'));
    }

    public function test_returns_i_dont_know_when_no_chunks_match(): void
    {
        $this->mock(ChunkRetriever::class)
            ->shouldReceive('retrieve')
            ->once()
            ->with('What is the office address?')
            ->andReturn([]);

        KnowledgeBase::fake()->preventStrayPrompts();

        $answer = app(AnswerService::class)->answer('What is the office address?');

        $this->assertSame("I don't know", $answer->text);
        $this->assertSame([], $answer->sources);
        $this->assertDatabaseHas('chat_messages', [
            'question' => 'What is the office address?',
            'answer' => "I don't know",
        ]);
        $this->assertSame([], ChatMessage::query()->sole()->sources);
        KnowledgeBase::assertNeverPrompted();
    }

    public function test_history_is_returned_in_chronological_order(): void
    {
        ChatMessage::factory()->create([
            'question' => 'Second question',
            'created_at' => '2026-01-02 00:00:00',
        ]);
        ChatMessage::factory()->create([
            'question' => 'First question',
            'created_at' => '2026-01-01 00:00:00',
        ]);

        $history = app(AnswerService::class)->getHistory();

        $this->assertSame(
            ['First question', 'Second question'],
            $history->pluck('question')->all(),
        );
    }
}
