<?php

namespace App\Http\Controllers\Document;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentRequest;
use App\Services\DocumentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    public function __construct(private DocumentService $documentService) {}

    public function index(): Response
    {
        return Inertia::render('Chat/Index', [
            'documents' => $this->documentService->getDocuments(),
        ]);
    }

    public function store(DocumentRequest $request): RedirectResponse
    {
        try {
            $this->documentService->storeDocument($request->validated());

            return redirect()->route('documents.index')->with('success', 'Document uploaded successfully');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('documents.index')->with('error', 'Failed to upload document');
        }
    }
}
