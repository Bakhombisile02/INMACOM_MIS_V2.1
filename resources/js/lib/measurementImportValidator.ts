import type { MeasurementType } from './measurementImportTemplate';

// ─── Types ────────────────────────────────────────────────────────────────────

export interface CellError {
    field: string;
    message: string;
    severity: 'error' | 'warning' | 'fixed';
}

export interface NormalizedMeasurementRow {
    station_code: string;
    parameter_code?: string;
    value: number;
    unit: string;
    date: string;
}

export interface ValidatedMeasurementRow {
    index: number;
    normalized: NormalizedMeasurementRow;
    errors: CellError[];
    hasBlockingError: boolean;
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

const DATE_RE = /^\d{4}-\d{2}-\d{2}$/;

/** Attempts to parse a date in common formats and normalise to YYYY-MM-DD. */
function normalizeDate(raw: unknown): { value: string; fixed: boolean } | null {
    if (raw == null || raw === '') return null;
    const s = String(raw).trim();

    // Already correct
    if (DATE_RE.test(s)) return { value: s, fixed: false };

    // Try Excel serial number (numeric)
    const num = Number(s);
    if (!isNaN(num) && num > 10000) {
        // Excel date serial: days since 1899-12-30
        const d = new Date((num - 25569) * 86400 * 1000);
        const iso = d.toISOString().split('T')[0];
        return { value: iso, fixed: true };
    }

    // Try common separators: DD/MM/YYYY, MM/DD/YYYY, DD-MM-YYYY
    const dmy = s.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
    if (dmy) {
        const [, d, m, y] = dmy;
        const iso = `${y}-${m.padStart(2, '0')}-${d.padStart(2, '0')}`;
        return { value: iso, fixed: true };
    }

    // Let JS parse anything else (e.g. "01 May 2026")
    const parsed = new Date(s);
    if (!isNaN(parsed.getTime())) {
        return { value: parsed.toISOString().split('T')[0], fixed: true };
    }

    return null;
}

// ─── Main validator ───────────────────────────────────────────────────────────

export function validateMeasurementRows(
    rows: Record<string, unknown>[],
    type: MeasurementType,
): ValidatedMeasurementRow[] {
    return rows.map((row, index) => {
        const errors: CellError[] = [];

        // station_code
        const rawCode = String(row['station_code'] ?? row['Station Code'] ?? row['StationCode'] ?? '').trim();
        if (!rawCode) {
            errors.push({ field: 'station_code', message: 'Station code is required.', severity: 'error' });
        }

        // parameter_code (WQ only)
        let paramCode: string | undefined;
        if (type === 'wq') {
            const rawParam = String(row['parameter_code'] ?? row['Parameter Code'] ?? row['ParameterCode'] ?? '').trim();
            if (!rawParam) {
                errors.push({ field: 'parameter_code', message: 'Parameter code is required for water quality.', severity: 'error' });
            }
            paramCode = rawParam || undefined;
        }

        // value
        const rawVal = row['value'] ?? row['Value'] ?? '';
        const numVal = Number(rawVal);
        if (rawVal === '' || rawVal == null) {
            errors.push({ field: 'value', message: 'Value is required.', severity: 'error' });
        } else if (isNaN(numVal)) {
            errors.push({ field: 'value', message: `"${rawVal}" is not a valid number.`, severity: 'error' });
        }

        // unit (optional — just warn if unexpected chars)
        const rawUnit = String(row['unit'] ?? row['Unit'] ?? '').trim();

        // date
        const rawDate = row['date'] ?? row['Date'] ?? '';
        const dateResult = normalizeDate(rawDate);
        if (!dateResult) {
            errors.push({ field: 'date', message: `"${rawDate}" is not a recognisable date. Use YYYY-MM-DD.`, severity: 'error' });
        } else if (dateResult.fixed) {
            errors.push({ field: 'date', message: `Date normalised from "${rawDate}" → "${dateResult.value}".`, severity: 'fixed' });
        }

        const normalized: NormalizedMeasurementRow = {
            station_code: rawCode,
            ...(paramCode !== undefined && { parameter_code: paramCode }),
            value: isNaN(numVal) ? 0 : numVal,
            unit: rawUnit,
            date: dateResult?.value ?? '',
        };

        const hasBlockingError = errors.some((e) => e.severity === 'error');

        return { index, normalized, errors, hasBlockingError };
    });
}
