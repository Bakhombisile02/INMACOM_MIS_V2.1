<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ExcelDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/excel_data.json');
        if (! file_exists($jsonPath)) {
            $this->command->error("Parsed Excel JSON not found at: {$jsonPath}");

            return;
        }

        $this->command->info("Reading parsed Excel data from {$jsonPath}...");
        $data = json_decode(file_get_contents($jsonPath), true);

        if (! $data) {
            $this->command->error('Failed to decode JSON data.');

            return;
        }

        // 1. Get or create seeder user
        $user = User::query()->first();
        if (! $user) {
            $user = User::create([
                'id' => (string) Str::uuid(),
                'display_name' => 'INMACOM Seeder User',
                'email' => 'seeder@inmacom.org',
                'role' => User::ROLE_ADMIN,
                'firebase_uid' => 'seeder-default-uid',
            ]);
        }
        $userId = $user->id;

        // 2. Insert/Update Stations & Capabilities & Subcatchment Junctions
        $this->command->info('Seeding Eswatini stations and capabilities...');
        $stationIdMap = [];
        foreach ($data['stations'] as $item) {
            $existingId = DB::table('stations')->where('code', $item['code'])->value('id');
            $stationId = $existingId ?? (string) Str::uuid();
            $stationIdMap[$item['code']] = $stationId;

            DB::table('stations')->updateOrInsert(
                ['code' => $item['code']],
                [
                    'id' => $stationId,
                    'name' => $item['name'],
                    'latitude' => $item['latitude'],
                    'longitude' => $item['longitude'],
                    'category' => $item['category'],
                    'water_source' => $item['water_source'],
                    'water_body_type' => $item['category'] === 'dam' ? 'dam' : 'river',
                    'is_active' => true,
                    'is_real_time' => $item['realtime'],
                    'owner_org' => $item['org'],
                    'country' => $item['country'],
                    'river_basin' => $item['basin'],
                    'summary' => $item['name'].' monitoring station in the '.$item['basin'].' River Basin.',
                ]
            );

            // Capabilities
            foreach ($item['capabilities'] as $cap) {
                DB::table('station_capabilities')->updateOrInsert(
                    ['station_id' => $stationId, 'measurement_type' => $cap],
                    [
                        'is_primary' => true,
                        'installed_at' => Carbon::now()->subYears(3),
                    ]
                );
            }

            // Subcatchment Junction
            if (! empty($item['subcatchment']) && Schema::hasTable('management_areas')) {
                $subcatId = DB::table('management_areas')->where('code', $item['subcatchment'])->value('id');
                if ($subcatId) {
                    DB::table('management_area_stations')->updateOrInsert([
                        'management_area_id' => $subcatId,
                        'station_id' => $stationId,
                    ]);
                }
            }
        }

        // Cache existing station IDs in database
        $allStations = DB::table('stations')->select('id', 'code')->get();
        foreach ($allStations as $st) {
            $stationIdMap[$st->code] = $st->id;
        }

        // 3. Seed Interim Target Instream Flows (Eflow Key Points and Requirements)
        $this->command->info('Seeding IIMA target instream flows for the Maputo Basin...');
        foreach ($data['eflow_requirements'] as $req) {
            $subcatchmentId = DB::table('management_areas')
                ->where('code', $req['subcatchment_code'])
                ->value('id');

            $stationId = $stationIdMap[$req['station_code']] ?? null;

            // Update or Insert Key Point
            $existingKpId = DB::table('iima_eflow_key_points')
                ->where('code', $req['station_code'])
                ->value('id');

            $kpId = $existingKpId ?? (string) Str::uuid();

            DB::table('iima_eflow_key_points')->updateOrInsert(
                ['code' => $req['station_code']],
                [
                    'id' => $kpId,
                    'name' => $req['key_point'],
                    'river' => $req['river'].' River',
                    'country' => $stationId ? DB::table('stations')->where('id', $stationId)->value('country') : 'Eswatini',
                    'subcatchment_id' => $subcatchmentId,
                    'station_id' => $stationId,
                    'latitude' => $stationId ? DB::table('stations')->where('id', $stationId)->value('latitude') : null,
                    'longitude' => $stationId ? DB::table('stations')->where('id', $stationId)->value('longitude') : null,
                    'is_active' => true,
                    'note' => $req['note'],
                ]
            );

            // Update or Insert eflow Requirement
            $existingReqId = DB::table('iima_eflow_requirements')
                ->where('key_point_id', $kpId)
                ->value('id');

            DB::table('iima_eflow_requirements')->updateOrInsert(
                ['key_point_id' => $kpId],
                [
                    'id' => $existingReqId ?? (string) Str::uuid(),
                    'subcatchment_id' => $subcatchmentId,
                    'river' => $req['river'],
                    'key_point' => $req['key_point'],
                    'station_id' => $stationId,
                    'mean_annual_mm3' => $req['mean_annual_mm3'],
                    'min_flow_m3_s' => $req['min_flow_m3_s'],
                    'source_article' => 'IIMA Table 5-4',
                    'note' => $req['note'],
                ]
            );
        }

        // 4. Seeding Measurements
        $this->command->info('Seeding Eswatini hydrology and dam measurements...');

        // Let's delete existing measurements for these Eswatini stations so we don't duplicate on re-run
        $seededStationIds = array_values($stationIdMap);
        DB::table('measurements')->whereIn('station_id', $seededStationIds)->delete();

        $measurementsBatch = [];
        $insertedCount = 0;

        foreach ($data['measurements'] as $meas) {
            $stationId = $stationIdMap[$meas['station_code']] ?? null;
            if (! $stationId) {
                continue;
            }

            $measurementsBatch[] = [
                'id' => (string) Str::uuid(),
                'station_id' => $stationId,
                'measurement_type' => $meas['type'],
                'parameter_id' => null,
                'fsc' => $meas['fsc'] ?? null,
                'value' => $meas['value'],
                'unit' => $meas['unit'],
                'date' => Carbon::parse($meas['date']),
                'status' => 'approved',
                'submitted_by_id' => $userId,
                'submitted_at' => Carbon::now(),
                'reviewed_by_id' => $userId,
                'reviewed_at' => Carbon::now(),
                'review_notes' => 'Imported Eswatini hydrology & reservoir operational data.',
                'is_self_override' => true,
            ];

            if (count($measurementsBatch) >= 500) {
                DB::table('measurements')->insert($measurementsBatch);
                $insertedCount += count($measurementsBatch);
                $measurementsBatch = [];
            }
        }

        if (count($measurementsBatch) > 0) {
            DB::table('measurements')->insert($measurementsBatch);
            $insertedCount += count($measurementsBatch);
        }

        $this->command->info("Seeding complete! Successfully preloaded Eswatini stations and inserted {$insertedCount} measurements.");
    }
}
