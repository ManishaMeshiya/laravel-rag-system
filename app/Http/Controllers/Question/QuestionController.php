<?php

declare(strict_types=1);

namespace App\Http\Controllers\Question;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuestionRequest;
use App\Services\AnswerService;
use Illuminate\Http\RedirectResponse;

class QuestionController extends Controller
{
    public function __construct(private AnswerService $answerService) {}

    public function store(QuestionRequest $request): RedirectResponse
    {
        $this->answerService->answer($request->question());

        return redirect()->route('documents.index');
    }
}
