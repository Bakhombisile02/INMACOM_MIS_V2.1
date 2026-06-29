<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ManagementAreasSeeder extends Seeder
{
    /**
     * Seed all 15 IIMA management areas (subcatchments) for the Incomati and Maputo basins.
     * Based on INMACOM IAAP-10 / DDR Iteration 2 definitions.
     */
    public function run(): void
    {
        if (! Schema::hasTable('management_areas')) {
            return;
        }

        $areas = [
            // ── Incomati Basin ──────────────────────────────────────────────────
            [
                'code' => 'INC-KOMATI',
                'name' => 'Komati',
                'basin' => 'Incomati',
                'country' => 'South Africa / Eswatini / Mozambique',
                'is_active' => true,
                'description' => 'Komati River subcatchment. The main tributary of the Incomati, rising in the Mpumalanga highlands and flowing through Eswatini before re-entering South Africa at Komatipoort, then Mozambique. Maguga and Driekoppies dams are the primary storage infrastructure.',
            ],
            [
                'code' => 'INC-CROCODILE',
                'name' => 'Crocodile',
                'basin' => 'Incomati',
                'country' => 'South Africa / Mozambique',
                'is_active' => true,
                'description' => 'Crocodile River subcatchment (RSA). Drains the southern slopes of the Mpumalanga escarpment through the Kruger National Park before confluencing with the Incomati at Komatipoort.',
            ],
            [
                'code' => 'INC-SABIE',
                'name' => 'Sabie',
                'basin' => 'Incomati',
                'country' => 'South Africa / Mozambique',
                'is_active' => true,
                'description' => 'Sabie River subcatchment. Rises near Sabie town in Mpumalanga, flows through the Kruger NP, crosses into Mozambique at Gaza Province and joins the Incomati. Important for ecology in the TFCA.',
            ],
            [
                'code' => 'INC-MASSINTONTO',
                'name' => 'Massintonto',
                'basin' => 'Incomati',
                'country' => 'Mozambique',
                'is_active' => true,
                'description' => 'Massintonto subcatchment in Mozambique. A right-bank tributary joining the lower Incomati.',
            ],
            [
                'code' => 'INC-UANETZE',
                'name' => 'Uanetze',
                'basin' => 'Incomati',
                'country' => 'Mozambique',
                'is_active' => true,
                'description' => 'Uanetze subcatchment in Mozambique. Left-bank tributary of the lower Incomati.',
            ],
            [
                'code' => 'INC-MAZIMECHOPES',
                'name' => 'Mazimechopes',
                'basin' => 'Incomati',
                'country' => 'Mozambique',
                'is_active' => true,
                'description' => 'Mazimechopes subcatchment in lower Mozambique. Drains into the coastal zone of the Incomati.',
            ],
            [
                'code' => 'INC-LOWER',
                'name' => 'Lower Incomati',
                'basin' => 'Incomati',
                'country' => 'Mozambique',
                'is_active' => true,
                'description' => 'Lower Incomati mainstem in Mozambique from Ressano Garcia to the Indian Ocean. Includes the Incomati mouth near Xai-Xai. Critical eflow compliance zone (E-23 / X2H036).',
            ],
            // ── Maputo Basin ─────────────────────────────────────────────────────
            [
                'code' => 'MAP-LUSUSHWANA',
                'name' => 'Lusushwana',
                'basin' => 'Maputo',
                'country' => 'Eswatini',
                'is_active' => true,
                'description' => 'Lusushwana River subcatchment in western Eswatini. Left-bank tributary of the Usuthu.',
            ],
            [
                'code' => 'MAP-MPULUZI',
                'name' => 'Mpuluzi',
                'basin' => 'Maputo',
                'country' => 'South Africa / Eswatini',
                'is_active' => true,
                'description' => 'Mpuluzi River subcatchment straddling the RSA–Eswatini border. Tributary of the Usuthu.',
            ],
            [
                'code' => 'MAP-USUTHU',
                'name' => 'Usuthu',
                'basin' => 'Maputo',
                'country' => 'South Africa / Eswatini / Mozambique',
                'is_active' => true,
                'description' => 'Usuthu (Lusutfu) River mainstem subcatchment. Largest tributary of the Maputo River system, flowing through Eswatini and entering Mozambique before joining the Maputo. GS-16 (Big Bend) is the key IIMA compliance gauge.',
            ],
            [
                'code' => 'MAP-NGWEMPISI',
                'name' => 'Ngwempisi',
                'basin' => 'Maputo',
                'country' => 'South Africa / Eswatini',
                'is_active' => true,
                'description' => 'Ngwempisi River subcatchment in Eswatini. Right-bank tributary of the Usuthu.',
            ],
            [
                'code' => 'MAP-MKHONDVO',
                'name' => 'Mkhondvo',
                'basin' => 'Maputo',
                'country' => 'South Africa / Eswatini',
                'is_active' => true,
                'description' => 'Mkhondvo River subcatchment straddling the RSA–Eswatini border. Left-bank tributary of the Usuthu.',
            ],
            [
                'code' => 'MAP-NGWAVUMA',
                'name' => 'Ngwavuma',
                'basin' => 'Maputo',
                'country' => 'South Africa / Eswatini / Mozambique',
                'is_active' => true,
                'description' => 'Ngwavuma River subcatchment. Flows from the RSA highlands through Eswatini into Mozambique, joining the Usuthu before the Maputo confluence.',
            ],
            [
                'code' => 'MAP-PONGOLA',
                'name' => 'Pongola',
                'basin' => 'Maputo',
                'country' => 'South Africa / Mozambique',
                'is_active' => true,
                'description' => 'Pongola River subcatchment in KwaZulu-Natal (RSA) and Mozambique. Pongolapoort (Jozini) Dam is the primary storage. Joins the Usuthu at Ndumo.',
            ],
            [
                'code' => 'MAP-MAPUTO',
                'name' => 'Lower Maputo',
                'basin' => 'Maputo',
                'country' => 'Mozambique',
                'is_active' => true,
                'description' => 'Lower Maputo River mainstem in Mozambique. Receives the Usuthu and flows to Maputo Bay. The compliance gauge is E-4 at Salamanga.',
            ],
        ];

        foreach ($areas as $area) {
            $existingId = DB::table('management_areas')
                ->where('code', $area['code'])
                ->value('id');

            DB::table('management_areas')->updateOrInsert(
                ['code' => $area['code']],
                [
                    'id' => $existingId ?? (string) Str::uuid(),
                    'name' => $area['name'],
                    'basin' => $area['basin'],
                    'is_active' => $area['is_active'],
                    'country' => $area['country'],
                    'description' => $area['description'],
                ]
            );
        }

        $this->command->info('Management areas seeded: '.count($areas).' records.');
    }
}
