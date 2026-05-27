<?php

namespace Database\Seeders;

use App\Models\Station;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DomainDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure we have at least one user to assign as submitter/reviewer
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

        // 2. Seed Water Quality Parameters
        $this->seedWaterQualityParameters();

        // 3. Seed Monitoring Stations & Capabilities & subcatchment junctions
        $this->seedStationsAndCapabilities();

        // 4. Seed Compliance Thresholds (REIWQ Appendix A defaults)
        $this->seedComplianceThresholds();

        // 5. Seed Mock Measurements for Dam Levels, Flow, Water Quality, and Rainfall
        $this->seedMockMeasurements($userId);
    }

    private function seedWaterQualityParameters(): void
    {
        $parameters = [
            ['code' => 'Colour', 'name' => 'Colour', 'unit' => 'mg/L Pt–Co', 'is_priority' => false, 'reiwq' => '4', 'order' => 1],
            ['code' => 'Odour', 'name' => 'Odour', 'unit' => 'Dilution factor', 'is_priority' => false, 'reiwq' => '4', 'order' => 2],
            ['code' => 'TUR', 'name' => 'Turbidity', 'unit' => 'NTU', 'is_priority' => false, 'reiwq' => '4', 'order' => 3],
            ['code' => 'pH', 'name' => 'pH', 'unit' => 'pH units', 'is_priority' => false, 'reiwq' => '1, 4', 'order' => 4],
            ['code' => 'EC', 'name' => 'Electrical Conductivity', 'unit' => 'mS/m', 'is_priority' => false, 'reiwq' => '1, 4', 'order' => 5],
            ['code' => 'NH3-N', 'name' => 'Ammonia Nitrogen', 'unit' => 'mg/L', 'is_priority' => false, 'reiwq' => '1, 4', 'order' => 6],
            ['code' => 'BOD', 'name' => 'Biochemical Oxygen Demand', 'unit' => 'mg/L', 'is_priority' => false, 'reiwq' => '1', 'order' => 7],
            ['code' => 'COD', 'name' => 'Chemical Oxygen Demand', 'unit' => 'mg/L', 'is_priority' => false, 'reiwq' => '1, 4', 'order' => 8],
            ['code' => 'Cl', 'name' => 'Chloride', 'unit' => 'mg/L', 'is_priority' => false, 'reiwq' => '1, 4', 'order' => 9],
            ['code' => 'DO', 'name' => 'Dissolved Oxygen', 'unit' => '% saturation', 'is_priority' => false, 'reiwq' => '1', 'order' => 10],
            ['code' => 'F', 'name' => 'Fluoride', 'unit' => 'mg/L', 'is_priority' => false, 'reiwq' => '1, 3, 4', 'order' => 11],
            ['code' => 'NO3', 'name' => 'Nitrate (as NO₃)', 'unit' => 'mg/L', 'is_priority' => false, 'reiwq' => '1, 2', 'order' => 12],
            ['code' => 'NO2+NO3', 'name' => 'Nitrite + Nitrate', 'unit' => 'mg/L', 'is_priority' => false, 'reiwq' => null, 'order' => 13],
            ['code' => 'K', 'name' => 'Potassium', 'unit' => 'mg/L', 'is_priority' => false, 'reiwq' => null, 'order' => 14],
            ['code' => 'Na', 'name' => 'Sodium', 'unit' => 'mg/L', 'is_priority' => false, 'reiwq' => null, 'order' => 15],
            ['code' => 'TP', 'name' => 'Total Phosphate', 'unit' => 'mg/L', 'is_priority' => false, 'reiwq' => '1, 4', 'order' => 16],
            ['code' => 'PO4', 'name' => 'Phosphate', 'unit' => 'mg/L', 'is_priority' => false, 'reiwq' => null, 'order' => 17],
            ['code' => 'SO4', 'name' => 'Sulphate', 'unit' => 'mg/L', 'is_priority' => false, 'reiwq' => '1, 2, 4', 'order' => 18],
            ['code' => 'TC', 'name' => 'Total Coliforms', 'unit' => 'CFU/100 mL', 'is_priority' => false, 'reiwq' => '1, 2', 'order' => 19],
            ['code' => 'FC', 'name' => 'Faecal Coliforms', 'unit' => 'CFU/100 mL', 'is_priority' => false, 'reiwq' => '1, 2', 'order' => 20],
            ['code' => 'FS', 'name' => 'Faecal Streptococci', 'unit' => 'CFU/100 mL', 'is_priority' => false, 'reiwq' => '1, 2', 'order' => 21],
            ['code' => 'VC', 'name' => 'Vibrio cholerae', 'unit' => 'CFU/1,000 mL', 'is_priority' => false, 'reiwq' => '3', 'order' => 22],
            ['code' => 'Cu', 'name' => 'Copper', 'unit' => 'mg/L', 'is_priority' => false, 'reiwq' => '1', 'order' => 23],
            ['code' => 'Fe', 'name' => 'Iron', 'unit' => 'mg/L', 'is_priority' => false, 'reiwq' => '1, 2', 'order' => 24],
            ['code' => 'Mn', 'name' => 'Manganese', 'unit' => 'mg/L', 'is_priority' => false, 'reiwq' => '1, 2, 4', 'order' => 25],
            ['code' => 'Pesticides', 'name' => 'Pesticides (general)', 'unit' => ' qualitative', 'is_priority' => true, 'reiwq' => '3, 4', 'order' => 26],
            ['code' => 'E coli', 'name' => 'Escherichia coli', 'unit' => 'CFU/100 mL', 'is_priority' => false, 'reiwq' => null, 'order' => 27],
            ['code' => 'SS', 'name' => 'Suspended Solids', 'unit' => 'mg/L', 'is_priority' => false, 'reiwq' => null, 'order' => 28],
            ['code' => 'As', 'name' => 'Arsenic', 'unit' => 'mg/L', 'is_priority' => true, 'reiwq' => null, 'order' => 29],
            ['code' => 'Cn', 'name' => 'Cyanide', 'unit' => 'mg/L', 'is_priority' => true, 'reiwq' => null, 'order' => 30],
            ['code' => 'Al', 'name' => 'Aluminium', 'unit' => 'mg/L', 'is_priority' => false, 'reiwq' => null, 'order' => 31],
            ['code' => 'Cr6', 'name' => 'Hexavalent Chromium', 'unit' => 'mg/L', 'is_priority' => true, 'reiwq' => null, 'order' => 32],
            ['code' => 'Ni', 'name' => 'Nickel', 'unit' => 'mg/L', 'is_priority' => true, 'reiwq' => null, 'order' => 33],
            ['code' => 'Hg', 'name' => 'Mercury', 'unit' => 'mg/L', 'is_priority' => true, 'reiwq' => null, 'order' => 34],
            ['code' => 'Mg', 'name' => 'Magnesium', 'unit' => 'mg/L', 'is_priority' => false, 'reiwq' => null, 'order' => 35],
            ['code' => 'Temp', 'name' => 'Temperature', 'unit' => '°C', 'is_priority' => false, 'reiwq' => null, 'order' => 36],
            ['code' => 'Sal', 'name' => 'Salinity', 'unit' => 'PSU', 'is_priority' => false, 'reiwq' => null, 'order' => 37],
            ['code' => 'TDS', 'name' => 'Total Dissolved Solids', 'unit' => 'mg/L', 'is_priority' => false, 'reiwq' => null, 'order' => 38],
            ['code' => 'Sn', 'name' => 'Tin', 'unit' => 'mg/L', 'is_priority' => false, 'reiwq' => null, 'order' => 39],
        ];

        foreach ($parameters as $param) {
            $existingId = DB::table('water_quality_parameters')->where('code', $param['code'])->value('id');

            DB::table('water_quality_parameters')->updateOrInsert(
                ['code' => $param['code']],
                [
                    'id' => $existingId ?? (string) Str::uuid(),
                    'name' => $param['name'],
                    'default_unit' => $param['unit'],
                    'is_priority_pollutant' => $param['is_priority'],
                    'display_order' => $param['order'],
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedStationsAndCapabilities(): void
    {
        $stations = [
            // --- Rivers / Flow levels stations ---
            [
                'code' => 'E-23', 'name' => 'Ressano Garcia', 'alt_code' => 'X2H036',
                'lat' => -25.4431, 'lng' => 31.9861, 'type' => 'river', 'source' => 'surface', 'realtime' => true,
                'org' => 'ARA-Sul', 'country' => 'Mozambique', 'basin' => 'Incomati', 'subcatchment' => 'INC-LOWER',
                'capabilities' => ['flow', 'water_quality'],
            ],
            [
                'code' => 'E-4', 'name' => 'Salamanga', 'alt_code' => null,
                'lat' => -26.4753, 'lng' => 32.6483, 'type' => 'river', 'source' => 'surface', 'realtime' => true,
                'org' => 'ARA-Sul', 'country' => 'Mozambique', 'basin' => 'Maputo', 'subcatchment' => 'MAP-MAPUTO',
                'capabilities' => ['flow', 'water_quality'],
            ],
            [
                'code' => 'GS-16', 'name' => 'Big Bend', 'alt_code' => null,
                'lat' => -26.8170, 'lng' => 31.9330, 'type' => 'river', 'source' => 'surface', 'realtime' => true,
                'org' => 'DWA-SW', 'country' => 'Eswatini', 'basin' => 'Maputo', 'subcatchment' => 'MAP-USUTHU',
                'capabilities' => ['flow', 'water_quality', 'rainfall'],
            ],
            [
                'code' => 'GS-30', 'name' => 'Mananga', 'alt_code' => null,
                'lat' => -25.9330, 'lng' => 31.9170, 'type' => 'river', 'source' => 'surface', 'realtime' => true,
                'org' => 'KOBWA', 'country' => 'Eswatini', 'basin' => 'Incomati', 'subcatchment' => 'INC-KOMATI',
                'capabilities' => ['flow', 'water_quality', 'rainfall'],
            ],
            [
                'code' => 'X3H021', 'name' => 'Kruger Gate (Lower Sabie)', 'alt_code' => null,
                'lat' => -24.9685, 'lng' => 31.5154, 'type' => 'river', 'source' => 'surface', 'realtime' => true,
                'org' => 'DWA-RSA', 'country' => 'South Africa', 'basin' => 'Incomati', 'subcatchment' => 'INC-SABIE',
                'capabilities' => ['flow', 'water_quality', 'rainfall'],
            ],
            [
                'code' => 'X2H016', 'name' => 'Tenbosch', 'alt_code' => null,
                'lat' => -25.3639, 'lng' => 31.9557, 'type' => 'river', 'source' => 'surface', 'realtime' => true,
                'org' => 'DWA-RSA', 'country' => 'South Africa', 'basin' => 'Incomati', 'subcatchment' => 'INC-CROCODILE',
                'capabilities' => ['flow', 'water_quality'],
            ],
            [
                'code' => 'X1H049', 'name' => 'Driekoppies Outflow', 'alt_code' => null,
                'lat' => -25.7170, 'lng' => 31.5670, 'type' => 'river', 'source' => 'surface', 'realtime' => true,
                'org' => 'DWA-RSA', 'country' => 'South Africa', 'basin' => 'Incomati', 'subcatchment' => 'INC-KOMATI',
                'capabilities' => ['flow'],
            ],
            [
                'code' => 'X1H001', 'name' => 'Hooggenoeg', 'alt_code' => null,
                'lat' => -25.9670, 'lng' => 30.9330, 'type' => 'river', 'source' => 'surface', 'realtime' => true,
                'org' => 'DWA-RSA', 'country' => 'South Africa', 'basin' => 'Incomati', 'subcatchment' => 'INC-KOMATI',
                'capabilities' => ['flow'],
            ],
            [
                'code' => 'GS-34', 'name' => 'Matsamo', 'alt_code' => null,
                'lat' => -25.7330, 'lng' => 31.4170, 'type' => 'river', 'source' => 'surface', 'realtime' => true,
                'org' => 'KOBWA', 'country' => 'Eswatini', 'basin' => 'Incomati', 'subcatchment' => 'INC-KOMATI',
                'capabilities' => ['flow'],
            ],
            [
                'code' => 'GS-25', 'name' => 'Mkhondvo', 'alt_code' => null,
                'lat' => -26.5830, 'lng' => 31.2500, 'type' => 'river', 'source' => 'surface', 'realtime' => false,
                'org' => 'DWA-SW', 'country' => 'Eswatini', 'basin' => 'Maputo', 'subcatchment' => 'MAP-MKHONDVO',
                'capabilities' => ['flow', 'water_quality'],
            ],
            [
                'code' => 'GS-22', 'name' => 'Hlelo', 'alt_code' => null,
                'lat' => -26.7170, 'lng' => 31.1170, 'type' => 'river', 'source' => 'surface', 'realtime' => false,
                'org' => 'DWA-SW', 'country' => 'Eswatini', 'basin' => 'Maputo', 'subcatchment' => 'MAP-USUTHU',
                'capabilities' => ['flow', 'water_quality'],
            ],
            [
                'code' => 'GS-21', 'name' => 'Ngwempisi', 'alt_code' => null,
                'lat' => -26.6500, 'lng' => 31.1500, 'type' => 'river', 'source' => 'surface', 'realtime' => false,
                'org' => 'DWA-SW', 'country' => 'Eswatini', 'basin' => 'Maputo', 'subcatchment' => 'MAP-NGWEMPISI',
                'capabilities' => ['flow', 'water_quality'],
            ],
            [
                'code' => 'GS-23', 'name' => 'Usuthu', 'alt_code' => null,
                'lat' => -26.5000, 'lng' => 31.4170, 'type' => 'river', 'source' => 'surface', 'realtime' => false,
                'org' => 'DWA-SW', 'country' => 'Eswatini', 'basin' => 'Maputo', 'subcatchment' => 'MAP-USUTHU',
                'capabilities' => ['flow'],
            ],
            [
                'code' => 'GS-33', 'name' => 'Lusushwana', 'alt_code' => null,
                'lat' => -26.4000, 'lng' => 31.1830, 'type' => 'river', 'source' => 'surface', 'realtime' => false,
                'org' => 'DWA-SW', 'country' => 'Eswatini', 'basin' => 'Maputo', 'subcatchment' => 'MAP-LUSUSHWANA',
                'capabilities' => ['flow', 'water_quality'],
            ],
            [
                'code' => 'E-173', 'name' => 'Pelegrine', 'alt_code' => null,
                'lat' => -25.7500, 'lng' => 32.2330, 'type' => 'river', 'source' => 'surface', 'realtime' => false,
                'org' => 'ARA-Sul', 'country' => 'Mozambique', 'basin' => 'Incomati', 'subcatchment' => 'INC-LOWER',
                'capabilities' => ['flow', 'water_quality'],
            ],
            [
                'code' => 'E-28', 'name' => 'Chinhanguanine', 'alt_code' => null,
                'lat' => -25.2170, 'lng' => 32.2830, 'type' => 'river', 'source' => 'surface', 'realtime' => false,
                'org' => 'ARA-Sul', 'country' => 'Mozambique', 'basin' => 'Incomati', 'subcatchment' => 'INC-LOWER',
                'capabilities' => ['flow', 'water_quality'],
            ],
            [
                'code' => 'E-393', 'name' => 'Sabie-Incomati Confluence', 'alt_code' => null,
                'lat' => -25.3330, 'lng' => 32.2830, 'type' => 'river', 'source' => 'surface', 'realtime' => false,
                'org' => 'ARA-Sul', 'country' => 'Mozambique', 'basin' => 'Incomati', 'subcatchment' => 'INC-LOWER',
                'capabilities' => ['flow'],
            ],
            [
                'code' => 'E-413', 'name' => 'Drift Confluencia', 'alt_code' => null,
                'lat' => -25.6830, 'lng' => 32.3330, 'type' => 'river', 'source' => 'surface', 'realtime' => false,
                'org' => 'ARA-Sul', 'country' => 'Mozambique', 'basin' => 'Incomati', 'subcatchment' => 'INC-LOWER',
                'capabilities' => ['flow', 'water_quality'],
            ],
            [
                'code' => 'E-43', 'name' => 'Moamba', 'alt_code' => null,
                'lat' => -25.6000, 'lng' => 32.2400, 'type' => 'river', 'source' => 'surface', 'realtime' => false,
                'org' => 'ARA-Sul', 'country' => 'Mozambique', 'basin' => 'Incomati', 'subcatchment' => 'INC-LOWER',
                'capabilities' => ['flow', 'water_quality'],
            ],
            [
                'code' => 'E-6', 'name' => 'Marracuene', 'alt_code' => null,
                'lat' => -25.7500, 'lng' => 32.6830, 'type' => 'river', 'source' => 'surface', 'realtime' => false,
                'org' => 'ARA-Sul', 'country' => 'Mozambique', 'basin' => 'Incomati', 'subcatchment' => 'INC-LOWER',
                'capabilities' => ['flow', 'water_quality'],
            ],
            [
                'code' => 'E-572', 'name' => 'Sabie Downstream', 'alt_code' => null,
                'lat' => -25.1500, 'lng' => 32.1830, 'type' => 'river', 'source' => 'surface', 'realtime' => false,
                'org' => 'ARA-Sul', 'country' => 'Mozambique', 'basin' => 'Incomati', 'subcatchment' => 'INC-LOWER',
                'capabilities' => ['flow'],
            ],

            // --- Dams / Reservoir storage stations ---
            [
                'code' => 'MAGUGA-DAM-01', 'name' => 'Maguga Dam', 'alt_code' => null,
                'lat' => -26.0792, 'lng' => 31.2492, 'type' => 'dam', 'source' => 'surface', 'realtime' => true,
                'org' => 'KOBWA', 'country' => 'Eswatini', 'basin' => 'Incomati', 'subcatchment' => 'INC-KOMATI',
                'capabilities' => ['dam_level', 'rainfall', 'water_quality'],
            ],
            [
                'code' => 'DRIEKOPPIES-DAM-01', 'name' => 'Driekoppies Dam', 'alt_code' => null,
                'lat' => -25.7000, 'lng' => 31.5500, 'type' => 'dam', 'source' => 'surface', 'realtime' => true,
                'org' => 'KOBWA', 'country' => 'South Africa', 'basin' => 'Incomati', 'subcatchment' => 'INC-KOMATI',
                'capabilities' => ['dam_level', 'rainfall'],
            ],
            [
                'code' => 'JOZINI-DAM-01', 'name' => 'Jozini (Pongolapoort) Dam', 'alt_code' => null,
                'lat' => -27.4208, 'lng' => 32.0736, 'type' => 'dam', 'source' => 'surface', 'realtime' => true,
                'org' => 'DWA-RSA', 'country' => 'South Africa', 'basin' => 'Maputo', 'subcatchment' => 'MAP-PONGOLA',
                'capabilities' => ['dam_level', 'water_quality'],
            ],
            [
                'code' => 'WESTOE-DAM-01', 'name' => 'Westoe Dam', 'alt_code' => null,
                'lat' => -26.5000, 'lng' => 30.6500, 'type' => 'dam', 'source' => 'surface', 'realtime' => false,
                'org' => 'DWA-RSA', 'country' => 'South Africa', 'basin' => 'Maputo', 'subcatchment' => 'MAP-USUTHU',
                'capabilities' => ['dam_level'],
            ],
            [
                'code' => 'JERICHO-DAM-01', 'name' => 'Jericho Dam', 'alt_code' => null,
                'lat' => -26.4000, 'lng' => 30.4830, 'type' => 'dam', 'source' => 'surface', 'realtime' => false,
                'org' => 'DWA-RSA', 'country' => 'South Africa', 'basin' => 'Maputo', 'subcatchment' => 'MAP-USUTHU',
                'capabilities' => ['dam_level'],
            ],
            [
                'code' => 'HEYSHOPE-DAM-01', 'name' => 'Heyshope Dam', 'alt_code' => null,
                'lat' => -27.0000, 'lng' => 30.5170, 'type' => 'dam', 'source' => 'surface', 'realtime' => false,
                'org' => 'DWA-RSA', 'country' => 'South Africa', 'basin' => 'Maputo', 'subcatchment' => 'MAP-PONGOLA',
                'capabilities' => ['dam_level'],
            ],
            [
                'code' => 'BIVANE-DAM-01', 'name' => 'Bivane Dam', 'alt_code' => null,
                'lat' => -27.5167, 'lng' => 31.2500, 'type' => 'dam', 'source' => 'surface', 'realtime' => false,
                'org' => 'DWA-RSA', 'country' => 'South Africa', 'basin' => 'Maputo', 'subcatchment' => 'MAP-PONGOLA',
                'capabilities' => ['dam_level'],
            ],
            [
                'code' => 'E-630', 'name' => 'Corumana Dam (Upstream)', 'alt_code' => null,
                'lat' => -25.2000, 'lng' => 32.0830, 'type' => 'dam', 'source' => 'surface', 'realtime' => true,
                'org' => 'ARA-Sul', 'country' => 'Mozambique', 'basin' => 'Incomati', 'subcatchment' => 'INC-LOWER',
                'capabilities' => ['dam_level', 'water_quality'],
            ],
            [
                'code' => 'E-634', 'name' => 'Corumana Dam (Downstream)', 'alt_code' => null,
                'lat' => -25.2330, 'lng' => 32.1170, 'type' => 'dam', 'source' => 'surface', 'realtime' => true,
                'org' => 'ARA-Sul', 'country' => 'Mozambique', 'basin' => 'Incomati', 'subcatchment' => 'INC-LOWER',
                'capabilities' => ['dam_level', 'water_quality'],
            ],
        ];

        foreach ($stations as $item) {
            $existingId = DB::table('stations')->where('code', $item['code'])->value('id');
            $stationId = $existingId ?? (string) Str::uuid();

            // Insert/Update station
            DB::table('stations')->updateOrInsert(
                ['code' => $item['code']],
                [
                    'id' => $stationId,
                    'name' => $item['name'],
                    'gauge_code' => $item['alt_code'],
                    'latitude' => $item['lat'],
                    'longitude' => $item['lng'],
                    'category' => $item['type'],
                    'water_source' => $item['source'],
                    'water_body_type' => $item['type'] === 'dam' ? 'dam' : 'river',
                    'is_active' => true,
                    'is_real_time' => $item['realtime'],
                    'owner_org' => $item['org'],
                    'country' => $item['country'],
                    'river_basin' => $item['basin'],
                    'summary' => $item['name'].' water monitoring station in the '.$item['basin'].' River Basin.',
                ]
            );

            // Fetch actual ID
            $finalId = DB::table('stations')->where('code', $item['code'])->value('id');

            // Map Capabilities
            foreach ($item['capabilities'] as $cap) {
                DB::table('station_capabilities')->updateOrInsert(
                    ['station_id' => $finalId, 'measurement_type' => $cap],
                    [
                        'is_primary' => true,
                        'installed_at' => Carbon::now()->subYears(5),
                    ]
                );
            }

            // Map subcatchment junction if management_areas exists
            if (Schema::hasTable('management_areas')) {
                $subcatId = DB::table('management_areas')->where('code', $item['subcatchment'])->value('id');
                if ($subcatId) {
                    DB::table('management_area_stations')->updateOrInsert([
                        'management_area_id' => $subcatId,
                        'station_id' => $finalId,
                    ]);
                }
            }
        }
    }

    private function seedComplianceThresholds(): void
    {
        // Default guidelines for water quality parameters from REIWQ Appendix A
        $wqGuidelines = [
            'pH' => ['min' => 6.5, 'max' => 8.5, 'unit' => 'pH units', 'basis' => 'REIWQ Appendix A guideline'],
            'EC' => ['min' => null, 'max' => 150.0, 'unit' => 'mS/m', 'basis' => 'REIWQ Appendix A guideline'],
            'Colour' => ['min' => null, 'max' => 15.0, 'unit' => 'mg/L Pt–Co', 'basis' => 'REIWQ Appendix A guideline'],
            'TUR' => ['min' => null, 'max' => 5.0, 'unit' => 'NTU', 'basis' => 'REIWQ Appendix A guideline'],
            'NH3-N' => ['min' => null, 'max' => 1.0, 'unit' => 'mg/L', 'basis' => 'REIWQ Appendix A guideline'],
            'BOD' => ['min' => null, 'max' => 5.0, 'unit' => 'mg/L', 'basis' => 'REIWQ Appendix A guideline'],
            'COD' => ['min' => null, 'max' => 10.0, 'unit' => 'mg/L', 'basis' => 'REIWQ Appendix A guideline'],
            'Cl' => ['min' => null, 'max' => 250.0, 'unit' => 'mg/L', 'basis' => 'REIWQ Appendix A guideline'],
            'DO' => ['min' => 75.0, 'max' => null, 'unit' => '% saturation', 'basis' => 'REIWQ Appendix A guideline'],
            'F' => ['min' => null, 'max' => 0.75, 'unit' => 'mg/L', 'basis' => 'REIWQ Appendix A guideline'],
            'NO3' => ['min' => null, 'max' => 50.0, 'unit' => 'mg/L', 'basis' => 'REIWQ Appendix A guideline'],
            'SO4' => ['min' => null, 'max' => 250.0, 'unit' => 'mg/L', 'basis' => 'REIWQ Appendix A guideline'],
            'TC' => ['min' => null, 'max' => 10000.0, 'unit' => 'CFU/100 mL', 'basis' => 'REIWQ Appendix A guideline'],
            'FC' => ['min' => null, 'max' => 2000.0, 'unit' => 'CFU/100 mL', 'basis' => 'REIWQ Appendix A guideline'],
            'FS' => ['min' => null, 'max' => 1000.0, 'unit' => 'CFU/100 mL', 'basis' => 'REIWQ Appendix A guideline'],
            'Cu' => ['min' => null, 'max' => 0.02, 'unit' => 'mg/L', 'basis' => 'REIWQ Appendix A guideline'],
            'Mn' => ['min' => null, 'max' => 0.3, 'unit' => 'mg/L', 'basis' => 'REIWQ Appendix A guideline'],
        ];

        $stations = DB::table('stations')->get();

        foreach ($stations as $station) {
            foreach ($wqGuidelines as $code => $limit) {
                $paramId = DB::table('water_quality_parameters')->where('code', $code)->value('id');
                if (! $paramId) {
                    continue;
                }

                $existingId = DB::table('compliance_thresholds')
                    ->where('station_id', $station->id)
                    ->where('data_type', 'water_quality')
                    ->where('parameter_id', $paramId)
                    ->value('id');

                DB::table('compliance_thresholds')->updateOrInsert(
                    [
                        'station_id' => $station->id,
                        'data_type' => 'water_quality',
                        'parameter_id' => $paramId,
                    ],
                    [
                        'id' => $existingId ?? (string) Str::uuid(),
                        'min_value' => $limit['min'],
                        'max_value' => $limit['max'],
                        'unit' => $limit['unit'],
                        'notes' => $limit['basis'],
                    ]
                );
            }
        }
    }

    private function seedMockMeasurements(string $userId): void
    {
        $stations = DB::table('stations')->get();

        // Clear existing mock measurements to keep database fresh
        DB::table('measurements')->delete();

        foreach ($stations as $station) {
            $caps = DB::table('station_capabilities')->where('station_id', $station->id)->pluck('measurement_type')->toArray();

            foreach ($caps as $cap) {
                $value = 0.0;
                $unit = '';
                $paramId = null;

                if ($cap === 'flow') {
                    // Seed mock flow rate
                    $value = rand(8, 48) / 10.0;
                    $unit = 'm³/s';
                } elseif ($cap === 'dam_level') {
                    $value = rand(15, 105); // Percentage storage
                    $unit = '%';
                } elseif ($cap === 'rainfall') {
                    $value = rand(0, 110);
                    $unit = 'mm';
                } elseif ($cap === 'water_quality') {
                    // Seed specifically Electrical Conductivity (EC)
                    $param = DB::table('water_quality_parameters')->where('code', 'EC')->first();
                    if ($param) {
                        $paramId = $param->id;
                        $value = rand(20, 240); // Standard EC readings range
                        $unit = 'mS/m';
                    }
                }

                if ($value > 0 || $cap === 'water_quality') {
                    DB::table('measurements')->insert([
                        'id' => (string) Str::uuid(),
                        'station_id' => $station->id,
                        'measurement_type' => $cap,
                        'parameter_id' => $paramId,
                        'value' => $value,
                        'unit' => $unit,
                        'date' => Carbon::now()->subMinutes(rand(10, 120)),
                        'status' => 'approved',
                        'submitted_by_id' => $userId,
                        'submitted_at' => Carbon::now()->subHours(2),
                        'reviewed_by_id' => $userId,
                        'reviewed_at' => Carbon::now()->subHour(),
                        'review_notes' => 'Pre-seeded operational status data.',
                        'is_self_override' => true,
                    ]);
                }
            }
        }
    }
}
