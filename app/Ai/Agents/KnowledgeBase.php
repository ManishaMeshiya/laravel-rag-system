<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::OpenAI)]
#[Temperature(0.0)]
#[Model('gpt-5.6')]
#[MaxTokens(1000)]
class KnowledgeBase implements Agent, HasProviderOptions
{
    use Promptable;

    public function instructions(): string
    {
        return 'Answer only from the provided context. If the context does not contain the answer, say only "I don\'t know". '
            .'Write the answer as plain text, without Markdown formatting.';
    }

    public function providerOptions(Lab|string $provider): array
    {
        return match ($provider) {
            Lab::OpenAI => ['reasoning' => ['effort' => 'none']],
            default => [],
        };
    }
}
