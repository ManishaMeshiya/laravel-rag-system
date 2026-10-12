<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
                'extensions:txt,md,pdf',
                'mimetypes:text/plain,text/markdown,text/x-markdown,application/pdf',
            ],
        ];
    }
}
