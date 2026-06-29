<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentStorage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LibraryController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $storages = DocumentStorage::orderBy('name')->get(['id', 'name', 'slug', 'visibility']);

        $documents = Document::with('storage:id,name,slug')
            ->latest()
            ->get()
            ->map(fn (Document $d) => [
                'id' => $d->id,
                'title' => $d->title,
                'description' => $d->description,
                'media_type' => $d->media_type,
                'visibility' => $d->visibility,
                'size_bytes' => $d->size_bytes,
                'mime_type' => $d->mime_type,
                'storage' => $d->storage ? ['id' => $d->storage->id, 'slug' => $d->storage->slug, 'name' => $d->storage->name] : null,
                'download_url' => route('library.documents.download', $d),
                'created_at' => $d->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Library/Index', [
            'storages' => $storages,
            'documents' => $documents,
            'canManage' => (bool) $user?->canManageDocuments(),
        ]);
    }
}
