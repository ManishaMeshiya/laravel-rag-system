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

];
