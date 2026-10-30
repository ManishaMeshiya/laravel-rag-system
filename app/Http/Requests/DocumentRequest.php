<?php

namespace App\Http\Requests;

use App\Data\StoreDocumentData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class DocumentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'file' => [
                'required',
                'file',
                'max:10240',
                'extensions:txt,md',
                'mimetypes:text/plain,text/markdown,text/x-markdown',
            ],
        ];
    }

    public function toData(): StoreDocumentData
    {
        $file = $this->file('file');

        if (! $file instanceof UploadedFile) {
            throw new RuntimeException('The uploaded document is missing.');
        }

        return new StoreDocumentData(
            title: $this->string('title')->toString(),
            file: $file,
        );
    }
}
