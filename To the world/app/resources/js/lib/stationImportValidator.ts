/**
 * Client-side validation for bulk station imports.
 * Pure functions — no network calls. Server re-validates before insert.
 */

// ─── Allowed values ───────────────────────────────────────────────────────────
export const ALLOWED_COUNTRIES = ['Mozambique', 'South Africa', 'Eswatini'] as const;
export const ALLOWED_CATEGORIES = [
    'river_gauge',
    'dam',
    'borehole',
    'rainfall_station',
    'lake',
    'wetland',
    'other',
] as const;
export const ALLOWED_WATER_SOURCES = ['surface', 'ground', 'atmospheric'] as const;
export const ALLOWED_WATER_BODY_TYPES = ['river', 'dam', 'borehole', 'lake', 'wetland'] as const;

// Human-readable aliases → canonical value (covers common misspellings / display labels)
const CATEGORY_ALIASES: Record<string, string> = {
    'river gauge': 'river_gauge',
    rivergauge: 'river_gauge',
    river_gauge: 'river_gauge',
    dam: 'dam',
    borehole: 'borehole',
    'rainfall station': 'rainfall_station',
    rainfallstation: 'rainfall_station',
    rainfall_station: 'rainfall_station',
    rainfall: 'rainfall_station',
    lake: 'lake',
    wetland: 'wetland',
    other: 'other',
};
const WATER_SOURCE_ALIASES: Record<string, string> = {
    surface: 'surface',
    'surface water': 'surface',
    ground: 'ground',
    groundwater: 'ground',
    'ground water': 'ground',
    atmospheric: 'atmospheric',
    rain: 'atmospheric',
    rainfall: 'atmospheric',
};
const WATER_BODY_ALIASES: Record<string, string> = {
    river: 'river',
    dam: 'dam',
    reservoir: 'dam',
    borehole: 'borehole',
    lake: 'lake',
    wetland: 'wetland',
};
const COUNTRY_ALIASES: Record<string, string> = {
    mozambique: 'Mozambique',
    moz: 'Mozambique',
    'south africa': 'South Africa',
    'southafrica': 'South Africa',
    sa: 'South Africa',
    rsa: 'South Africa',
    eswatini: 'Eswatini',
    swaziland: 'Eswatini',
    esw: 'Eswatini',
};

// ─── Types ────────────────────────────────────────────────────────────────────
export interface NormalizedRow {
    code: string;
    name: string;
    country: string;
    category: string;
    water_source: string;
    water_body_type: string;
    latitude: number | null;
    longitude: number | null;
    river_basin: string;
    owner_org: string;
    telemetry_system: string;
    gauge_code: string;
    summary: string;
    is_active: boolean;
    is_real_time: boolean;
}

export type ErrorSeverity = 'error' | 'warning' | 'fixed';

export interface CellError {
    field: keyof NormalizedRow;
    severity: ErrorSeverity;
    message: string;
}

export interface ValidatedRow {
    index: number;          // 0-based index in the source array
    raw: Record<string, unknown>;
    normalized: NormalizedRow;
    errors: CellError[];
    hasBlockingError: boolean;
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function trim(v: unknown): string {
    return String(v ?? '').trim();
}

function parseBoolean(v: unknown, defaultValue: boolean): { value: boolean; fixed: boolean } {
    const s = trim(v).toLowerCase();
    if (['true', 'yes', '1', 'y', 'sim', 'ativo', 'activo'].includes(s)) return { value: true, fixed: false };
    if (['false', 'no', '0', 'n', 'não', 'nao', 'inativo'].includes(s)) return { value: false, fixed: false };
    if (s === '') return { value: defaultValue, fixed: true };
    return { value: defaultValue, fixed: true };
}

function resolveAlias(
    value: string,
    aliasMap: Record<string, string>,
    allowed: readonly string[],
): { resolved: string | null; wasFixed: boolean } {
    const key = value.toLowerCase().replace(/\s+/g, ' ').trim();
    if (aliasMap[key]) {
        const resolved = aliasMap[key];
        const wasFixed = resolved.toLowerCase() !== value.toLowerCase();
        return { resolved, wasFixed };
    }
    // Already exact match?
    const exact = allowed.find((a) => a.toLowerCase() === key);
    if (exact) return { resolved: exact, wasFixed: exact !== value };
    return { resolved: null, wasFixed: false };
}

function closestMatch(value: string, allowed: readonly string[]): string | null {
    // Simple distance: pick value that shares the most chars
    let best: string | null = null;
    let bestScore = -1;
    const v = value.toLowerCase();
    for (const a of allowed) {
        const aLower = a.toLowerCase();
        let score = 0;
        for (let i = 0; i < Math.min(v.length, aLower.length); i++) {
            if (v[i] === aLower[i]) score++;
        }
        if (score > bestScore) {
            bestScore = score;
            best = a;
        }
    }
    return best;
}

function isDMSCoord(s: string): boolean {
    // Looks like degrees-minutes-seconds: contains ° or ' or " or N/S/E/W suffix or multiple dots
    return /[°'"NSEW]/i.test(s) || (s.match(/\./g) || []).length > 1;
}

function parseCoordinateString(val: string): number | null {
    let cleanStr = val.trim().toUpperCase();
    if (!cleanStr) return null;

    // Check for dot-separated DMS e.g. 26.20.26.5 S or 26.20.26 S
    const dotCount = (cleanStr.match(/\./g) || []).length;
    if (dotCount > 1) {
        const dirMatch = cleanStr.match(/[NSEW]/);
        const dir = dirMatch ? dirMatch[0] : '';
        const numericPart = cleanStr.replace(/[NSEW]/g, '').trim();
        const parts = numericPart.split('.');
        if (parts.length === 3) {
            cleanStr = `${parts[0]} ${parts[1]} ${parts[2]} ${dir}`;
        } else if (parts.length === 4) {
            cleanStr = `${parts[0]} ${parts[1]} ${parts[2]}.${parts[3]} ${dir}`;
        }
    }

    // Find all numbers (integers or decimals)
    const matches = cleanStr.match(/([+-]?\d+(?:\.\d+)?)/g);
    if (!matches || matches.length === 0) return null;

    const dirMatch = cleanStr.match(/[NSEW]/);

    let d = 0;
    let m = 0;
    let s = 0;

    if (matches.length === 1) {
        d = Math.abs(parseFloat(matches[0]));
    } else if (matches.length === 2) {
        d = Math.abs(parseFloat(matches[0]));
        m = Math.abs(parseFloat(matches[1]));
    } else if (matches.length >= 3) {
        d = Math.abs(parseFloat(matches[0]));
        m = Math.abs(parseFloat(matches[1]));
        s = Math.abs(parseFloat(matches[2]));
    }

    const decimal = d + (m / 60) + (s / 3600);

    let isNegative = matches[0].startsWith('-');
    if (dirMatch) {
        if (dirMatch[0] === 'S' || dirMatch[0] === 'W') {
            isNegative = true;
        } else if (dirMatch[0] === 'N' || dirMatch[0] === 'E') {
            isNegative = false;
        }
    }

    return isNegative ? -decimal : decimal;
}


// ─── Column name normalisation ────────────────────────────────────────────────
const COLUMN_ALIASES: Record<string, keyof NormalizedRow> = {
    code: 'code',
    'station code': 'code',
    'station_code': 'code',
    name: 'name',
    'station name': 'name',
    'station_name': 'name',
    country: 'country',
    category: 'category',
    water_source: 'water_source',
    'water source': 'water_source',
    watersource: 'water_source',
    water_body_type: 'water_body_type',
    'water body type': 'water_body_type',
    waterbodytype: 'water_body_type',
    latitude: 'latitude',
    lat: 'latitude',
    longitude: 'longitude',
    lon: 'longitude',
    lng: 'longitude',
    long: 'longitude',
    river_basin: 'river_basin',
    'river basin': 'river_basin',
    riverbasin: 'river_basin',
    owner_org: 'owner_org',
    'owner org': 'owner_org',
    'owner organization': 'owner_org',
    ownerorg: 'owner_org',
    organisation: 'owner_org',
    organization: 'owner_org',
    telemetry_system: 'telemetry_system',
    'telemetry system': 'telemetry_system',
    telemetrysystem: 'telemetry_system',
    telemetry: 'telemetry_system',
    gauge_code: 'gauge_code',
    'gauge code': 'gauge_code',
    gaugecode: 'gauge_code',
    gauge: 'gauge_code',
    summary: 'summary',
    description: 'summary',
    is_active: 'is_active',
    'is active': 'is_active',
    active: 'is_active',
    isactive: 'is_active',
    is_real_time: 'is_real_time',
    'is real time': 'is_real_time',
    'real time': 'is_real_time',
    realtime: 'is_real_time',
    'real-time': 'is_real_time',
    isrealtime: 'is_real_time',
};

function normaliseHeader(raw: string): keyof NormalizedRow | null {
    const key = raw.toLowerCase().replace(/_/g, ' ').replace(/\s+/g, ' ').trim();
    return COLUMN_ALIASES[key] ?? null;
}

// ─── Main export ─────────────────────────────────────────────────────────────
/**
 * Parse raw rows from SheetJS (array of objects) into validated rows.
 * @param rawRows  - Array of plain objects from XLSX.utils.sheet_to_json
 * @param existingCodes - Set of codes already in DB (for duplicate detection)
 */
export function validateImportRows(
    rawRows: Record<string, unknown>[],
    existingCodes: Set<string> = new Set(),
): ValidatedRow[] {
    const batchCodes = new Map<string, number>(); // code → first occurrence index
    const results: ValidatedRow[] = [];

    for (let i = 0; i < rawRows.length; i++) {
        const raw = rawRows[i];

        // Skip entirely empty rows
        const allEmpty = Object.values(raw).every((v) => trim(v) === '');
        if (allEmpty) continue;

        // Map raw keys → normalized field names
        const mapped: Partial<Record<keyof NormalizedRow, unknown>> = {};
        for (const [rawKey, val] of Object.entries(raw)) {
            const field = normaliseHeader(rawKey);
            if (field && !(field in mapped)) {
                mapped[field] = val;
            }
        }

        const errors: CellError[] = [];

        // ── code ─────────────────────────────────────────────────────────────
        const code = trim(mapped.code);
        if (!code) {
            errors.push({ field: 'code', severity: 'error', message: 'Station code is required.' });
        } else if (code.length > 50) {
            errors.push({ field: 'code', severity: 'error', message: `Code too long (max 50 chars, got ${code.length}).` });
        } else if (existingCodes.has(code)) {
            errors.push({ field: 'code', severity: 'error', message: `Code "${code}" already exists in the database.` });
        } else if (batchCodes.has(code)) {
            errors.push({ field: 'code', severity: 'error', message: `Duplicate code "${code}" — also on row ${(batchCodes.get(code)! + 1)}.` });
        } else {
            batchCodes.set(code, i);
        }

        // ── name ─────────────────────────────────────────────────────────────
        const name = trim(mapped.name);
        if (!name) {
            errors.push({ field: 'name', severity: 'error', message: 'Station name is required.' });
        } else if (name.length > 255) {
            errors.push({ field: 'name', severity: 'error', message: `Name too long (max 255 chars).` });
        }

        // ── country ──────────────────────────────────────────────────────────
        let country = trim(mapped.country);
        if (country) {
            const { resolved, wasFixed } = resolveAlias(country, COUNTRY_ALIASES, ALLOWED_COUNTRIES);
            if (!resolved) {
                const suggestion = closestMatch(country, ALLOWED_COUNTRIES);
                errors.push({
                    field: 'country',
                    severity: 'error',
                    message: `"${country}" is not a valid country. Allowed: ${ALLOWED_COUNTRIES.join(', ')}${suggestion ? `. Did you mean "${suggestion}"?` : ''}`,
                });
            } else {
                if (wasFixed) {
                    errors.push({ field: 'country', severity: 'fixed', message: `Auto-corrected to "${resolved}".` });
                }
                country = resolved;
            }
        }

        // ── category ─────────────────────────────────────────────────────────
        let category = trim(mapped.category);
        if (!category) {
            errors.push({ field: 'category', severity: 'error', message: 'Category is required.' });
        } else {
            const { resolved, wasFixed } = resolveAlias(category, CATEGORY_ALIASES, ALLOWED_CATEGORIES);
            if (!resolved) {
                const suggestion = closestMatch(category, ALLOWED_CATEGORIES);
                errors.push({
                    field: 'category',
                    severity: 'error',
                    message: `"${category}" is not a valid category. Allowed: ${ALLOWED_CATEGORIES.join(', ')}${suggestion ? `. Did you mean "${suggestion}"?` : ''}`,
                });
            } else {
                if (wasFixed) {
                    errors.push({ field: 'category', severity: 'fixed', message: `Auto-corrected to "${resolved}".` });
                }
                category = resolved;
            }
        }

        // ── water_source ──────────────────────────────────────────────────────
        let water_source = trim(mapped.water_source);
        if (!water_source) {
            errors.push({ field: 'water_source', severity: 'error', message: 'Water source is required.' });
        } else {
            const { resolved, wasFixed } = resolveAlias(water_source, WATER_SOURCE_ALIASES, ALLOWED_WATER_SOURCES);
            if (!resolved) {
                const suggestion = closestMatch(water_source, ALLOWED_WATER_SOURCES);
                errors.push({
                    field: 'water_source',
                    severity: 'error',
                    message: `"${water_source}" is not valid. Allowed: ${ALLOWED_WATER_SOURCES.join(', ')}${suggestion ? `. Did you mean "${suggestion}"?` : ''}`,
                });
            } else {
                if (wasFixed) {
                    errors.push({ field: 'water_source', severity: 'fixed', message: `Auto-corrected to "${resolved}".` });
                }
                water_source = resolved;
            }
        }

        // ── water_body_type ───────────────────────────────────────────────────
        let water_body_type = trim(mapped.water_body_type);
        if (!water_body_type) {
            errors.push({ field: 'water_body_type', severity: 'error', message: 'Water body type is required.' });
        } else {
            const { resolved, wasFixed } = resolveAlias(water_body_type, WATER_BODY_ALIASES, ALLOWED_WATER_BODY_TYPES);
            if (!resolved) {
                const suggestion = closestMatch(water_body_type, ALLOWED_WATER_BODY_TYPES);
                errors.push({
                    field: 'water_body_type',
                    severity: 'error',
                    message: `"${water_body_type}" is not valid. Allowed: ${ALLOWED_WATER_BODY_TYPES.join(', ')}${suggestion ? `. Did you mean "${suggestion}"?` : ''}`,
                });
            } else {
                if (wasFixed) {
                    errors.push({ field: 'water_body_type', severity: 'fixed', message: `Auto-corrected to "${resolved}".` });
                }
                water_body_type = resolved;
            }
        }

        // ── latitude & longitude parsing and normalisation ───────────────────────
        const rawLat = trim(mapped.latitude);
        const rawLng = trim(mapped.longitude);

        let latitude: number | null = null;
        let longitude: number | null = null;

        let hasLatError = false;
        let hasLngError = false;

        if (!rawLat) {
            errors.push({ field: 'latitude', severity: 'error', message: 'Latitude is required.' });
            hasLatError = true;
        }
        if (!rawLng) {
            errors.push({ field: 'longitude', severity: 'error', message: 'Longitude is required.' });
            hasLngError = true;
        }

        if (!hasLatError && !hasLngError) {
            let parsedLat = parseCoordinateString(rawLat);
            let parsedLng = parseCoordinateString(rawLng);

            if (parsedLat === null) {
                errors.push({ field: 'latitude', severity: 'error', message: `"${rawLat}" is not a valid coordinate format.` });
                hasLatError = true;
            }
            if (parsedLng === null) {
                errors.push({ field: 'longitude', severity: 'error', message: `"${rawLng}" is not a valid coordinate format.` });
                hasLngError = true;
            }

            if (parsedLat !== null && parsedLng !== null) {
                // Swap coordinates check (if lat is positive and lng is negative, e.g. SA bounds)
                if (parsedLat > 10 && parsedLng < -10) {
                    const temp = parsedLat;
                    parsedLat = parsedLng;
                    parsedLng = temp;
                    errors.push({
                        field: 'latitude',
                        severity: 'fixed',
                        message: 'Swapped Latitude and Longitude values auto-corrected based on Southern Africa region.',
                    });
                    errors.push({
                        field: 'longitude',
                        severity: 'fixed',
                        message: 'Swapped Latitude and Longitude values auto-corrected based on Southern Africa region.',
                    });
                }

                // Southern Africa negative latitude correction
                if (parsedLat > 0) {
                    parsedLat = -parsedLat;
                    errors.push({
                        field: 'latitude',
                        severity: 'fixed',
                        message: 'Latitude positive value auto-corrected to negative for Southern Africa region.',
                    });
                }

                // Longitude positive correction (omit accidental negative sign)
                if (parsedLng < 0) {
                    parsedLng = Math.abs(parsedLng);
                    errors.push({
                        field: 'longitude',
                        severity: 'fixed',
                        message: 'Longitude negative value auto-corrected to positive for Southern Africa region.',
                    });
                }

                // Earth limit checks
                if (parsedLat < -90 || parsedLat > 90) {
                    errors.push({ field: 'latitude', severity: 'error', message: `Latitude ${parsedLat} out of range (−90 to 90).` });
                    hasLatError = true;
                }
                if (parsedLng < -180 || parsedLng > 180) {
                    errors.push({ field: 'longitude', severity: 'error', message: `Longitude ${parsedLng} out of range (−180 to 180).` });
                    hasLngError = true;
                }

                // Incomati / Southern Africa bounding box warning
                // Lat: -35.5 to -9.5, Lng: 16.0 to 41.5
                if (!hasLatError && !hasLngError) {
                    if (parsedLat < -35.5 || parsedLat > -9.5 || parsedLng < 16.0 || parsedLng > 41.5) {
                        errors.push({
                            field: 'latitude',
                            severity: 'warning',
                            message: 'Coordinates are outside the expected Southern Africa region (Mozambique, South Africa, Eswatini).',
                        });
                        errors.push({
                            field: 'longitude',
                            severity: 'warning',
                            message: 'Coordinates are outside the expected Southern Africa region (Mozambique, South Africa, Eswatini).',
                        });
                    }

                    latitude = parsedLat;
                    longitude = parsedLng;

                    // Report DMS conversions using corrected final values
                    if (isDMSCoord(rawLat)) {
                        errors.push({
                            field: 'latitude',
                            severity: 'fixed',
                            message: `DMS coordinate "${rawLat}" auto-converted to decimal degrees: ${parsedLat.toFixed(6)}`,
                        });
                    }
                    if (isDMSCoord(rawLng)) {
                        errors.push({
                            field: 'longitude',
                            severity: 'fixed',
                            message: `DMS coordinate "${rawLng}" auto-converted to decimal degrees: ${parsedLng.toFixed(6)}`,
                        });
                    }

                    // Warn if too many decimal places
                    if (!isDMSCoord(rawLat) && rawLat.includes('.')) {
                        const dpMatch = rawLat.split('.')[1];
                        if (dpMatch && dpMatch.length > 6) {
                            errors.push({
                                field: 'latitude',
                                severity: 'warning',
                                message: `More than 6 decimal places — stored at full precision but displayed as ${parsedLat.toFixed(6)}.`,
                            });
                        }
                    }
                    if (!isDMSCoord(rawLng) && rawLng.includes('.')) {
                        const dpMatch = rawLng.split('.')[1];
                        if (dpMatch && dpMatch.length > 6) {
                            errors.push({
                                field: 'longitude',
                                severity: 'warning',
                                message: `More than 6 decimal places — stored at full precision but displayed as ${parsedLng.toFixed(6)}.`,
                            });
                        }
                    }
                }
            }
        }


        // ── optional string fields ────────────────────────────────────────────
        const river_basin = trim(mapped.river_basin);
        if (river_basin.length > 255) errors.push({ field: 'river_basin', severity: 'error', message: 'River basin exceeds 255 chars.' });

        const owner_org = trim(mapped.owner_org);
        if (owner_org.length > 255) errors.push({ field: 'owner_org', severity: 'error', message: 'Owner org exceeds 255 chars.' });

        const telemetry_system = trim(mapped.telemetry_system);
        if (telemetry_system.length > 255) errors.push({ field: 'telemetry_system', severity: 'error', message: 'Telemetry system exceeds 255 chars.' });

        const gauge_code = trim(mapped.gauge_code);
        if (gauge_code.length > 100) errors.push({ field: 'gauge_code', severity: 'error', message: 'Gauge code exceeds 100 chars.' });

        const summary = trim(mapped.summary);
        if (summary.length > 2000) errors.push({ field: 'summary', severity: 'error', message: 'Summary exceeds 2000 chars.' });

        // ── booleans ──────────────────────────────────────────────────────────
        const isActiveParsed = parseBoolean(mapped.is_active, true);
        const isRealTimeParsed = parseBoolean(mapped.is_real_time, false);

        if (isActiveParsed.fixed && trim(mapped.is_active) !== '') {
            errors.push({ field: 'is_active', severity: 'warning', message: `"${trim(mapped.is_active)}" not recognised — defaulted to ${isActiveParsed.value}.` });
        }
        if (isRealTimeParsed.fixed && trim(mapped.is_real_time) !== '') {
            errors.push({ field: 'is_real_time', severity: 'warning', message: `"${trim(mapped.is_real_time)}" not recognised — defaulted to ${isRealTimeParsed.value}.` });
        }

        const normalized: NormalizedRow = {
            code,
            name,
            country,
            category,
            water_source,
            water_body_type,
            latitude,
            longitude,
            river_basin,
            owner_org,
            telemetry_system,
            gauge_code,
            summary,
            is_active: isActiveParsed.value,
            is_real_time: isRealTimeParsed.value,
        };

        const hasBlockingError = errors.some((e) => e.severity === 'error');

        results.push({ index: i, raw, normalized, errors, hasBlockingError });
    }

    return results;
}
