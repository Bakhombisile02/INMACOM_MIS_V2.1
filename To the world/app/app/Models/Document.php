<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory, HasUuids;

    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_PRIVATE = 'private';

    public const MEDIA_DOCUMENTS = 'documents';

    public const MEDIA_IMAGES = 'images';

    public const MEDIA_VIDEOS = 'videos';

    public const MEDIA_AUDIO = 'audio';

    public const MEDIA_ARCHIVES = 'archives';

    public const MEDIA_TYPES = [
        self::MEDIA_DOCUMENTS,
        self::MEDIA_IMAGES,
        self::MEDIA_VIDEOS,
        self::MEDIA_AUDIO,
        self::MEDIA_ARCHIVES,
    ];

    protected $fillable = [
        'storage_id',
        'title',
        'description',
        'file_path',
        'disk',
        'mime_type',
        'size_bytes',
        'media_type',
        'visibility',
        'uploaded_by',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    public function storage(): BelongsTo
    {
        return $this->belongsTo(DocumentStorage::class, 'storage_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', self::VISIBILITY_PUBLIC);
    }

    public function scopePrivate($query)
    {
        return $query->where('visibility', self::VISIBILITY_PRIVATE);
    }

    public function scopeMediaType($query, string $type)
    {
        return $query->where('media_type', $type);
    }
}
