<?php

namespace App\Queries;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

class AuditLogQuery
{
    protected $query;

    public function __construct()
    {
        $this->query = AuditLog::query();
    }

    /**
     * Start a fluent audit log query.
     */
    public static function query(): self
    {
        return new self;
    }

    /**
     * Filter by action type.
     */
    public function forActionType(?string $actionType): self
    {
        if ($actionType) {
            $this->query->where('action_type', $actionType);
        }

        return $this;
    }

    /**
     * Filter by entity type.
     */
    public function forEntityType(?string $entityType): self
    {
        if ($entityType) {
            $this->query->where('entity_type', $entityType);
        }

        return $this;
    }

    /**
     * Filter by actor ID.
     */
    public function forActor(?string $actorId): self
    {
        if ($actorId) {
            $this->query->where('actor_id', $actorId);
        }

        return $this;
    }

    /**
     * Filter by occurred_at date range.
     */
    public function forDateRange(?Carbon $from, ?Carbon $to): self
    {
        if ($from) {
            $this->query->where('occurred_at', '>=', $from);
        }
        if ($to) {
            $this->query->where('occurred_at', '<=', $to);
        }

        return $this;
    }

    /**
     * Eager load the actor relation.
     */
    public function withActor(): self
    {
        $this->query->with('actor:id,display_name,email,photo_url,role');

        return $this;
    }

    /**
     * Paginate results.
     */
    public function paginate(int $perPage = 50): LengthAwarePaginator
    {
        return $this->query->orderByDesc('occurred_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Stream results via lazy cursor.
     */
    public function cursor(): LazyCollection
    {
        return $this->query->orderByDesc('occurred_at')->cursor();
    }

    /**
     * Retrieve summary statistics for audit logs.
     */
    public static function getSummaryStats(): array
    {
        $todayCount = AuditLog::whereDate('occurred_at', today())->count();

        $selfApprovalCount = AuditLog::where('action_type', 'self_approval')
            ->whereYear('occurred_at', now()->year)
            ->whereMonth('occurred_at', now()->month)
            ->count();

        $roleChangeCount = AuditLog::where('action_type', 'role_change')->count();

        $mostActive = AuditLog::selectRaw('actor_id, count(*) as total')
            ->whereNotNull('actor_id')
            ->whereYear('occurred_at', now()->year)
            ->whereMonth('occurred_at', now()->month)
            ->groupBy('actor_id')
            ->orderByDesc('total')
            ->first();

        $mostActiveUser = $mostActive ? User::find($mostActive->actor_id) : null;

        return [
            'today_count' => $todayCount,
            'self_approval_count' => $selfApprovalCount,
            'role_change_count' => $roleChangeCount,
            'most_active_name' => $mostActiveUser?->display_name,
        ];
    }

    /**
     * Retrieve unique list of actors who have logged events.
     */
    public static function getUniqueActors(): Collection
    {
        return AuditLog::selectRaw('actor_id')
            ->distinct()
            ->with('actor:id,display_name,email')
            ->get()
            ->map(fn ($row) => $row->actor)
            ->filter()
            ->values()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'display_name' => $u->display_name,
                'email' => $u->email,
            ]);
    }

    /**
     * Sanitize values for CSV cells to prevent injection.
     */
    public static function sanitizeCsvCell(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (preg_match('/^[=+\-@]/', $value)) {
            return "'".$value;
        }

        return $value;
    }
}
