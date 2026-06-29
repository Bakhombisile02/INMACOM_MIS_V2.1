<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentStorage;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PublicDocumentsController extends Controller
{
    public function index(): Response
    {
        $storages = DocumentStorage::public()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $documents = Document::public()
            ->with('storage:id,name,slug')
            ->latest()
            ->get()
            ->map(fn (Document $d) => [
                'id' => $d->id,
                'title' => $d->title,
                'description' => $d->description,
                'media_type' => $d->media_type,
                'size_bytes' => $d->size_bytes,
                'mime_type' => $d->mime_type,
                'storage' => $d->storage ? ['id' => $d->storage->id, 'slug' => $d->storage->slug, 'name' => $d->storage->name] : null,
                'download_url' => Storage::disk($d->disk)->url($d->file_path),
                'created_at' => $d->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Documents/Index', [
            'storages' => $storages,
            'documents' => $documents,
        ]);
    }
}
