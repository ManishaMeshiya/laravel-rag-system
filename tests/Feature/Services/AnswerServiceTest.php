<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Ai\Agents\KnowledgeBase;
use App\Data\RetrievedContext;
use App\Services\AnswerService;
use App\Services\ChunkRetriever;
use Laravel\Ai\Prompts\AgentPrompt;
use Tests\TestCase;

class AnswerServiceTest extends TestCase
{
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
        KnowledgeBase::assertNeverPrompted();
    }
}
