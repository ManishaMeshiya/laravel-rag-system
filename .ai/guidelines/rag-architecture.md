# RAG Architecture

Flow: Upload → Extract text → Chunk → Embed → Store → Retrieve → Generate answer

## Layers
- Controllers are thin: validate (FormRequest), call an Action/Service, return a response.
- Business logic lives in `app/Services` or `app/Actions`.
- Use DTOs between layers (e.g. ChunkData, RetrievedContext, AnswerData).
- Embedding and answer generation use the Laravel AI SDK. Do not call
  provider HTTP APIs directly.
- Retrieval uses `whereVectorSimilarTo`. Do not write raw vector SQL.
- Document ingestion (chunk + embed) runs in a queued Job, never in the request.

## Rules
- Chunk size and overlap come from `config/rag.php`, not hardcoded.
- The answer prompt must say: answer only from the provided context;
  if the context does not contain the answer, say "I don't know".