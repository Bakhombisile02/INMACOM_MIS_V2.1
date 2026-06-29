<?php

namespace App\Http\Requests;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canManageDocuments();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:51200'], // 50 MB
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'media_type' => ['required', Rule::in(Document::MEDIA_TYPES)],
            'visibility' => ['required', Rule::in([Document::VISIBILITY_PUBLIC, Document::VISIBILITY_PRIVATE])],
            'storage_id' => ['nullable', 'uuid', 'exists:document_storages,id'],
        ];
    }
}
