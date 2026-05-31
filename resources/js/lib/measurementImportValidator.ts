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

const PT_TO_EN_MONTHS: Record<string, string> = {
    janeiro: 'january', jan: 'jan',
    fevereiro: 'february', fev: 'feb',
    março: 'march', mar: 'mar',
    abril: 'april', abr: 'apr',
    maio: 'may', mai: 'may',
    junho: 'june', jun: 'jun',
    julho: 'july', jul: 'jul',
    agosto: 'august', ago: 'aug',
    setembro: 'september', set: 'sep',
    outubro: 'october', out: 'oct',
    novembro: 'november', nov: 'nov',
    dezembro: 'december', dez: 'dec'
};

function formatDateLocal(d: Date): string {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

/** Attempts to parse a date in common formats and normalise to YYYY-MM-DD. */
function normalizeDate(raw: unknown): { value: string; fixed: boolean; ambiguous?: boolean } | null {
    if (raw == null || raw === '') return null;
    let s = String(raw).trim();

    // Already correct
    if (DATE_RE.test(s)) return { value: s, fixed: false };

    // Try Excel serial number (numeric) — bound to plausible Excel serial date ranges (e.g. 10000 to 60000)
    const num = Number(s);
    if (!isNaN(num) && num > 10000 && num < 60000) {
        // Excel date serial: days since 1899-12-30.
        // Excel serial parses to UTC to avoid local timezone variance.
        const utcDate = new Date((num - 25569) * 86400 * 1000);
        const y = utcDate.getUTCFullYear();
        const m = String(utcDate.getUTCMonth() + 1).padStart(2, '0');
        const day = String(utcDate.getUTCDate()).padStart(2, '0');
        return { value: `${y}-${m}-${day}`, fixed: true };
    }

    // Standardise separators and translate Portuguese
    let cleanStr = s.toLowerCase();
    for (const [pt, en] of Object.entries(PT_TO_EN_MONTHS)) {
        const re = new RegExp(`\\b${pt}\\b`, 'g');
        cleanStr = cleanStr.replace(re, en);
    }
    // Remove "de" prepositions commonly used in PT dates e.g. "29 de maio de 2026"
    cleanStr = cleanStr.replace(/\bde\b/g, ' ');
    // Remove extra spaces
    cleanStr = cleanStr.replace(/\s+/g, ' ').trim();

    // Support compact 8-digit numeric dates e.g. "20231225"
    if (/^\d{8}$/.test(cleanStr)) {
        const y = cleanStr.substring(0, 4);
        const m = cleanStr.substring(4, 6);
        const d = cleanStr.substring(6, 8);
        const monthNum = parseInt(m);
        const dayNum = parseInt(d);
        if (monthNum >= 1 && monthNum <= 12 && dayNum >= 1 && dayNum <= 31) {
            const ambiguous = monthNum <= 12 && dayNum <= 12 && monthNum !== dayNum;
            return { value: `${y}-${m}-${d}`, fixed: true, ambiguous };
        }
    }

    // Try parsing dd/mm/yyyy or dd-mm-yyyy or yyyy/mm/dd or yyyy-mm-dd
    const numMatches = cleanStr.match(/\d+/g);
    if (numMatches && numMatches.length === 3) {
        let first = parseInt(numMatches[0]);
        let second = parseInt(numMatches[1]);
        let third = parseInt(numMatches[2]);

        let y = 0;
        let m = 0;
        let d = 0;
        let ambiguous = false;

        if (first > 31) {
            // YYYY-MM-DD
            y = first;
            m = second;
            d = third;
            if (m <= 12 && d <= 12 && m !== d) {
                ambiguous = true;
            }
        } else if (third > 31) {
            // DD-MM-YYYY
            d = first;
            m = second;
            y = third;
            if (m <= 12 && d <= 12 && m !== d) {
                ambiguous = true;
            }
        } else {
            // 2-digit year e.g. DD-MM-YY
            if (first <= 31 && second <= 12 && third <= 99) {
                d = first;
                m = second;
                y = third + (third > 50 ? 1900 : 2000);
                if (m <= 12 && d <= 12 && m !== d) {
                    ambiguous = true;
                }
            }
        }

        if (y > 0 && m >= 1 && m <= 12 && d >= 1 && d <= 31) {
            const iso = `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            return { value: iso, fixed: true, ambiguous };
        }
    }

    // Let JS parse anything else timezone-safe (e.g. "29 May 2026", "29-May-26", "29-Dez-26")
    const parsed = new Date(cleanStr);
    if (!isNaN(parsed.getTime())) {
        // Find if it was ambiguous (if numeric elements were both <= 12 and no letters in original input)
        let ambiguous = false;
        const rawDigits = s.match(/\d+/g);
        if (rawDigits && rawDigits.length >= 2 && !/[a-zA-Z]/.test(s)) {
            const first = parseInt(rawDigits[0]);
            const second = parseInt(rawDigits[1]);
            if (first <= 12 && second <= 12 && first !== second) {
                ambiguous = true;
            }
        }
        return { value: formatDateLocal(parsed), fixed: true, ambiguous };
    }

    return null;
}

function parseMeasurementValue(raw: unknown): number | null {
    if (raw == null || raw === '') return null;
    if (typeof raw === 'number') return raw;
    let s = String(raw).trim();

    // Normalize thousands and decimal separators when both are present
    const lastComma = s.lastIndexOf(',');
    const lastDot = s.lastIndexOf('.');
    if (lastComma !== -1 && lastDot !== -1) {
        if (lastComma > lastDot) {
            // Comma is decimal separator, dot is thousands separator (e.g. 1.234,56)
            s = s.replace(/\./g, '').replace(',', '.');
        } else {
            // Dot is decimal separator, comma is thousands separator (e.g. 1,234.56)
            s = s.replace(/,/g, '');
        }
    } else if (s.includes(',') && !s.includes('.')) {
        // e.g. "12,5" -> "12.5"
        s = s.replace(',', '.');
    }

    // Extract the leading decimal number pattern supporting bare-decimal (e.g. .5, -,5)
    const match = s.match(/^\s*([+-]?(?:\d+(?:\.\d+)?|\.\d+))/);
    if (!match) return null;

    const num = Number(match[1]);
    return isNaN(num) ? null : num;
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
        const numVal = parseMeasurementValue(rawVal);
        if (rawVal === '' || rawVal == null) {
            errors.push({ field: 'value', message: 'Value is required.', severity: 'error' });
        } else if (numVal === null) {
            errors.push({ field: 'value', message: `"${rawVal}" is not a valid number.`, severity: 'error' });
        } else {
            const rawValStr = String(rawVal).trim();
            if (rawValStr !== String(numVal) && rawValStr !== String(Number(rawValStr))) {
                errors.push({ field: 'value', message: `Value auto-corrected from "${rawValStr}" to ${numVal}.`, severity: 'fixed' });
            }
        }

        // unit (optional — just warn if unexpected chars)
        const rawUnit = String(row['unit'] ?? row['Unit'] ?? '').trim();

        // date
        const rawDate = row['date'] ?? row['Date'] ?? '';
        const dateResult = normalizeDate(rawDate);
        if (!dateResult) {
            errors.push({ field: 'date', message: `"${rawDate}" is not a recognisable date. Use YYYY-MM-DD.`, severity: 'error' });
        } else {
            if (dateResult.fixed) {
                errors.push({ field: 'date', message: `Date normalised from "${rawDate}" → "${dateResult.value}".`, severity: 'fixed' });
            }
            if (dateResult.ambiguous) {
                errors.push({ field: 'date', message: `Ambiguous date "${rawDate}" — assumed DD-MM-YYYY format by Southern African convention.`, severity: 'warning' });
            }
        }

        const normalized: NormalizedMeasurementRow = {
            station_code: rawCode,
            ...(paramCode !== undefined && { parameter_code: paramCode }),
            value: numVal ?? 0,
            unit: rawUnit,
            date: dateResult?.value ?? '',
        };

        const hasBlockingError = errors.some((e) => e.severity === 'error');

        return { index, normalized, errors, hasBlockingError };
    });
}
