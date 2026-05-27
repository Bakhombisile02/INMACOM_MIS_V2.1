<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class StationSqlSeeder extends Seeder
{
    /**
     * Run the database seeds by parsing the legacy SQL dump.
     * Maps legacy categories to standard INMACOM MIS v2 categories,
     * resolves duplicate codes and overlapping stations, and infers water source/body types.
     */
    public function run(): void
    {
        $sqlPath = base_path('Data for Database /station.sql');
        if (! file_exists($sqlPath)) {
            $this->command->error("Legacy SQL dump not found at: {$sqlPath}");

            return;
        }

        $this->command->info("Reading legacy station dump from {$sqlPath}...");
        $sqlContent = file_get_contents($sqlPath);

        // Parse lines of SQL inserts
        $lines = explode("\n", $sqlContent);
        $parsedStations = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, '(') === 0) {
                // Strip the trailing comma or semicolon
                $line = rtrim($line, ',;');

                // Parse values inside the parenthesis
                if (strlen($line) > 2) {
                    $content = substr($line, 1, -1);
                    $parts = str_getcsv($content, ',', "'");

                    if (count($parts) >= 6) {
                        $legacyId = intval($parts[0]);
                        $code = trim($parts[1]);

                        // Normalize GS codes (e.g., GS8 -> GS-08, GS 16 -> GS-16) to avoid duplication
                        if (preg_match('/^GS\s*[-–]?\s*(\d+)(.*)$/i', $code, $matches)) {
                            $num = intval($matches[1]);
                            $suffix = trim($matches[2]);
                            $suffix = preg_replace('/[\s\(\)\-\_]+/i', '', $suffix);
                            $code = 'GS-'.sprintf('%02d', $num).($suffix ? '-'.strtoupper($suffix) : '');
                        }

                        $name = trim($parts[2]);
                        $lat = floatval($parts[3]);
                        $lng = floatval($parts[4]);
                        $legacyCategory = trim($parts[5]);

                        // Skip placeholder or test stations
                        if ($code === 'TEST' || stripos($name, 'testing') !== false || stripos($name, 'test') !== false) {
                            continue;
                        }

                        // 1. Resolve duplicate code conflict for 'PONG'
                        if ($code === 'PONG') {
                            if ($legacyCategory === 'Rainfall') {
                                $code = 'PONG-RAIN';
                            } else {
                                $code = 'PONG-DAM';
                            }
                        }

                        // Correct coordinates for 'E-3' (Bela Vista) which are set to 0.0, 0.0 (Null Island near Congo) in the SQL dump
                        if ($code === 'E-3') {
                            $lat = -26.34070;
                            $lng = 32.66750;
                            $legacyCategory = 'Flow Levels'; // Bela Vista is a river flow gauge on the Maputo River
                        }

                        // 2. Map legacy category to standard category, water source, and water body type
                        $category = 'river';
                        $waterSource = 'surface';
                        $waterBodyType = 'river';
                        $capability = 'flow';

                        switch ($legacyCategory) {
                            case 'Dam Levels':
                                $category = 'dam';
                                $waterSource = 'surface';
                                $waterBodyType = 'dam';
                                $capability = 'dam_level';
                                break;
                            case 'Flow Levels':
                                $category = 'river';
                                $waterSource = 'surface';
                                $waterBodyType = 'river';
                                $capability = 'flow';
                                break;
                            case 'Water Quality':
                                if (stripos($name, 'Dam') !== false || stripos($name, 'Pongolapoort') !== false) {
                                    $category = 'dam';
                                    $waterSource = 'surface';
                                    $waterBodyType = 'dam';
                                } else {
                                    $category = 'river';
                                    $waterSource = 'surface';
                                    $waterBodyType = 'river';
                                }
                                $capability = 'water_quality';
                                break;
                            case 'Rainfall':
                                $category = 'meteorological';
                                $waterSource = 'precipitation';
                                $waterBodyType = 'meteorological';
                                $capability = 'rainfall';
                                break;
                            case 'Groundwater':
                                $category = 'groundwater';
                                $waterSource = 'groundwater';
                                $waterBodyType = 'aquifer';
                                $capability = 'groundwater';
                                break;
                            default:
                                if (stripos($name, 'Dam') !== false) {
                                    $category = 'dam';
                                    $waterSource = 'surface';
                                    $waterBodyType = 'dam';
                                    $capability = 'dam_level';
                                }
                                break;
                        }

                        // Merge capabilities if duplicate code exists (e.g. GS34 listed as both Flow and WQ)
                        if (isset($parsedStations[$code])) {
                            if (! in_array($capability, $parsedStations[$code]['capabilities'])) {
                                $parsedStations[$code]['capabilities'][] = $capability;
                            }
                            // Keep the most informative name/coordinates
                            if (strlen($name) > strlen($parsedStations[$code]['name'])) {
                                $parsedStations[$code]['name'] = $name;
                            }
                        } else {
                            $parsedStations[$code] = [
                                'code' => $code,
                                'name' => $name,
                                'latitude' => $lat,
                                'longitude' => $lng,
                                'category' => $category,
                                'water_source' => $waterSource,
                                'water_body_type' => $waterBodyType,
                                'capabilities' => [$capability],
                            ];
                        }
                    }
                }
            }
        }

        $this->command->info('Parsed '.count($parsedStations).' distinct stations from station.sql.');

        // 3. Insert / Update each station in the database
        $inserted = 0;
        $updated = 0;

        foreach ($parsedStations as $code => $st) {
            // Determine Country & Organization
            $country = 'South Africa';
            $org = 'DWA-RSA';

            if (strpos($code, 'GS') === 0 || in_array($st['name'], ['Lubovane', 'Luphohlo', 'Maguga', 'Mnjoli'])) {
                $country = 'Eswatini';
                $org = 'DWA-SW';
                if ($st['name'] === 'Maguga') {
                    $org = 'KOBWA';
                }
            } elseif (strpos($code, 'E-') === 0 || in_array($st['name'], ['Xinavane', 'Manhica', 'Maputo em Salamanga', 'Incoluane', 'Maragra', 'Marracuene', 'Maputo em Madubula I', 'Barragem Corrumane', 'Barragem Corrumane - Jusante', 'Fronteira Oeste', 'Magude', 'Bela Vista', 'Pequenos Libombos', 'Corumana'])) {
                $country = 'Mozambique';
                $org = 'ARA-Sul';
            } else {
                $org = 'DWA-RSA';
                if (in_array($st['name'], ['Bivane', 'Driekoppies'])) {
                    $org = 'KOBWA';
                }
            }

            // Determine Basin (Maputo vs Incomati)
            $maputoIdentifiers = [
                'Usuthu', 'Ngwempisi', 'Mkhondvo', 'Assegai', 'Mahamba', 'Nerston', 'Mpuluzi', 'Hlelo',
                'Piet Retief', 'Bivane', 'Heyshope', 'Westoe', 'Jericho', 'Morgenstond', 'Salamanga',
                'Bela Vista', 'Madubula', 'Jozini', 'Pongolapoort', 'Pequenos Libombos', 'PEQ', 'MNJ',
                'Mnjoli', 'GS', 'U-26', 'U-44', 'U-53', 'U-57', 'U-61', 'U-43', 'U-5', 'U-47', 'U-55',
                'U-49', 'BIV', 'PONG', 'W4H013',
            ];

            $isMaputo = false;
            foreach ($maputoIdentifiers as $id) {
                if (stripos($code, $id) !== false || stripos($st['name'], $id) !== false) {
                    $isMaputo = true;
                    break;
                }
            }
            $riverBasin = $isMaputo ? 'Maputo' : 'Incomati';

            // Find matching subcatchment code
            $subcatchmentCode = null;
            if ($riverBasin === 'Maputo') {
                if (stripos($st['name'], 'Ngwempisi') !== false) {
                    $subcatchmentCode = 'MAP-NGWEMPISI';
                } elseif (stripos($st['name'], 'Mkhondvo') !== false) {
                    $subcatchmentCode = 'MAP-MKHONDVO';
                } elseif (stripos($st['name'], 'Ngwavuma') !== false) {
                    $subcatchmentCode = 'MAP-NGWAVUMA';
                } elseif (stripos($st['name'], 'Pongola') !== false || stripos($st['name'], 'Bivane') !== false) {
                    $subcatchmentCode = 'MAP-PONGOLA';
                } elseif (stripos($st['name'], 'Lusushwana') !== false) {
                    $subcatchmentCode = 'MAP-LUSUSHWANA';
                } elseif (stripos($st['name'], 'Mpuluzi') !== false) {
                    $subcatchmentCode = 'MAP-MPULUZI';
                } elseif (stripos($st['name'], 'Salamanga') !== false || stripos($st['name'], 'Bela Vista') !== false) {
                    $subcatchmentCode = 'MAP-MAPUTO';
                } else {
                    $subcatchmentCode = 'MAP-USUTHU';
                }
            } else {
                if (stripos($st['name'], 'Sabie') !== false) {
                    $subcatchmentCode = 'INC-SABIE';
                } elseif (stripos($st['name'], 'Crocodile') !== false) {
                    $subcatchmentCode = 'INC-CROCODILE';
                } elseif (stripos($st['name'], 'Komati') !== false || stripos($st['name'], 'Driekoppies') !== false || stripos($st['name'], 'Matsamo') !== false || stripos($st['name'], 'Mananga') !== false || stripos($st['name'], 'Diepgezet') !== false) {
                    $subcatchmentCode = 'INC-KOMATI';
                } else {
                    $subcatchmentCode = 'INC-LOWER';
                }
            }

            // Insert or update station row
            $existingId = DB::table('stations')->where('code', $code)->value('id');
            $stationId = $existingId ?? (string) Str::uuid();

            if ($existingId) {
                $updated++;
            } else {
                $inserted++;
            }

            DB::table('stations')->updateOrInsert(
                ['code' => $code],
                [
                    'id' => $stationId,
                    'name' => $st['name'],
                    'latitude' => $st['latitude'],
                    'longitude' => $st['longitude'],
                    'category' => $st['category'],
                    'water_source' => $st['water_source'],
                    'water_body_type' => $st['water_body_type'],
                    'is_active' => true,
                    'is_real_time' => in_array($st['category'], ['dam', 'river']) && ($country === 'Mozambique' || $org === 'KOBWA'),
                    'owner_org' => $org,
                    'country' => $country,
                    'river_basin' => $riverBasin,
                    'summary' => $st['name'].' monitoring station in the '.$riverBasin.' Basin. [Inferred Metadata — Unconfirmed & Unverified]',
                ]
            );

            // Capabilities mapping
            foreach ($st['capabilities'] as $cap) {
                DB::table('station_capabilities')->updateOrInsert(
                    ['station_id' => $stationId, 'measurement_type' => $cap],
                    [
                        'is_primary' => true,
                        'installed_at' => Carbon::now()->subYears(4),
                    ]
                );
            }

            // Subcatchment mapping
            if ($subcatchmentCode && Schema::hasTable('management_areas')) {
                $subcatId = DB::table('management_areas')->where('code', $subcatchmentCode)->value('id');
                if ($subcatId) {
                    DB::table('management_area_stations')->updateOrInsert([
                        'management_area_id' => $subcatId,
                        'station_id' => $stationId,
                    ]);
                }
            }
        }

        $this->command->info("StationSqlSeeder complete! Mapped {$inserted} new stations and updated {$updated} existing stations.");
    }
}
