<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

class KnowledgeBase implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'Answer only from the provided context. If the context does not contain the answer, say "I don\'t know".';
    }
}
