<?php

namespace App\Http\Controllers\Document;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentRequest;
use App\Models\Document;
use App\Services\AnswerService;
use App\Services\DocumentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentService $documentService,
        private AnswerService $answerService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Chat/Index', [
            'documents' => fn () => $this->documentService->getDocuments(),
            'messages' => fn () => $this->answerService->getHistory(),
        ]);
    }

    public function store(DocumentRequest $request): RedirectResponse
    {
        $this->documentService->storeDocument($request->toData());

        return redirect()->route('documents.index')->with('success', 'Document uploaded successfully');
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->documentService->deleteDocument($document);

        return redirect()->route('documents.index')->with('success', 'Document deleted successfully');
    }
}
