<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateHydrologyDataJuly2019Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/hydrology_july_2019.json');
        if (! file_exists($jsonPath)) {
            $this->command->error("Parsed JSON not found at: {$jsonPath}");

            return;
        }

        $this->command->info("Reading confirmed July 2019 hydrology data from {$jsonPath}...");
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

        // Helper to map river basin to subcatchment code
        $getSubcatchmentCode = function ($basin, $river = null) {
            $name = strtolower($basin ?? $river ?? '');
            if (str_contains($name, 'lusutfu') || str_contains($name, 'lusuthu') || str_contains($name, 'usuthu')) {
                return 'MAP-USUTHU';
            }
            if (str_contains($name, 'mkhondvo')) {
                return 'MAP-MKHONDVO';
            }
            if (str_contains($name, 'ngwavuma')) {
                return 'MAP-NGWAVUMA';
            }
            if (str_contains($name, 'lomati')) {
                return 'INC-KOMATI';
            }
            if (str_contains($name, 'komati')) {
                return 'INC-KOMATI';
            }
            if (str_contains($name, 'hlelo')) {
                return 'MAP-USUTHU';
            }
            if (str_contains($name, 'ngwempisi')) {
                return 'MAP-NGWEMPISI';
            }
            if (str_contains($name, 'mpuluzi')) {
                return 'MAP-MPULUZI';
            }
            if (str_contains($name, 'lusushwana')) {
                return 'MAP-LUSUSHWANA';
            }
            if (str_contains($name, 'pongola') || str_contains($name, 'phongola')) {
                return 'MAP-PONGOLA';
            }
            if (str_contains($name, 'maputo')) {
                return 'MAP-MAPUTO';
            }

            return 'MAP-USUTHU'; // default fallback for Eswatini Maputo basin
        };

        $normalizeCode = function (string $code): string {
            $code = trim(strtoupper($code));
            if (preg_match('/^GS\s*[-–]?\s*(\d+)(.*)$/i', $code, $matches)) {
                $num = intval($matches[1]);
                $suffix = trim($matches[2]);
                $suffix = preg_replace('/[\s\(\)\-\_]+/i', '', $suffix);
                if ($suffix) {
                    return 'GS-'.sprintf('%02d', $num).'-'.$suffix;
                } else {
                    return 'GS-'.sprintf('%02d', $num);
                }
            }

            return str_replace(' ', '-', $code);
        };

        // 2. Seed/Update stations from Sheet1
        $this->command->info('Seeding/updating hydrology stations...');
        $stationIdMap = [];

        foreach ($data['stations'] as $item) {
            $originalCode = $item['original_code'];
            $cleanCode = $item['clean_code'];

            $normOriginal = $normalizeCode($originalCode);
            $normClean = $normalizeCode($cleanCode);

            // Build search codes to avoid duplicates (e.g. GS8 vs GS-08)
            $searchCodes = [$originalCode, $cleanCode, $normOriginal, $normClean];
            if (preg_match('/^GS(\d+)$/i', $cleanCode, $matches)) {
                $num = intval($matches[1]);
                $searchCodes[] = 'GS-'.$num;
                $searchCodes[] = 'GS-'.sprintf('%02d', $num);
                $searchCodes[] = 'GS '.$num;
            }

            // Add normalized versions of all search codes
            $additionalSearch = [];
            foreach ($searchCodes as $sc) {
                $additionalSearch[] = $normalizeCode($sc);
            }
            $searchCodes = array_values(array_unique(array_merge($searchCodes, $additionalSearch)));

            // Find existing station in DB
            $existingStation = DB::table('stations')
                ->whereIn('code', $searchCodes)
                ->first();

            $stationId = $existingStation ? $existingStation->id : (string) Str::uuid();
            $targetCode = $existingStation ? $existingStation->code : $normClean;
            $stationIdMap[$cleanCode] = $stationId;
            $stationIdMap[$originalCode] = $stationId;
            $stationIdMap[$normClean] = $stationId;
            $stationIdMap[$normOriginal] = $stationId;
            if ($existingStation) {
                $stationIdMap[$existingStation->code] = $stationId;
            }

            $isActive = ($item['latitude'] !== null && $item['longitude'] !== null);
            $elevationStr = $item['elevation'] !== null ? $item['elevation'].' m a.s.l.' : 'N/A';
            $weirStr = $item['weir_type'] !== null ? $item['weir_type'] : 'N/A';
            $meanStr = $item['lt_mean'] !== null ? $item['lt_mean'].' Mm3/a' : 'N/A';

            $summary = "[Confirmed July 2019] Elevation: {$elevationStr}. Weir Type: {$weirStr}. Long-Term Mean: {$meanStr}.";
            if ($item['opening_date']) {
                $summary .= " Opened: {$item['opening_date']}.";
            }
            if ($item['comments']) {
                $summary .= " Comments: {$item['comments']}.";
            }

            DB::table('stations')->updateOrInsert(
                ['id' => $stationId],
                [
                    'code' => $targetCode,
                    'name' => $originalCode.' ('.($item['basin'] ?? 'Lusutfu').')',
                    'latitude' => $item['latitude'] ?? ($existingStation ? $existingStation->latitude : -26.5),
                    'longitude' => $item['longitude'] ?? ($existingStation ? $existingStation->longitude : 31.5),
                    'category' => 'hydrology',
                    'water_source' => 'surface',
                    'water_body_type' => 'river',
                    'is_active' => $isActive,
                    'is_real_time' => $existingStation ? $existingStation->is_real_time : false,
                    'summary' => $summary,
                    'telemetry_system' => $item['weir_type'],
                    'gauge_code' => $cleanCode,
                    'owner_org' => 'DWA-SW',
                    'country' => 'Eswatini',
                    'river_basin' => $item['basin'] ?? 'Lusutfu',
                ]
            );

            // Capabilities
            DB::table('station_capabilities')->updateOrInsert(
                ['station_id' => $stationId, 'measurement_type' => 'flow'],
                [
                    'is_primary' => true,
                    'installed_at' => Carbon::now()->subYears(5),
                    'notes' => 'Confirmed July 2019 metadata',
                ]
            );

            // Subcatchment Junction
            $subcatCode = $getSubcatchmentCode($item['basin']);
            $subcatId = DB::table('management_areas')->where('code', $subcatCode)->value('id');
            if ($subcatId) {
                DB::table('management_area_stations')->updateOrInsert([
                    'management_area_id' => $subcatId,
                    'station_id' => $stationId,
                ]);
            }
        }

        // Cache all database stations into the map to be absolutely thorough
        $allStations = DB::table('stations')->select('id', 'code')->get();
        foreach ($allStations as $st) {
            $stationIdMap[$st->code] = $st->id;
            if (preg_match('/^GS-(\d+)$/i', $st->code, $matches)) {
                $num = intval($matches[1]);
                $stationIdMap['GS'.$num] = $st->id;
                $stationIdMap['GS '.$num] = $st->id;
            }
        }

        // 3. Seed/Update Cross Border Eflow Requirements (Sheet2)
        $this->command->info('Seeding confirmed cross border eflows...');
        foreach ($data['eflows'] as $req) {
            $riverName = $req['river'] ?? 'Usuthu';
            $keyPoint = $req['key_point'];

            // Match key_point to station code
            $stationId = null;
            if (preg_match('/GS\s*(\d+)/i', $keyPoint, $matches)) {
                $num = intval($matches[1]);
                $stationId = $stationIdMap['GS'.$num] ?? $stationIdMap['GS-'.sprintf('%02d', $num)] ?? null;
            } elseif (str_contains(strtolower($keyPoint), 'salamanga')) {
                $stationId = $stationIdMap['E-4'] ?? null;
            } elseif (str_contains(strtolower($keyPoint), 'ndumo')) {
                $stationId = $stationIdMap['NDUMO-01'] ?? null;
            }

            $subcatCode = $getSubcatchmentCode(null, $riverName);
            $subcatId = DB::table('management_areas')->where('code', $subcatCode)->value('id');

            // Insert Key Point
            $existingKp = DB::table('iima_eflow_key_points')
                ->where('name', $keyPoint)
                ->orWhere('code', $keyPoint)
                ->first();

            $kpId = $existingKp ? $existingKp->id : (string) Str::uuid();

            DB::table('iima_eflow_key_points')->updateOrInsert(
                ['id' => $kpId],
                [
                    'code' => $keyPoint,
                    'name' => $keyPoint,
                    'river' => $riverName.' River',
                    'country' => $stationId ? DB::table('stations')->where('id', $stationId)->value('country') : 'Eswatini',
                    'subcatchment_id' => $subcatId,
                    'station_id' => $stationId,
                    'latitude' => $stationId ? DB::table('stations')->where('id', $stationId)->value('latitude') : null,
                    'longitude' => $stationId ? DB::table('stations')->where('id', $stationId)->value('longitude') : null,
                    'is_active' => true,
                    'note' => 'Confirmed: Update to hydrology gauging station data July 2019',
                ]
            );

            // Insert / Update Requirement
            $existingReqId = DB::table('iima_eflow_requirements')
                ->where('key_point', $keyPoint)
                ->orWhere('key_point_id', $kpId)
                ->value('id');

            DB::table('iima_eflow_requirements')->updateOrInsert(
                ['key_point' => $keyPoint],
                [
                    'id' => $existingReqId ?? (string) Str::uuid(),
                    'key_point_id' => $kpId,
                    'subcatchment_id' => $subcatId,
                    'river' => $riverName,
                    'station_id' => $stationId,
                    'mean_annual_mm3' => $req['mean_annual_mm3'],
                    'min_flow_m3_s' => $req['min_flow_m3_s'],
                    'source_article' => 'IIMA Target Flows',
                    'note' => 'Confirmed: Update to hydrology gauging station data July 2019',
                ]
            );
        }

        $this->command->info('Seeding complete! Successfully preloaded and confirmed July 2019 hydrology metadata.');
    }
}
