import * as XLSX from 'xlsx';

export type MeasurementType = 'flow' | 'dam' | 'wq' | 'rainfall' | 'groundwater';

// ─── Column definitions per measurement type ──────────────────────────────────

const FLOW_HEADERS = ['station_code', 'value', 'unit', 'date'];
const FLOW_ALLOWED  = ['Station code (must exist in INMACOM)', 'Numeric (m³/s)', 'Optional — defaults to m³/s', 'YYYY-MM-DD'];
const FLOW_EXAMPLE  = ['GS-01', 12.4, 'm³/s', '2026-05-01'];

const DAM_HEADERS  = ['station_code', 'value', 'unit', 'date'];
const DAM_ALLOWED  = ['Station code (must exist in INMACOM)', 'Numeric (m)', 'Optional — defaults to m', 'YYYY-MM-DD'];
const DAM_EXAMPLE  = ['D-03', 45.2, 'm', '2026-05-01'];

const WQ_HEADERS   = ['station_code', 'parameter_code', 'value', 'unit', 'date'];
const WQ_ALLOWED   = ['Station code (must exist in INMACOM)', 'Parameter code (e.g. pH, DO, EC)', 'Numeric', 'Optional', 'YYYY-MM-DD'];
const WQ_EXAMPLE   = ['WQ-05', 'pH', 7.4, '', '2026-05-01'];

const RAIN_HEADERS = ['station_code', 'value', 'unit', 'date'];
const RAIN_ALLOWED = ['Station code (must exist in INMACOM)', 'Numeric (mm)', 'Optional — defaults to mm', 'YYYY-MM-DD'];
const RAIN_EXAMPLE = ['R-12', 23.5, 'mm', '2026-05-01'];

const GW_HEADERS   = ['station_code', 'value', 'unit', 'date'];
const GW_ALLOWED   = ['Station code (must exist in INMACOM)', 'Numeric (m)', 'Optional — defaults to m', 'YYYY-MM-DD'];
const GW_EXAMPLE   = ['GW-07', 3.1, 'm', '2026-05-01'];

const TYPE_CONFIG: Record<MeasurementType, { headers: string[]; allowed: (string | number)[]; example: (string | number)[] }> = {
    flow:        { headers: FLOW_HEADERS, allowed: FLOW_ALLOWED, example: FLOW_EXAMPLE },
    dam:         { headers: DAM_HEADERS,  allowed: DAM_ALLOWED,  example: DAM_EXAMPLE },
    wq:          { headers: WQ_HEADERS,   allowed: WQ_ALLOWED,   example: WQ_EXAMPLE },
    rainfall:    { headers: RAIN_HEADERS, allowed: RAIN_ALLOWED, example: RAIN_EXAMPLE },
    groundwater: { headers: GW_HEADERS,   allowed: GW_ALLOWED,   example: GW_EXAMPLE },
};

// ─── Download helper ──────────────────────────────────────────────────────────

export function downloadMeasurementTemplate(type: MeasurementType, format: 'csv' | 'xlsx'): void {
    const { headers, allowed, example } = TYPE_CONFIG[type];
    const data = [headers, allowed, example];

    const ws = XLSX.utils.aoa_to_sheet(data);

    // Column widths
    ws['!cols'] = headers.map(() => ({ wch: 22 }));

    // Bold header row
    headers.forEach((_, col) => {
        const cell = ws[XLSX.utils.encode_cell({ r: 0, c: col })];
        if (cell) cell.s = { font: { bold: true } };
    });

    const wb = XLSX.utils.book_new();
    const sheetName = `${type}_measurements`;
    XLSX.utils.book_append_sheet(wb, ws, sheetName);

    const fileName = `${sheetName}_template.${format}`;
    if (format === 'xlsx') {
        XLSX.writeFile(wb, fileName);
    } else {
        XLSX.writeFile(wb, fileName, { bookType: 'csv' });
    }
}
