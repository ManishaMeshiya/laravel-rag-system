<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Question;

use App\Data\AnswerData;
use App\Services\AnswerService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class QuestionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_store_returns_the_answer(): void
    {
        $this->mock(AnswerService::class)
            ->shouldReceive('answer')
            ->once()
            ->with('How many vacation days?')
            ->andReturn(new AnswerData(
                text: 'Employees receive 20 vacation days.',
            ));

        $this->from(route('documents.index'))
            ->post(route('questions.store'), [
                'question' => 'How many vacation days?',
            ])
            ->assertRedirectToRoute('documents.index')
            ->assertSessionHas('answer', [
                'question' => 'How many vacation days?',
                'text' => 'Employees receive 20 vacation days.',
            ]);

        $this->get(route('documents.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Chat/Index')
                ->where('flash.answer.question', 'How many vacation days?')
                ->where('flash.answer.text', 'Employees receive 20 vacation days.')
                ->missing('flash.answer.sources'));
    }

    public function test_store_rejects_a_missing_question(): void
    {
        $this->from(route('documents.index'))
            ->post(route('questions.store'), [])
            ->assertRedirectToRoute('documents.index')
            ->assertInvalid([
                'question' => 'The question field is required.',
            ]);
    }

    public function test_store_rejects_a_question_longer_than_2000_characters(): void
    {
        $this->from(route('documents.index'))
            ->post(route('questions.store'), [
                'question' => str_repeat('a', 2001),
            ])
            ->assertRedirectToRoute('documents.index')
            ->assertInvalid([
                'question' => 'The question field must not be greater than 2000 characters.',
            ]);
    }
}
