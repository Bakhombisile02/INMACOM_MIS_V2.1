<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @description Handles private library document uploads and authorised file downloads.
 * @author ITPTC / Datamatics
 * @since 2.1.0
 */
class DocumentUploadController extends Controller
{
    /**
     * Store an uploaded file as a Document row.
     */
    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $disk = $data['visibility'] === Document::VISIBILITY_PRIVATE ? 'local' : 'public';

        $path = $request->file('file')->store('documents', $disk);

        Document::create([
            'storage_id' => $data['storage_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'file_path' => $path,
            'disk' => $disk,
            'mime_type' => $request->file('file')->getMimeType(),
            'size_bytes' => $request->file('file')->getSize(),
            'media_type' => $data['media_type'],
            'visibility' => $data['visibility'],
            'uploaded_by' => $request->user()?->id,
        ]);

        return back()->with('status', 'document.uploaded');
    }

    /**
     * Stream a document to an authenticated user (private files included).
     */
    public function download(Request $request, Document $document): StreamedResponse|Response
    {
        // Public files: anyone may download. Private: must be logged in.
        if ($document->visibility === Document::VISIBILITY_PRIVATE && ! $request->user()) {
            abort(403);
        }

        $disk = Storage::disk($document->disk);
        if (! $disk->exists($document->file_path)) {
            abort(404);
        }

        $filename = basename($document->file_path);

        return $disk->download($document->file_path, $filename);
    }
}
