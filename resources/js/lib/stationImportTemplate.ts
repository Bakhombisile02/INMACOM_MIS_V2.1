import * as XLSX from 'xlsx';

const HEADERS = [
    'code',
    'name',
    'country',
    'category',
    'water_source',
    'water_body_type',
    'latitude',
    'longitude',
    'river_basin',
    'owner_org',
    'telemetry_system',
    'gauge_code',
    'summary',
    'is_active',
    'is_real_time',
];

const ALLOWED_VALUES_ROW = [
    'Unique code (max 50)',
    'Full station name',
    'Mozambique | South Africa | Eswatini',
    'river_gauge | dam | borehole | rainfall_station | lake | wetland | other',
    'surface | ground | atmospheric',
    'river | dam | borehole | lake | wetland',
    'Decimal degrees e.g. -25.4937',
    'Decimal degrees e.g. 31.9123',
    'Optional',
    'Optional',
    'Optional',
    'Optional',
    'Optional (max 2000 chars)',
    'true | false (default: true)',
    'true | false (default: false)',
];

const EXAMPLE_ROW_1 = [
    'GS-99',
    'Komati River at Border',
    'South Africa',
    'river_gauge',
    'surface',
    'river',
    -25.6234,
    31.9123,
    'Komati',
    'DWS',
    'IoT',
    'KOM-1',
    'Main flow gauge at the Komati border crossing.',
    'true',
    'false',
];

const EXAMPLE_ROW_2 = [
    'E-999',
    'Corumana Rainfall Station',
    'Mozambique',
    'rainfall_station',
    'atmospheric',
    'river',
    -25.1234,
    32.9123,
    '',
    '',
    '',
    '',
    '',
    'true',
    'false',
];

export function downloadTemplate(format: 'csv' | 'xlsx'): void {
    const data = [HEADERS, ALLOWED_VALUES_ROW, EXAMPLE_ROW_1, EXAMPLE_ROW_2];

    const ws = XLSX.utils.aoa_to_sheet(data);

    // Column widths
    ws['!cols'] = HEADERS.map((_, i) => ({ wch: [16, 30, 24, 36, 20, 20, 18, 18, 20, 24, 22, 16, 40, 14, 14][i] ?? 16 }));

    if (format === 'xlsx') {
        // Style header row bold
        for (let c = 0; c < HEADERS.length; c++) {
            const cellRef = XLSX.utils.encode_cell({ r: 0, c });
            if (ws[cellRef]) {
                ws[cellRef].s = { font: { bold: true }, fill: { fgColor: { rgb: '1C7ED6' } } };
            }
        }
    }

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Stations');

    const filename = `INMACOM_Station_Import_Template.${format}`;
    XLSX.writeFile(wb, filename, { bookType: format === 'csv' ? 'csv' : 'xlsx' });
}
