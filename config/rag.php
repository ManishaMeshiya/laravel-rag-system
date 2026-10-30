<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Chunk Size
    |--------------------------------------------------------------------------
    |
    | The maximum number of characters in each chunk of extracted document
    | text. Overlap is the number of characters repeated from the previous
    | chunk so context is not lost at a boundary.
    |
    */

    'chunk_size' => (int) env('RAG_CHUNK_SIZE', 800),

    'chunk_overlap' => (int) env('RAG_CHUNK_OVERLAP', 100),

    /*
    |--------------------------------------------------------------------------
    | Embedding Dimensions
    |--------------------------------------------------------------------------
    |
    | The length of each embedding vector. This must match the dimension of
    | the document_chunks.embedding column.
    |
    */

    'embedding_dimensions' => (int) env('RAG_EMBEDDING_DIMENSIONS', 1536),

    /*
    |--------------------------------------------------------------------------
    | Retrieval
    |--------------------------------------------------------------------------
    |
    | How many chunks to return for a question, and the lowest cosine
    | similarity that still counts as a match. Similarity is between 0 and 1.
    |
    */

    'retrieval_limit' => (int) env('RAG_RETRIEVAL_LIMIT', 5),

    'min_similarity' => (float) env('RAG_MIN_SIMILARITY', 0.4),

];
