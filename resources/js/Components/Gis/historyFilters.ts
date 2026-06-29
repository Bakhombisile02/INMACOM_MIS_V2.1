export type HistorySortOption = 'date_desc' | 'date_asc' | 'value_desc' | 'value_asc';
export type HistoryRangeOption = 'all' | '30d' | '90d' | '180d' | '365d' | 'custom';

interface DateBounds {
    from: Date | null;
    to: Date | null;
}

const RANGE_TO_DAYS: Record<Exclude<HistoryRangeOption, 'all' | 'custom'>, number> = {
    '30d': 30,
    '90d': 90,
    '180d': 180,
    '365d': 365,
};

const parseDate = (value: string | null | undefined): Date | null => {
    if (!value) return null;
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
};

const startOfDay = (value: Date): Date => {
    const result = new Date(value);
    result.setHours(0, 0, 0, 0);
    return result;
};

const endOfDay = (value: Date): Date => {
    const result = new Date(value);
    result.setHours(23, 59, 59, 999);
    return result;
};

export const getHistoryDateBounds = (
    range: HistoryRangeOption,
    customFrom: string,
    customTo: string,
): DateBounds => {
    if (range === 'all') {
        return { from: null, to: null };
    }

    if (range === 'custom') {
        const parsedFrom = parseDate(customFrom);
        const parsedTo = parseDate(customTo);
        return {
            from: parsedFrom ? startOfDay(parsedFrom) : null,
            to: parsedTo ? endOfDay(parsedTo) : null,
        };
    }

    const days = RANGE_TO_DAYS[range];
    const to = endOfDay(new Date());
    const from = startOfDay(new Date());
    from.setDate(from.getDate() - days + 1);

    return { from, to };
};

export const isDateWithinBounds = (
    dateValue: string | null | undefined,
    bounds: DateBounds,
): boolean => {
    const parsed = parseDate(dateValue ?? undefined);
    if (!parsed) {
        return false;
    }

    if (bounds.from && parsed < bounds.from) {
        return false;
    }

    if (bounds.to && parsed > bounds.to) {
        return false;
    }

    return true;
};

export const filterAndSortHistoryRows = <T extends { date: string; value: number | string }>(
    rows: T[],
    options: {
        search: string;
        sort: HistorySortOption;
        bounds: DateBounds;
        searchText: (row: T) => string;
    },
): T[] => {
    const needle = options.search.trim().toLowerCase();

    const filtered = rows.filter((row) => {
        if (!isDateWithinBounds(row.date, options.bounds)) {
            return false;
        }

        if (!needle) {
            return true;
        }

        return options.searchText(row).toLowerCase().includes(needle);
    });

    return filtered.sort((a, b) => {
        if (options.sort === 'date_desc') {
            return new Date(b.date).getTime() - new Date(a.date).getTime();
        }

        if (options.sort === 'date_asc') {
            return new Date(a.date).getTime() - new Date(b.date).getTime();
        }

        const aValue = Number(a.value);
        const bValue = Number(b.value);

        if (Number.isNaN(aValue) && Number.isNaN(bValue)) return 0;
        if (Number.isNaN(aValue)) return 1;
        if (Number.isNaN(bValue)) return -1;

        if (options.sort === 'value_desc') {
            return bValue - aValue;
        }

        return aValue - bValue;
    });
};

export const getHistorySortOptions = (t: (key: string) => string) => [
    { value: 'date_desc', label: t('common.history.sort.dateDesc') },
    { value: 'date_asc', label: t('common.history.sort.dateAsc') },
    { value: 'value_desc', label: t('common.history.sort.valueDesc') },
    { value: 'value_asc', label: t('common.history.sort.valueAsc') },
];

export const getHistoryRangeOptions = (t: (key: string) => string) => [
    { value: 'all', label: t('common.history.range.all') },
    { value: '30d', label: t('common.history.range.last30') },
    { value: '90d', label: t('common.history.range.last90') },
    { value: '180d', label: t('common.history.range.last180') },
    { value: '365d', label: t('common.history.range.last365') },
    { value: 'custom', label: t('common.history.range.custom') },
];

