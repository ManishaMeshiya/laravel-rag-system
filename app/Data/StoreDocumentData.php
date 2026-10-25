<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Http\UploadedFile;

readonly class StoreDocumentData
{
    public function __construct(
        public string $title,
        public UploadedFile $file,
    ) {}
}
