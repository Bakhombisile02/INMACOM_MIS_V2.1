<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class NavigationBuilder
{
    /** @var array<string, bool> */
    private array $tableAvailability = [];

    /** @var array<string, int> */
    private array $tableCounts = [];

    /**
     * Build role- and schema-aware navigation payload for the authenticated shell.
     *
     * @return array{main: array<int, array<string, mixed>>, bottom: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function build(?User $user): array
    {
        $items = [];

        foreach ($this->definitions() as $definition) {
            if (! $this->roleAllowed($definition['roles'], $user)) {
                continue;
            }

            if (! $this->tablesAvailable($definition['required_tables'])) {
                continue;
            }

            if (! Route::has($definition['route'])) {
                continue;
            }

            $items[] = [
                'id' => $definition['id'],
                'label' => $definition['label'],
                'icon' => $definition['icon'],
                'href' => route($definition['route']),
                'method' => $definition['method'],
                'group' => $definition['group'],
                'supportsGis' => $definition['supports_gis'],
                'badge' => $this->countFor($definition['count_table']),
            ];
        }

        return [
            'main' => array_values(array_filter($items, fn (array $item) => $item['group'] === 'main')),
            'bottom' => array_values(array_filter($items, fn (array $item) => $item['group'] === 'bottom')),
            'meta' => [
                'derivedFromDatabase' => true,
                'userRole' => $user?->role,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function definitions(): array
    {
        $allRoles = [
            User::ROLE_ADMIN,
            User::ROLE_MANAGER,
            User::ROLE_CLERK,
        ];

        return [
            [
                'id' => 'dashboard',
                'label' => 'dashboard',
                'icon' => 'dashboard',
                'route' => 'dashboard',
                'method' => 'get',
                'group' => 'main',
                'roles' => $allRoles,
                'required_tables' => [],
                'count_table' => null,
                'supports_gis' => false,
            ],
            [
                'id' => 'stations',
                'label' => 'stations',
                'icon' => 'stations',
                'route' => 'stations.index',
                'method' => 'get',
                'group' => 'main',
                'roles' => $allRoles,
                'required_tables' => ['stations'],
                'count_table' => 'stations',
                'supports_gis' => true,
            ],
            [
                'id' => 'thresholds',
                'label' => 'thresholds',
                'icon' => 'thresholds',
                'route' => 'thresholds.index',
                'method' => 'get',
                'group' => 'main',
                'roles' => [User::ROLE_ADMIN, User::ROLE_MANAGER],
                'required_tables' => ['compliance_thresholds'],
                'count_table' => 'compliance_thresholds',
                'supports_gis' => false,
            ],
            [
                'id' => 'users',
                'label' => 'users',
                'icon' => 'users',
                'route' => 'users.index',
                'method' => 'get',
                'group' => 'main',
                'roles' => [User::ROLE_ADMIN],
                'required_tables' => ['users'],
                'count_table' => 'users',
                'supports_gis' => false,
            ],
            [
                'id' => 'flow-levels',
                'label' => 'flowLevels',
                'icon' => 'flowLevels',
                'route' => 'flow-levels.index',
                'method' => 'get',
                'group' => 'main',
                'roles' => $allRoles,
                'required_tables' => ['measurements'],
                'count_table' => ['table' => 'measurements', 'where' => ['measurement_type' => 'flow'], 'distinct' => 'station_id'],
                'supports_gis' => false,
            ],
            [
                'id' => 'dam-levels',
                'label' => 'damLevels',
                'icon' => 'damLevels',
                'route' => 'dam-levels.index',
                'method' => 'get',
                'group' => 'main',
                'roles' => $allRoles,
                'required_tables' => ['measurements'],
                'count_table' => ['table' => 'measurements', 'where' => ['measurement_type' => 'dam_level'], 'distinct' => 'station_id'],
                'supports_gis' => false,
            ],
            [
                'id' => 'water-quality',
                'label' => 'waterQuality',
                'icon' => 'waterQuality',
                'route' => 'water-quality.index',
                'method' => 'get',
                'group' => 'main',
                'roles' => $allRoles,
                'required_tables' => ['water_quality_parameters', 'measurements'],
                'count_table' => 'water_quality_parameters',
                'supports_gis' => false,
            ],
            [
                'id' => 'rainfall',
                'label' => 'rainfall',
                'icon' => 'rainfall',
                'route' => 'rainfall.index',
                'method' => 'get',
                'group' => 'main',
                'roles' => $allRoles,
                'required_tables' => ['measurements'],
                'count_table' => ['table' => 'measurements', 'where' => ['measurement_type' => 'rainfall'], 'distinct' => 'station_id'],
                'supports_gis' => true,
            ],
            [
                'id' => 'groundwater',
                'label' => 'groundwater',
                'icon' => 'groundwater',
                'route' => 'groundwater.index',
                'method' => 'get',
                'group' => 'main',
                'roles' => $allRoles,
                'required_tables' => ['measurements'],
                'count_table' => ['table' => 'measurements', 'where' => ['measurement_type' => 'groundwater_level'], 'distinct' => 'station_id'],
                'supports_gis' => true,
            ],
            [
                'id' => 'disaster-management',
                'label' => 'disasterManagement',
                'icon' => 'disasterManagement',
                'route' => 'disaster.index',
                'method' => 'get',
                'group' => 'main',
                'roles' => $allRoles,
                'required_tables' => ['disaster_incidents'],
                'count_table' => null,
                'supports_gis' => true,
            ],
            [
                'id' => 'document-storage',
                'label' => 'documentStorage',
                'icon' => 'documentStorage',
                'route' => 'library',
                'method' => 'get',
                'group' => 'main',
                'roles' => $allRoles,
                'required_tables' => ['document_storages', 'documents'],
                'count_table' => 'documents',
                'supports_gis' => false,
            ],
            [
                'id' => 'audit-log',
                'label' => 'auditLog',
                'icon' => 'auditLog',
                'route' => 'audit.index',
                'method' => 'get',
                'group' => 'main',
                'roles' => [User::ROLE_ADMIN, User::ROLE_MANAGER],
                'required_tables' => ['audit_logs'],
                'count_table' => null,
                'supports_gis' => false,
            ],
            [
                'id' => 'profile',
                'label' => 'profile',
                'icon' => 'profile',
                'route' => 'profile',
                'method' => 'get',
                'group' => 'bottom',
                'roles' => $allRoles,
                'required_tables' => [],
                'count_table' => null,
                'supports_gis' => false,
            ],
            [
                'id' => 'settings',
                'label' => 'settings',
                'icon' => 'settings',
                'route' => 'settings.index',
                'method' => 'get',
                'group' => 'bottom',
                'roles' => $allRoles,
                'required_tables' => [],
                'count_table' => null,
                'supports_gis' => false,
            ],
            [
                'id' => 'logout',
                'label' => 'logout',
                'icon' => 'logout',
                'route' => 'logout',
                'method' => 'post',
                'group' => 'bottom',
                'roles' => $allRoles,
                'required_tables' => [],
                'count_table' => null,
                'supports_gis' => false,
            ],
        ];
    }

    /**
     * @param  array<int, string>  $roles
     */
    private function roleAllowed(array $roles, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return in_array($user->role, $roles, true);
    }

    /**
     * @param  array<int, string>  $requiredTables
     */
    private function tablesAvailable(array $requiredTables): bool
    {
        foreach ($requiredTables as $table) {
            if (! $this->hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function hasTable(string $table): bool
    {
        if (array_key_exists($table, $this->tableAvailability)) {
            return $this->tableAvailability[$table];
        }

        $this->tableAvailability[$table] = Schema::hasTable($table);

        return $this->tableAvailability[$table];
    }

    /**
     * @param  string|array{table: string, where: array<string, string>, distinct: string}|null  $table
     */
    private function countFor(string|array|null $table): ?int
    {
        if (! $table) {
            return null;
        }

        // Simple table count
        if (is_string($table)) {
            if (! $this->hasTable($table)) {
                return null;
            }

            if (array_key_exists($table, $this->tableCounts)) {
                return $this->tableCounts[$table];
            }

            $this->tableCounts[$table] = (int) DB::table($table)->count();

            return $this->tableCounts[$table];
        }

        // Filtered distinct count: ['table' => '...', 'where' => [...], 'distinct' => '...']
        $cacheKey = $table['table'].':'.serialize($table['where']).':'.($table['distinct'] ?? '');

        if (! $this->hasTable($table['table'])) {
            return null;
        }

        if (array_key_exists($cacheKey, $this->tableCounts)) {
            return $this->tableCounts[$cacheKey];
        }

        $query = DB::table($table['table']);

        foreach ($table['where'] as $column => $value) {
            $query->where($column, $value);
        }

        $count = isset($table['distinct'])
            ? (int) $query->distinct()->count($table['distinct'])
            : (int) $query->count();

        $this->tableCounts[$cacheKey] = $count;

        return $count;
    }
}
