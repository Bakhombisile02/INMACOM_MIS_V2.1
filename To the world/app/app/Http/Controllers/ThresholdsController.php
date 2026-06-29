<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ThresholdsController extends Controller
{
    /**
     * Default guidelines for water quality parameters from REIWQ Appendix A
     */
    private array $wqGuidelines = [
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

    public function index(Request $request): Response
    {
        $this->requireManagerOrAdmin();

        $user = auth()->user();
        $canManage = ($user?->canApprove() ?? false);

        // On-the-fly Seeding for user categories if empty
        $this->seedUserCategoriesIfEmpty();
        $this->seedAllocationsIfEmpty();

        // 1. Load active Water Quality parameters list
        $parameters = DB::table('water_quality_parameters')
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get(['id', 'code', 'name', 'default_unit', 'is_priority_pollutant']);

        // 2. Build WQ Compliance Thresholds query
        $wqQuery = DB::table('compliance_thresholds as ct')
            ->join('stations as s', 's.id', '=', 'ct.station_id')
            ->join('water_quality_parameters as wqp', 'wqp.id', '=', 'ct.parameter_id')
            ->select([
                'ct.id',
                'ct.station_id',
                'ct.parameter_id',
                'ct.data_type',
                'ct.min_value',
                'ct.max_value',
                'ct.unit',
                'ct.notes',
                's.code as station_code',
                's.name as station_name',
                's.country as station_country',
                's.river_basin as station_basin',
                'wqp.code as parameter_code',
                'wqp.name as parameter_name',
                'wqp.is_priority_pollutant',
            ]);

        // Filters
        if ($search = $request->string('search')->toString()) {
            $wqQuery->where(function ($q) use ($search) {
                $q->whereRaw('lower(s.code) like ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhereRaw('lower(s.name) like ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhereRaw('lower(wqp.code) like ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhereRaw('lower(wqp.name) like ?', ['%'.mb_strtolower($search).'%']);
            });
        }

        if ($basin = $request->string('basin')->toString()) {
            $wqQuery->where('s.river_basin', $basin);
        }

        if ($paramCode = $request->string('parameter')->toString()) {
            $wqQuery->where('wqp.code', $paramCode);
        }

        if ($request->boolean('is_priority')) {
            $wqQuery->where('wqp.is_priority_pollutant', true);
        }

        if ($request->boolean('is_custom')) {
            $wqQuery->where('ct.notes', 'not like', 'REIWQ%');
        }

        $complianceThresholds = $wqQuery
            ->orderBy('s.name')
            ->orderBy('wqp.display_order')
            ->paginate(30)
            ->withQueryString()
            ->through(fn ($row) => [
                'id' => $row->id,
                'station_id' => $row->station_id,
                'station_code' => $row->station_code,
                'station_name' => $row->station_name,
                'station_country' => $row->station_country,
                'station_basin' => $row->station_basin,
                'parameter_id' => $row->parameter_id,
                'parameter_code' => $row->parameter_code,
                'parameter_name' => $row->parameter_name,
                'is_priority_pollutant' => (bool) $row->is_priority_pollutant,
                'min_value' => $row->min_value !== null ? (float) $row->min_value : null,
                'max_value' => $row->max_value !== null ? (float) $row->max_value : null,
                'unit' => $row->unit,
                'notes' => $row->notes,
                'is_custom' => strpos($row->notes ?? '', 'REIWQ') === false,
            ]);

        // 3. Load IIMA Ecological Flow Requirements
        $eflowRequirements = DB::table('iima_eflow_requirements as er')
            ->leftJoin('management_areas as ma', 'ma.id', '=', 'er.subcatchment_id')
            ->leftJoin('stations as s', 's.id', '=', 'er.station_id')
            ->select([
                'er.id',
                'er.river',
                'er.key_point',
                'er.mean_annual_mm3',
                'er.min_flow_m3_s',
                'er.source_article',
                'er.note',
                'ma.name as subcatchment_name',
                'ma.code as subcatchment_code',
                's.code as station_code',
                's.name as station_name',
            ])
            ->orderBy('er.river')
            ->orderBy('er.key_point')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'river' => $row->river,
                'key_point' => $row->key_point,
                'mean_annual_mm3' => (float) $row->mean_annual_mm3,
                'min_flow_m3_s' => (float) $row->min_flow_m3_s,
                'source_article' => $row->source_article,
                'note' => $row->note,
                'subcatchment_name' => $row->subcatchment_name,
                'subcatchment_code' => $row->subcatchment_code,
                'station_code' => $row->station_code,
                'station_name' => $row->station_name,
            ]);

        // 4. Load IIMA Water Allocations
        $allocations = DB::table('iima_allocations as al')
            ->join('management_areas as ma', 'ma.id', '=', 'al.subcatchment_id')
            ->join('iima_user_categories as uc', 'uc.code', '=', 'al.user_category')
            ->select([
                'al.id',
                'al.subcatchment_id',
                'al.country',
                'al.user_category',
                'al.allocation_mm3_a',
                'al.effective_from',
                'al.note',
                'ma.name as subcatchment_name',
                'ma.code as subcatchment_code',
                'uc.name as user_category_name',
            ])
            ->orderBy('ma.name')
            ->orderBy('al.country')
            ->orderBy('uc.name')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'subcatchment_id' => $row->subcatchment_id,
                'subcatchment_name' => $row->subcatchment_name,
                'subcatchment_code' => $row->subcatchment_code,
                'country' => $row->country,
                'user_category' => $row->user_category,
                'user_category_name' => $row->user_category_name,
                'allocation_mm3_a' => (float) $row->allocation_mm3_a,
                'effective_from' => (int) $row->effective_from,
                'note' => $row->note,
            ]);

        // 5. Load Active Subcatchments & User Categories (for Modal dropdown selects)
        $subcatchments = DB::table('management_areas')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $userCategories = DB::table('iima_user_categories')
            ->orderBy('name')
            ->get(['code', 'name']);

        // 6. Load Writable Hazard Indicators settings
        $hazardSettings = $this->getHazardSettings();

        return Inertia::render('Thresholds/Index', [
            'complianceThresholds' => $complianceThresholds,
            'eflowRequirements' => $eflowRequirements,
            'allocations' => $allocations,
            'parameters' => $parameters,
            'subcatchments' => $subcatchments,
            'userCategories' => $userCategories,
            'hazardSettings' => $hazardSettings,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'basin' => $request->string('basin')->toString(),
                'parameter' => $request->string('parameter')->toString(),
                'is_priority' => $request->boolean('is_priority'),
                'is_custom' => $request->boolean('is_custom'),
            ],
            'canManage' => $canManage,
        ]);
    }

    public function updateCompliance(Request $request, string $id): RedirectResponse
    {
        $this->requireManagerOrAdmin();

        $validated = $request->validate([
            'min_value' => ['nullable', 'numeric'],
            'max_value' => ['nullable', 'numeric'],
        ]);

        if ($validated['min_value'] !== null && $validated['max_value'] !== null && $validated['max_value'] < $validated['min_value']) {
            return back()->withErrors(['max_value' => 'Maximum value must be greater than or equal to minimum value.']);
        }

        DB::table('compliance_thresholds')
            ->where('id', $id)
            ->update([
                'min_value' => $validated['min_value'],
                'max_value' => $validated['max_value'],
                'notes' => 'Station-specific limit (Customized)',
            ]);

        return back();
    }

    public function resetCompliance(string $id): RedirectResponse
    {
        $this->requireManagerOrAdmin();

        // 1. Get the parameter code for this threshold
        $threshold = DB::table('compliance_thresholds as ct')
            ->join('water_quality_parameters as wqp', 'wqp.id', '=', 'ct.parameter_id')
            ->where('ct.id', $id)
            ->select(['wqp.code'])
            ->first();

        if (! $threshold || ! isset($this->wqGuidelines[$threshold->code])) {
            return back()->withErrors(['reset' => 'Default values are not defined for this parameter.']);
        }

        $default = $this->wqGuidelines[$threshold->code];

        DB::table('compliance_thresholds')
            ->where('id', $id)
            ->update([
                'min_value' => $default['min'],
                'max_value' => $default['max'],
                'notes' => $default['basis'],
            ]);

        return back();
    }

    public function updateGroupCompliance(Request $request): RedirectResponse
    {
        $this->requireManagerOrAdmin();

        $validated = $request->validate([
            'parameter_code' => ['required', 'string'],
            'scope' => ['required', 'string', 'in:system,basin,subcatchment'],
            'basin' => ['required_if:scope,basin', 'nullable', 'string'],
            'subcatchment_id' => ['required_if:scope,subcatchment', 'nullable', 'uuid'],
            'min_value' => ['nullable', 'numeric'],
            'max_value' => ['nullable', 'numeric'],
        ]);

        if ($validated['min_value'] !== null && $validated['max_value'] !== null && $validated['max_value'] < $validated['min_value']) {
            return back()->withErrors(['max_value' => 'Maximum value must be greater than or equal to minimum value.']);
        }

        $paramId = DB::table('water_quality_parameters')
            ->where('code', $validated['parameter_code'])
            ->value('id');

        if (! $paramId) {
            return back()->withErrors(['parameter_code' => 'Invalid water quality parameter code.']);
        }

        // Build the compliance thresholds query to bulk update
        $query = DB::table('compliance_thresholds')
            ->where('parameter_id', $paramId)
            ->where('data_type', 'water_quality');

        if ($validated['scope'] === 'basin') {
            $query->whereIn('station_id', function ($sub) use ($validated) {
                $sub->select('id')->from('stations')->where('river_basin', $validated['basin']);
            });
        } elseif ($validated['scope'] === 'subcatchment') {
            $query->whereIn('station_id', function ($sub) use ($validated) {
                $sub->select('station_id')->from('management_area_stations')->where('management_area_id', $validated['subcatchment_id']);
            });
        }

        $query->update([
            'min_value' => $validated['min_value'],
            'max_value' => $validated['max_value'],
            'notes' => 'Group limit (Customized)',
        ]);

        return back();
    }

    public function updateEflow(Request $request, string $id): RedirectResponse
    {
        $this->requireManagerOrAdmin();

        $validated = $request->validate([
            'mean_annual_mm3' => ['required', 'numeric', 'min:0'],
            'min_flow_m3_s' => ['required', 'numeric', 'min:0'],
        ]);

        DB::table('iima_eflow_requirements')
            ->where('id', $id)
            ->update([
                'mean_annual_mm3' => $validated['mean_annual_mm3'],
                'min_flow_m3_s' => $validated['min_flow_m3_s'],
            ]);

        return back();
    }

    // --- Water Allocations CRUD Actions ---

    public function storeAllocation(Request $request): RedirectResponse
    {
        $this->requireManagerOrAdmin();

        $validated = $request->validate([
            'subcatchment_id' => ['required', 'uuid', 'exists:management_areas,id'],
            'country' => ['required', 'string', 'in:Eswatini,South Africa,Mozambique,KOBWA,TPTC'],
            'user_category' => ['required', 'string', 'exists:iima_user_categories,code'],
            'allocation_mm3_a' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['required', 'integer', 'min:1900', 'max:2100'],
            'note' => ['nullable', 'string'],
        ]);

        // Check if an allocation with identical compound primary keys already exists
        $exists = DB::table('iima_allocations')
            ->where('subcatchment_id', $validated['subcatchment_id'])
            ->where('country', $validated['country'])
            ->where('user_category', $validated['user_category'])
            ->where('effective_from', $validated['effective_from'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['note' => 'An identical water allocation already exists for this subcatchment, country, category, and effective year.']);
        }

        DB::table('iima_allocations')->insert([
            'id' => (string) Str::uuid(),
            'subcatchment_id' => $validated['subcatchment_id'],
            'country' => $validated['country'],
            'user_category' => $validated['user_category'],
            'allocation_mm3_a' => $validated['allocation_mm3_a'],
            'effective_from' => $validated['effective_from'],
            'note' => $validated['note'] ?? 'Created manually',
        ]);

        return back();
    }

    public function updateAllocation(Request $request, string $id): RedirectResponse
    {
        $this->requireManagerOrAdmin();

        $validated = $request->validate([
            'allocation_mm3_a' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['required', 'integer', 'min:1900', 'max:2100'],
            'note' => ['nullable', 'string'],
        ]);

        DB::table('iima_allocations')
            ->where('id', $id)
            ->update([
                'allocation_mm3_a' => $validated['allocation_mm3_a'],
                'effective_from' => $validated['effective_from'],
                'note' => $validated['note'],
            ]);

        return back();
    }

    public function destroyAllocation(string $id): RedirectResponse
    {
        $this->requireManagerOrAdmin();

        DB::table('iima_allocations')
            ->where('id', $id)
            ->delete();

        return back();
    }

    // --- Hazard alert configurations CRUD Actions ---

    public function updateHazard(Request $request): RedirectResponse
    {
        $this->requireManagerOrAdmin();

        $validated = $request->validate([
            'flood_watch' => ['required', 'numeric', 'min:0', 'max:10'],
            'flood_moderate' => ['required', 'numeric', 'min:0', 'max:10'],
            'flood_severe' => ['required', 'numeric', 'min:0', 'max:10'],
            'drought_watch' => ['required', 'numeric', 'min:0', 'max:10'],
            'drought_moderate' => ['required', 'numeric', 'min:0', 'max:10'],
            'drought_severe' => ['required', 'numeric', 'min:0', 'max:10'],
            'chemical_a' => ['required', 'numeric', 'min:0'],
            'chemical_b' => ['required', 'numeric', 'min:0'],
            'chemical_c' => ['required', 'numeric', 'min:0'],
            'chemical_d' => ['required', 'numeric', 'min:0'],
            'chemical_x' => ['required', 'numeric', 'min:0'],
            'coliform_orange' => ['required', 'numeric', 'min:0'],
            'coliform_red' => ['required', 'numeric', 'min:0'],
            'fish_kill_orange' => ['required', 'numeric', 'min:0'],
            'fish_kill_red_count' => ['required', 'numeric', 'min:0'],
            'fish_kill_red_mass' => ['required', 'numeric', 'min:0'],
        ]);

        $path = storage_path('app/hazard_settings.json');
        file_put_contents($path, json_encode($validated, JSON_PRETTY_PRINT));

        return back();
    }

    // --- Helpers ---

    private function requireManagerOrAdmin(): void
    {
        $user = auth()->user();
        abort_unless($user && $user->canApprove(), 403);
    }

    private function getHazardSettings(): array
    {
        $path = storage_path('app/hazard_settings.json');
        if (! file_exists($path)) {
            $defaults = [
                'flood_watch' => 2.5,
                'flood_moderate' => 5.0,
                'flood_severe' => 7.5,
                'drought_watch' => 2.5,
                'drought_moderate' => 5.0,
                'drought_severe' => 7.5,
                'chemical_a' => 0.5,
                'chemical_b' => 5.0,
                'chemical_c' => 50.0,
                'chemical_d' => 500.0,
                'chemical_x' => 2500.0,
                'coliform_orange' => 4000.0,
                'coliform_red' => 10000.0,
                'fish_kill_orange' => 1.0,
                'fish_kill_red_count' => 2.0,
                'fish_kill_red_mass' => 1000.0,
            ];
            file_put_contents($path, json_encode($defaults, JSON_PRETTY_PRINT));

            return $defaults;
        }

        return json_decode(file_get_contents($path), true);
    }

    private function seedUserCategoriesIfEmpty(): void
    {
        if (DB::table('iima_user_categories')->count() === 0) {
            DB::table('iima_user_categories')->insert([
                ['id' => (string) Str::uuid(), 'code' => 'domestic', 'name' => 'Domestic & Municipal', 'priority_order' => 1],
                ['id' => (string) Str::uuid(), 'code' => 'irrigation', 'name' => 'Irrigation Agriculture', 'priority_order' => 2],
                ['id' => (string) Str::uuid(), 'code' => 'industrial', 'name' => 'Industry & Mining', 'priority_order' => 3],
                ['id' => (string) Str::uuid(), 'code' => 'forestry', 'name' => 'Commercial Forestry', 'priority_order' => 4],
                ['id' => (string) Str::uuid(), 'code' => 'other', 'name' => 'Other Water Uses', 'priority_order' => 5],
            ]);
        }
    }

    private function seedAllocationsIfEmpty(): void
    {
        if (DB::table('iima_allocations')->count() === 0) {
            $komatiId = DB::table('management_areas')->where('code', 'INC-KOMATI')->value('id');
            $usuthuId = DB::table('management_areas')->where('code', 'MAP-USUTHU')->value('id');

            if ($komatiId && $usuthuId) {
                DB::table('iima_allocations')->insert([
                    [
                        'id' => (string) Str::uuid(),
                        'subcatchment_id' => $komatiId,
                        'country' => 'South Africa',
                        'user_category' => 'irrigation',
                        'allocation_mm3_a' => 220.0,
                        'effective_from' => 2026,
                        'note' => 'IIMA Table 4-1 default allocation',
                    ],
                    [
                        'id' => (string) Str::uuid(),
                        'subcatchment_id' => $komatiId,
                        'country' => 'Eswatini',
                        'user_category' => 'irrigation',
                        'allocation_mm3_a' => 120.0,
                        'effective_from' => 2026,
                        'note' => 'IIMA Table 4-1 default allocation',
                    ],
                    [
                        'id' => (string) Str::uuid(),
                        'subcatchment_id' => $usuthuId,
                        'country' => 'Eswatini',
                        'user_category' => 'domestic',
                        'allocation_mm3_a' => 15.5,
                        'effective_from' => 2026,
                        'note' => 'IAAP-10 reference allocation',
                    ],
                    [
                        'id' => (string) Str::uuid(),
                        'subcatchment_id' => $usuthuId,
                        'country' => 'Mozambique',
                        'user_category' => 'irrigation',
                        'allocation_mm3_a' => 45.0,
                        'effective_from' => 2026,
                        'note' => 'IAAP-10 reference allocation',
                    ],
                ]);
            }
        }
    }
}
