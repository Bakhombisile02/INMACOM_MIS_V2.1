<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class RegistrationPin extends Model
{
    use HasFactory, HasUuids;

    /** Characters used to generate PINs (uppercase, no ambiguous chars). */
    private const PIN_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    protected $fillable = [
        'code',
        'role',
        'created_by',
        'used_by',
        'used_at',
        'expires_at',
        'revoked_at',
        'reserved_for_email',
        'reserved_for_uid',
        'reserved_at',
        'note',
        'link_token',
        'link_opened_at',
        'sent_to_email',
    ];

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'reserved_at' => 'datetime',
            'link_opened_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now());
            });
    }

    public function isAvailable(): bool
    {
        if ($this->used_at !== null || $this->revoked_at !== null) {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    public function markUsedBy(User $user): void
    {
        $this->forceFill([
            'used_by' => $user->getKey(),
            'used_at' => Carbon::now(),
        ])->save();
    }

    /**
     * Generate a unique 6-character alphanumeric code (uppercase, no ambiguous chars).
     */
    public static function generateUniqueCode(): string
    {
        for ($i = 0; $i < 10; $i++) {
            $code = '';
            $max = strlen(self::PIN_ALPHABET) - 1;
            for ($j = 0; $j < 6; $j++) {
                $code .= self::PIN_ALPHABET[random_int(0, $max)];
            }
            if (! self::query()->where('code', $code)->exists()) {
                return $code;
            }
        }

        // Extremely unlikely fallback — append random suffix.
        return strtoupper(Str::random(6));
    }
}
