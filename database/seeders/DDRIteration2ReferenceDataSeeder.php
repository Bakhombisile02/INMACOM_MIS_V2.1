<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DDRIteration2ReferenceDataSeeder extends Seeder
{
    /**
     * Seed DDR Iteration 2 reference data.
     */
    public function run(): void
    {
        $this->seedIimaUserCategories();
        $this->seedIimaEflowKeyPoints();
        $this->seedIimaAllocations();
        $this->seedComplianceThresholds();
        $this->flagPriorityPollutants();
    }

    private function seedIimaUserCategories(): void
    {
        if (! Schema::hasTable('iima_user_categories')) {
            return;
        }

        $categories = [
            ['code' => 'domestic', 'name' => 'Domestic & Municipal', 'priority_order' => 1],
            ['code' => 'irrigation', 'name' => 'Irrigation Agriculture', 'priority_order' => 2],
            ['code' => 'industrial', 'name' => 'Industry & Mining', 'priority_order' => 3],
            ['code' => 'forestry', 'name' => 'Commercial Forestry', 'priority_order' => 4],
            ['code' => 'other', 'name' => 'Other Water Uses', 'priority_order' => 5],
        ];

        foreach ($categories as $cat) {
            $existingId = DB::table('iima_user_categories')
                ->where('code', $cat['code'])
                ->value('id');

            DB::table('iima_user_categories')->updateOrInsert(
                ['code' => $cat['code']],
                [
                    'id' => $existingId ?? (string) Str::uuid(),
                    'name' => $cat['name'],
                    'priority_order' => $cat['priority_order'],
                ]
            );
        }
    }

    private function seedIimaEflowKeyPoints(): void
    {
        if (! Schema::hasTable('iima_eflow_key_points')) {
            return;
        }

        $rows = [
            [
                'code' => 'E-23',
                'alternate_code' => 'X2H036',
                'name' => 'Ressano Garcia',
                'river' => 'Incomati River',
                'country' => 'Mozambique',
                'subcatchment_code' => null,
                'station_code' => null,
                'latitude' => null,
                'longitude' => null,
                'is_active' => true,
                'note' => 'Dual-code key point from DDR Iteration 2.',
            ],
        ];

        foreach ($rows as $row) {
            $subcatchmentId = null;
            if (! empty($row['subcatchment_code']) && Schema::hasTable('management_areas')) {
                $subcatchmentId = DB::table('management_areas')
                    ->where('code', $row['subcatchment_code'])
                    ->value('id');
            }

            $stationId = null;
            if (! empty($row['station_code']) && Schema::hasTable('stations')) {
                $stationId = DB::table('stations')
                    ->where('code', $row['station_code'])
                    ->value('id');
            }

            $existingId = DB::table('iima_eflow_key_points')
                ->where('code', $row['code'])
                ->value('id');

            DB::table('iima_eflow_key_points')->updateOrInsert(
                ['code' => $row['code']],
                [
                    'id' => $existingId ?? (string) Str::uuid(),
                    'alternate_code' => $row['alternate_code'],
                    'name' => $row['name'],
                    'river' => $row['river'],
                    'country' => $row['country'],
                    'subcatchment_id' => $subcatchmentId,
                    'station_id' => $stationId,
                    'latitude' => $row['latitude'],
                    'longitude' => $row['longitude'],
                    'is_active' => $row['is_active'],
                    'note' => $row['note'],
                ]
            );
        }
    }

    private function seedIimaAllocations(): void
    {
        if (! Schema::hasTable('iima_allocations')) {
            return;
        }

        $allocations = [
            [
                'subcatchment_code' => 'INC-KOMATI',
                'country' => 'South Africa',
                'user_category' => 'irrigation',
                'allocation_mm3_a' => 220.0,
                'effective_from' => 2026,
                'note' => 'IIMA Table 4-1 default allocation',
            ],
            [
                'subcatchment_code' => 'INC-KOMATI',
                'country' => 'Eswatini',
                'user_category' => 'irrigation',
                'allocation_mm3_a' => 120.0,
                'effective_from' => 2026,
                'note' => 'IIMA Table 4-1 default allocation',
            ],
            [
                'subcatchment_code' => 'MAP-USUTHU',
                'country' => 'Eswatini',
                'user_category' => 'domestic',
                'allocation_mm3_a' => 15.5,
                'effective_from' => 2026,
                'note' => 'IAAP-10 reference allocation',
            ],
            [
                'subcatchment_code' => 'MAP-USUTHU',
                'country' => 'Mozambique',
                'user_category' => 'irrigation',
                'allocation_mm3_a' => 45.0,
                'effective_from' => 2026,
                'note' => 'IAAP-10 reference allocation',
            ],
        ];

        foreach ($allocations as $allocation) {
            if (empty($allocation['subcatchment_code']) || empty($allocation['user_category'])) {
                continue;
            }

            $subcatchmentId = DB::table('management_areas')
                ->where('code', $allocation['subcatchment_code'])
                ->value('id');

            if (! $subcatchmentId) {
                continue;
            }

            $existingId = DB::table('iima_allocations')
                ->where('subcatchment_id', $subcatchmentId)
                ->where('country', $allocation['country'])
                ->where('user_category', $allocation['user_category'])
                ->where('effective_from', (int) $allocation['effective_from'])
                ->value('id');

            DB::table('iima_allocations')->updateOrInsert(
                [
                    'subcatchment_id' => $subcatchmentId,
                    'country' => $allocation['country'],
                    'user_category' => $allocation['user_category'],
                    'effective_from' => (int) $allocation['effective_from'],
                ],
                [
                    'id' => $existingId ?? (string) Str::uuid(),
                    'allocation_mm3_a' => (float) $allocation['allocation_mm3_a'],
                    'note' => $allocation['note'] ?? null,
                ]
            );
        }
    }

    private function seedComplianceThresholds(): void
    {
        if (! Schema::hasTable('compliance_thresholds')) {
            return;
        }

        $thresholds = [
            // TODO: Populate from REIWQ Appendix A once final values are approved.
            // [
            //     'station_code' => 'RG-01',
            //     'data_type' => 'water_quality',
            //     'parameter_code' => 'NO3',
            //     'min_value' => null,
            //     'max_value' => 0.0,
            //     'unit' => 'mg/L',
            //     'notes' => 'REIWQ Appendix A',
            // ],
        ];

        foreach ($thresholds as $threshold) {
            if (
                empty($threshold['station_code'])
                || empty($threshold['parameter_code'])
                || empty($threshold['data_type'])
            ) {
                continue;
            }

            $stationId = DB::table('stations')
                ->where('code', $threshold['station_code'])
                ->value('id');

            $parameterId = DB::table('water_quality_parameters')
                ->where('code', $threshold['parameter_code'])
                ->value('id');

            if (! $stationId || ! $parameterId) {
                continue;
            }

            $existingId = DB::table('compliance_thresholds')
                ->where('station_id', $stationId)
                ->where('data_type', $threshold['data_type'])
                ->where('parameter_id', $parameterId)
                ->value('id');

            DB::table('compliance_thresholds')->updateOrInsert(
                [
                    'station_id' => $stationId,
                    'data_type' => $threshold['data_type'],
                    'parameter_id' => $parameterId,
                ],
                [
                    'id' => $existingId ?? (string) Str::uuid(),
                    'min_value' => $threshold['min_value'],
                    'max_value' => $threshold['max_value'],
                    'unit' => $threshold['unit'] ?? null,
                    'notes' => $threshold['notes'] ?? null,
                ]
            );
        }
    }

    private function flagPriorityPollutants(): void
    {
        if (! Schema::hasTable('water_quality_parameters') || ! Schema::hasColumn('water_quality_parameters', 'is_priority_pollutant')) {
            return;
        }

        $priorityPollutantCodes = [
            // TODO: Populate from REIWQ Appendix E parameter codes.
            // 'NO3',
            // 'PO4',
        ];

        if ($priorityPollutantCodes === []) {
            return;
        }

        DB::table('water_quality_parameters')
            ->whereIn('code', $priorityPollutantCodes)
            ->update(['is_priority_pollutant' => true]);
    }
}
