/**
 * StationCharts — interactive historical data charts for a single station.
 *
 * Fetches data from GET /stations/{id}/historical-data with optional from/to
 * date range query params. Aggregates daily / monthly averages client-side so
 * no extra backend endpoints are needed.
 *
 * Charts use Recharts: responsive, TypeScript-native, includes <Brush> for
 * drag-to-select range directly on the time-series chart.
 */

import {
    Button,
    Card,
    Group,
    Loader,
    Skeleton,
    Stack,
    Tabs,
    Text,
    TextInput,
    Title,
} from '@mantine/core';
import {
    Area,
    AreaChart,
    Bar,
    BarChart,
    Brush,
    CartesianGrid,
    Legend,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import html2canvas from 'html2canvas';
import { forwardRef, useEffect, useImperativeHandle, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

// ─── Types ────────────────────────────────────────────────────────────────────

interface Reading {
    id: string;
    measurement_type: string;
    value: number;
    unit: string;
    date: string;
    parameter_code: string | null;
}

interface HistoricalApiResponse {
    station_code: string;
    station_name: string;
    readings: Reading[];
    fdc: { percentile: number; value: number }[];
    wq_matrix: {
        parameter: string;
        total_samples: number;
        exceedances: number;
        min_value: number;
        max_value: number;
    }[];
}

interface Capability {
    measurement_type: string;
    is_primary: boolean;
}

export interface ChartImage {
    title: string;
    dataUrl: string;
}

export interface StationChartsHandle {
    /** Renders all charts off-screen and returns them as PNG data URLs. */
    captureCharts(): Promise<ChartImage[]>;
}

interface Props {
    stationId: string;
    capabilities: Capability[];
}

// ─── Colour palette for multiple series ───────────────────────────────────────

const SERIES_COLORS = [
    '#1971c2', '#2f9e44', '#e67700', '#c92a2a', '#6741d9',
    '#0c8599', '#9c36b5', '#364fc7', '#087f5b', '#a61e4d',
];

// ─── Data helpers ─────────────────────────────────────────────────────────────

function groupByDay(readings: Reading[]): { date: string; [key: string]: number | string }[] {
    const map = new Map<string, { sum: Record<string, number>; count: Record<string, number> }>();

    for (const r of readings) {
        const key = r.date.slice(0, 10);
        const seriesKey = r.parameter_code ? `${r.measurement_type}/${r.parameter_code}` : r.measurement_type;
        if (!map.has(key)) map.set(key, { sum: {}, count: {} });
        const entry = map.get(key)!;
        entry.sum[seriesKey] = (entry.sum[seriesKey] ?? 0) + Number(r.value);
        entry.count[seriesKey] = (entry.count[seriesKey] ?? 0) + 1;
    }

    return Array.from(map.entries())
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([date, { sum, count }]) => {
            const row: { date: string; [key: string]: number | string } = { date };
            for (const k of Object.keys(sum)) {
                row[k] = parseFloat((sum[k] / count[k]).toFixed(4));
            }
            return row;
        });
}

function groupByMonth(readings: Reading[]): { month: string; [key: string]: number | string }[] {
    const map = new Map<string, { sum: Record<string, number>; count: Record<string, number> }>();

    for (const r of readings) {
        const key = r.date.slice(0, 7); // YYYY-MM
        const seriesKey = r.parameter_code ? `${r.measurement_type}/${r.parameter_code}` : r.measurement_type;
        if (!map.has(key)) map.set(key, { sum: {}, count: {} });
        const entry = map.get(key)!;
        entry.sum[seriesKey] = (entry.sum[seriesKey] ?? 0) + Number(r.value);
        entry.count[seriesKey] = (entry.count[seriesKey] ?? 0) + 1;
    }

    return Array.from(map.entries())
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([month, { sum, count }]) => {
            const row: { month: string; [key: string]: number | string } = { month };
            for (const k of Object.keys(sum)) {
                row[k] = parseFloat((sum[k] / count[k]).toFixed(4));
            }
            return row;
        });
}

function groupMonthlySum(readings: Reading[]): { month: string; [key: string]: number | string }[] {
    const map = new Map<string, Record<string, number>>();
    for (const r of readings) {
        const key = r.date.slice(0, 7);
        const seriesKey = r.parameter_code ? `${r.measurement_type}/${r.parameter_code}` : r.measurement_type;
        if (!map.has(key)) map.set(key, {});
        const entry = map.get(key)!;
        entry[seriesKey] = (entry[seriesKey] ?? 0) + Number(r.value);
    }
    return Array.from(map.entries())
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([month, sums]) => ({ month, ...sums }));
}

function getSeriesKeys(data: Record<string, unknown>[], skip = ['date', 'month']): string[] {
    const keys = new Set<string>();
    for (const row of data) {
        for (const k of Object.keys(row)) {
            if (!skip.includes(k)) keys.add(k);
        }
    }
    return Array.from(keys);
}

const FLOW_TYPES = new Set(['flow', 'dam_level']);

function stationHasFlow(capabilities: Capability[]): boolean {
    return capabilities.some((c) => FLOW_TYPES.has(c.measurement_type));
}

// ─── Shared tooltip formatter ─────────────────────────────────────────────────

const tooltipStyle = {
    fontSize: 12,
    borderRadius: 6,
    boxShadow: '0 1px 8px rgba(0,0,0,0.12)',
};

// ─── Hidden chart dimensions for off-screen capture ─────────────────────────
const CAPTURE_W = 860;
const CAPTURE_H = 340;

// ─── Main component ───────────────────────────────────────────────────────────

const StationCharts = forwardRef<StationChartsHandle, Props>(function StationCharts(
    { stationId, capabilities },
    ref,
) {
    const { t } = useTranslation('stations');
    const [loading, setLoading] = useState(true);
    const [data, setData] = useState<HistoricalApiResponse | null>(null);
    const [from, setFrom] = useState('');
    const [to, setTo] = useState('');
    const [pendingFrom, setPendingFrom] = useState('');
    const [pendingTo, setPendingTo] = useState('');

    const showFlowTabs = stationHasFlow(capabilities);

    // Refs for the hidden off-screen chart containers used by captureCharts()
    const hiddenTimeSeriesRef = useRef<HTMLDivElement>(null);
    const hiddenDailyAvgRef   = useRef<HTMLDivElement>(null);
    const hiddenMonthlyAvgRef = useRef<HTMLDivElement>(null);
    const hiddenDailyFlowRef  = useRef<HTMLDivElement>(null);
    const hiddenMonthlyVolRef = useRef<HTMLDivElement>(null);

    useImperativeHandle(ref, () => ({
        async captureCharts() {
            const opts = {
                scale: 2,
                useCORS: true,
                logging: false,
                backgroundColor: '#ffffff',
            } as const;

            const results: ChartImage[] = [];

            const entries: [React.RefObject<HTMLDivElement>, string][] = [
                [hiddenTimeSeriesRef,  'Time Series'],
                [hiddenDailyAvgRef,   'Daily Average'],
                [hiddenMonthlyAvgRef, 'Monthly Average'],
                ...(showFlowTabs
                    ? [
                        [hiddenDailyFlowRef,  'Daily Flow (m³/s)'] as [React.RefObject<HTMLDivElement>, string],
                        [hiddenMonthlyVolRef, 'Monthly Volume']   as [React.RefObject<HTMLDivElement>, string],
                      ]
                    : []),
            ];

            for (const [divRef, title] of entries) {
                if (!divRef.current) continue;
                // Skip charts with no data (empty SVG / 0 children)
                const svg = divRef.current.querySelector('svg');
                if (!svg) continue;
                const canvas = await html2canvas(divRef.current, opts);
                results.push({ title, dataUrl: canvas.toDataURL('image/png') });
            }

            return results;
        },
    }), [showFlowTabs]);

    async function fetchData(fromDate: string, toDate: string) {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            if (fromDate) params.set('from', fromDate);
            if (toDate) params.set('to', toDate);
            const url = `/stations/${stationId}/historical-data${params.toString() ? '?' + params.toString() : ''}`;
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('Failed to fetch');
            const json: HistoricalApiResponse = await res.json();
            setData(json);
        } catch {
            setData(null);
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => {
        fetchData('', '');
    }, [stationId]);

    function handleApply() {
        setFrom(pendingFrom);
        setTo(pendingTo);
        fetchData(pendingFrom, pendingTo);
    }

    function handleReset() {
        setPendingFrom('');
        setPendingTo('');
        setFrom('');
        setTo('');
        fetchData('', '');
    }

    const readings = data?.readings ?? [];

    // ── Derived data ─────────────────────────────────────────────────────────

    // Time series: group by day, keep all types as separate series
    const timeSeriesData = groupByDay(readings);
    const timeSeriesKeys = getSeriesKeys(timeSeriesData, ['date']);

    // Daily average
    const dailyAvgData = groupByDay(readings);
    const dailyAvgKeys = getSeriesKeys(dailyAvgData, ['date']);

    // Monthly average
    const monthlyAvgData = groupByMonth(readings);
    const monthlyAvgKeys = getSeriesKeys(monthlyAvgData, ['month']);

    // Daily flow (flow + dam_level only)
    const flowReadings = readings.filter((r) => FLOW_TYPES.has(r.measurement_type));
    const dailyFlowData = groupByDay(flowReadings);
    const dailyFlowKeys = getSeriesKeys(dailyFlowData, ['date']);

    // Monthly volume (sum, not average)
    const monthlyVolData = groupMonthlySum(flowReadings);
    const monthlyVolKeys = getSeriesKeys(monthlyVolData, ['month']);

    // ── Empty state ──────────────────────────────────────────────────────────

    if (!loading && readings.length === 0) {
        return (
            <Card withBorder radius="md" p="md" data-report-exclude="true">
                <Stack gap="sm">
                    <Title order={4}>{t('charts.title')}</Title>
                    <DateFilterBar
                        pendingFrom={pendingFrom}
                        pendingTo={pendingTo}
                        onFromChange={setPendingFrom}
                        onToChange={setPendingTo}
                        onApply={handleApply}
                        onReset={handleReset}
                        t={t}
                    />
                    <Text c="dimmed" size="sm" py="md" ta="center">
                        {t('charts.noData')}
                    </Text>
                </Stack>
            </Card>
        );
    }

    return (
        <>
        {/* ── Hidden off-screen charts for PDF capture ── */}
        <div
            aria-hidden="true"
            style={{
                position: 'fixed',
                left: '-9999px',
                top: 0,
                width: `${CAPTURE_W}px`,
                background: '#ffffff',
                pointerEvents: 'none',
            }}
        >
            {/* Time Series */}
            <div ref={hiddenTimeSeriesRef} style={{ width: CAPTURE_W, height: CAPTURE_H, padding: '16px 8px 8px' }}>
                <ResponsiveContainer width={CAPTURE_W - 16} height={CAPTURE_H - 24}>
                    <LineChart data={timeSeriesData} margin={{ top: 8, right: 24, left: 8, bottom: 24 }}>
                        <CartesianGrid strokeDasharray="3 3" stroke="rgba(0,0,0,0.06)" />
                        <XAxis dataKey="date" tick={{ fontSize: 11 }} tickFormatter={shortDate} minTickGap={40}
                            label={{ value: 'Date', position: 'insideBottom', offset: -8, style: { fontSize: 11, fill: '#495057' } }} />
                        <YAxis tick={{ fontSize: 11 }} width={60}
                            label={{ value: 'Value', angle: -90, position: 'insideLeft', style: { fontSize: 11, fill: '#495057' } }} />
                        <Tooltip contentStyle={tooltipStyle} formatter={fmtVal} />
                        <Legend wrapperStyle={{ fontSize: 12 }} />
                        {timeSeriesKeys.map((k, i) => (
                            <Line key={k} type="monotone" dataKey={k}
                                stroke={SERIES_COLORS[i % SERIES_COLORS.length]}
                                dot={false} strokeWidth={2} isAnimationActive={false} />
                        ))}
                    </LineChart>
                </ResponsiveContainer>
            </div>
            {/* Daily Average */}
            <div ref={hiddenDailyAvgRef} style={{ width: CAPTURE_W, height: CAPTURE_H, padding: '16px 8px 8px' }}>
                <ResponsiveContainer width={CAPTURE_W - 16} height={CAPTURE_H - 24}>
                    <BarChart data={dailyAvgData} margin={{ top: 8, right: 24, left: 8, bottom: 24 }}>
                        <CartesianGrid strokeDasharray="3 3" stroke="rgba(0,0,0,0.06)" />
                        <XAxis dataKey="date" tick={{ fontSize: 11 }} tickFormatter={shortDate} minTickGap={40}
                            label={{ value: 'Date', position: 'insideBottom', offset: -8, style: { fontSize: 11, fill: '#495057' } }} />
                        <YAxis tick={{ fontSize: 11 }} width={60}
                            label={{ value: 'Daily average', angle: -90, position: 'insideLeft', style: { fontSize: 11, fill: '#495057' } }} />
                        <Tooltip contentStyle={tooltipStyle} formatter={fmtVal} />
                        <Legend wrapperStyle={{ fontSize: 12 }} />
                        {dailyAvgKeys.map((k, i) => (
                            <Bar key={k} dataKey={k} fill={SERIES_COLORS[i % SERIES_COLORS.length]}
                                radius={[2, 2, 0, 0]} isAnimationActive={false} maxBarSize={20} />
                        ))}
                    </BarChart>
                </ResponsiveContainer>
            </div>
            {/* Monthly Average */}
            <div ref={hiddenMonthlyAvgRef} style={{ width: CAPTURE_W, height: CAPTURE_H, padding: '16px 8px 8px' }}>
                <ResponsiveContainer width={CAPTURE_W - 16} height={CAPTURE_H - 24}>
                    <BarChart data={monthlyAvgData} margin={{ top: 8, right: 24, left: 8, bottom: 24 }}>
                        <CartesianGrid strokeDasharray="3 3" stroke="rgba(0,0,0,0.06)" />
                        <XAxis dataKey="month" tick={{ fontSize: 11 }}
                            label={{ value: 'Month', position: 'insideBottom', offset: -8, style: { fontSize: 11, fill: '#495057' } }} />
                        <YAxis tick={{ fontSize: 11 }} width={60}
                            label={{ value: 'Monthly average', angle: -90, position: 'insideLeft', style: { fontSize: 11, fill: '#495057' } }} />
                        <Tooltip contentStyle={tooltipStyle} formatter={fmtVal} />
                        <Legend wrapperStyle={{ fontSize: 12 }} />
                        {monthlyAvgKeys.map((k, i) => (
                            <Bar key={k} dataKey={k} fill={SERIES_COLORS[i % SERIES_COLORS.length]}
                                radius={[2, 2, 0, 0]} isAnimationActive={false} />
                        ))}
                    </BarChart>
                </ResponsiveContainer>
            </div>
            {/* Daily Flow */}
            {showFlowTabs && (
                <div ref={hiddenDailyFlowRef} style={{ width: CAPTURE_W, height: CAPTURE_H, padding: '16px 8px 8px' }}>
                    <ResponsiveContainer width={CAPTURE_W - 16} height={CAPTURE_H - 24}>
                        <AreaChart data={dailyFlowData} margin={{ top: 8, right: 24, left: 8, bottom: 24 }}>
                            <defs>
                                {dailyFlowKeys.map((k, i) => (
                                    <linearGradient key={k} id={`hgrad-${i}`} x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="5%" stopColor={SERIES_COLORS[i % SERIES_COLORS.length]} stopOpacity={0.25} />
                                        <stop offset="95%" stopColor={SERIES_COLORS[i % SERIES_COLORS.length]} stopOpacity={0.03} />
                                    </linearGradient>
                                ))}
                            </defs>
                            <CartesianGrid strokeDasharray="3 3" stroke="rgba(0,0,0,0.06)" />
                            <XAxis dataKey="date" tick={{ fontSize: 11 }} tickFormatter={shortDate} minTickGap={40}
                                label={{ value: 'Date', position: 'insideBottom', offset: -8, style: { fontSize: 11, fill: '#495057' } }} />
                            <YAxis tick={{ fontSize: 11 }} width={60}
                                label={{ value: 'Flow (m³/s)', angle: -90, position: 'insideLeft', style: { fontSize: 11, fill: '#495057' } }} />
                            <Tooltip contentStyle={tooltipStyle} formatter={fmtVal} />
                            <Legend wrapperStyle={{ fontSize: 12 }} />
                            {dailyFlowKeys.map((k, i) => (
                                <Area key={k} type="monotone" dataKey={k}
                                    stroke={SERIES_COLORS[i % SERIES_COLORS.length]}
                                    fill={`url(#hgrad-${i})`} strokeWidth={2} dot={false}
                                    isAnimationActive={false} />
                            ))}
                        </AreaChart>
                    </ResponsiveContainer>
                </div>
            )}
            {/* Monthly Volume */}
            {showFlowTabs && (
                <div ref={hiddenMonthlyVolRef} style={{ width: CAPTURE_W, height: CAPTURE_H, padding: '16px 8px 8px' }}>
                    <ResponsiveContainer width={CAPTURE_W - 16} height={CAPTURE_H - 24}>
                        <BarChart data={monthlyVolData} margin={{ top: 8, right: 24, left: 8, bottom: 24 }}>
                            <CartesianGrid strokeDasharray="3 3" stroke="rgba(0,0,0,0.06)" />
                            <XAxis dataKey="month" tick={{ fontSize: 11 }}
                                label={{ value: 'Month', position: 'insideBottom', offset: -8, style: { fontSize: 11, fill: '#495057' } }} />
                            <YAxis tick={{ fontSize: 11 }} width={66}
                                label={{ value: 'Volume (m³)', angle: -90, position: 'insideLeft', style: { fontSize: 11, fill: '#495057' } }} />
                            <Tooltip contentStyle={tooltipStyle} formatter={fmtVal} />
                            <Legend wrapperStyle={{ fontSize: 12 }} />
                            {monthlyVolKeys.map((k, i) => (
                                <Bar key={k} dataKey={k} fill={SERIES_COLORS[i % SERIES_COLORS.length]}
                                    radius={[2, 2, 0, 0]} isAnimationActive={false} />
                            ))}
                        </BarChart>
                    </ResponsiveContainer>
                </div>
            )}
        </div>

        {/* ── Visible tabbed UI ── */}
        <Card withBorder radius="md" p="md" data-report-exclude="true">
            <Stack gap="md">
                <Group justify="space-between">
                    <Title order={4}>{t('charts.title')}</Title>
                    {loading && <Loader size="xs" />}
                </Group>

                <DateFilterBar
                    pendingFrom={pendingFrom}
                    pendingTo={pendingTo}
                    onFromChange={setPendingFrom}
                    onToChange={setPendingTo}
                    onApply={handleApply}
                    onReset={handleReset}
                    t={t}
                />

                <Tabs defaultValue="timeSeries" keepMounted={false}>
                    <Tabs.List>
                        <Tabs.Tab value="timeSeries">{t('charts.timeSeries')}</Tabs.Tab>
                        <Tabs.Tab value="dailyAvg">{t('charts.dailyAvg')}</Tabs.Tab>
                        <Tabs.Tab value="monthlyAvg">{t('charts.monthlyAvg')}</Tabs.Tab>
                        {showFlowTabs && (
                            <Tabs.Tab value="dailyFlow">{t('charts.dailyFlow')}</Tabs.Tab>
                        )}
                        {showFlowTabs && (
                            <Tabs.Tab value="monthlyVolume">{t('charts.monthlyVolume')}</Tabs.Tab>
                        )}
                    </Tabs.List>

                    {/* Time Series — Line chart with interactive Brush range selector */}
                    <Tabs.Panel value="timeSeries" pt="md">
                        {loading ? <ChartSkeleton /> : (
                            <ResponsiveContainer width="100%" height={360}>
                                <LineChart data={timeSeriesData} margin={{ top: 8, right: 24, left: 8, bottom: 80 }}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="rgba(0,0,0,0.06)" />
                                    <XAxis dataKey="date" tick={{ fontSize: 11 }} tickFormatter={shortDate} minTickGap={40}
                                        label={{ value: 'Date', position: 'insideBottom', offset: 44, style: { fontSize: 11, fill: '#495057' } }} />
                                    <YAxis tick={{ fontSize: 11 }} width={64}
                                        label={{ value: 'Value', angle: -90, position: 'insideLeft', style: { fontSize: 11, fill: '#495057' } }} />
                                    <Tooltip contentStyle={tooltipStyle} formatter={fmtVal} />
                                    <Legend verticalAlign="bottom" wrapperStyle={{ fontSize: 12, bottom: 0, lineHeight: '16px' }} />
                                    {timeSeriesKeys.map((k, i) => (
                                        <Line
                                            key={k}
                                            type="monotone"
                                            dataKey={k}
                                            stroke={SERIES_COLORS[i % SERIES_COLORS.length]}
                                            dot={false}
                                            strokeWidth={2}
                                            isAnimationActive={false}
                                        />
                                    ))}
                                    <Brush
                                        dataKey="date"
                                        height={24}
                                        stroke="#adb5bd"
                                        travellerWidth={8}
                                        tickFormatter={shortDate}
                                    />
                                </LineChart>
                            </ResponsiveContainer>
                        )}
                    </Tabs.Panel>

                    {/* Daily Average — Bar chart per calendar day */}
                    <Tabs.Panel value="dailyAvg" pt="md">
                        {loading ? <ChartSkeleton /> : (
                            <ResponsiveContainer width="100%" height={360}>
                                <BarChart data={dailyAvgData} margin={{ top: 8, right: 24, left: 8, bottom: 56 }}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="rgba(0,0,0,0.06)" />
                                    <XAxis dataKey="date" tick={{ fontSize: 11 }} tickFormatter={shortDate} minTickGap={40}
                                        label={{ value: 'Date', position: 'insideBottom', offset: -4, style: { fontSize: 11, fill: '#495057' } }} />
                                    <YAxis tick={{ fontSize: 11 }} width={64}
                                        label={{ value: 'Daily average', angle: -90, position: 'insideLeft', style: { fontSize: 11, fill: '#495057' } }} />
                                    <Tooltip contentStyle={tooltipStyle} formatter={fmtVal} />
                                    <Legend verticalAlign="bottom" wrapperStyle={{ fontSize: 12, bottom: 0, lineHeight: '16px' }} />
                                    {dailyAvgKeys.map((k, i) => (
                                        <Bar
                                            key={k}
                                            dataKey={k}
                                            fill={SERIES_COLORS[i % SERIES_COLORS.length]}
                                            radius={[2, 2, 0, 0]}
                                            isAnimationActive={false}
                                            maxBarSize={20}
                                        />
                                    ))}
                                </BarChart>
                            </ResponsiveContainer>
                        )}
                    </Tabs.Panel>

                    {/* Monthly Average — Bar chart grouped by YYYY-MM */}
                    <Tabs.Panel value="monthlyAvg" pt="md">
                        {loading ? <ChartSkeleton /> : (
                            <ResponsiveContainer width="100%" height={360}>
                                <BarChart data={monthlyAvgData} margin={{ top: 8, right: 24, left: 8, bottom: 56 }}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="rgba(0,0,0,0.06)" />
                                    <XAxis dataKey="month" tick={{ fontSize: 11 }}
                                        label={{ value: 'Month', position: 'insideBottom', offset: -4, style: { fontSize: 11, fill: '#495057' } }} />
                                    <YAxis tick={{ fontSize: 11 }} width={64}
                                        label={{ value: 'Monthly average', angle: -90, position: 'insideLeft', style: { fontSize: 11, fill: '#495057' } }} />
                                    <Tooltip contentStyle={tooltipStyle} formatter={fmtVal} />
                                    <Legend verticalAlign="bottom" wrapperStyle={{ fontSize: 12, bottom: 0, lineHeight: '16px' }} />
                                    {monthlyAvgKeys.map((k, i) => (
                                        <Bar
                                            key={k}
                                            dataKey={k}
                                            fill={SERIES_COLORS[i % SERIES_COLORS.length]}
                                            radius={[2, 2, 0, 0]}
                                            isAnimationActive={false}
                                        />
                                    ))}
                                </BarChart>
                            </ResponsiveContainer>
                        )}
                    </Tabs.Panel>

                    {/* Daily Flow m³/s — Area chart (flow + dam_level only) */}
                    {showFlowTabs && (
                        <Tabs.Panel value="dailyFlow" pt="md">
                            {loading ? <ChartSkeleton /> : (
                                dailyFlowData.length === 0 ? (
                                    <Text c="dimmed" size="sm" py="md" ta="center">{t('charts.noData')}</Text>
                                ) : (
                                    <ResponsiveContainer width="100%" height={360}>
                                        <AreaChart data={dailyFlowData} margin={{ top: 8, right: 24, left: 8, bottom: 80 }}>
                                            <defs>
                                                {dailyFlowKeys.map((k, i) => (
                                                    <linearGradient key={k} id={`grad-${i}`} x1="0" y1="0" x2="0" y2="1">
                                                        <stop offset="5%" stopColor={SERIES_COLORS[i % SERIES_COLORS.length]} stopOpacity={0.25} />
                                                        <stop offset="95%" stopColor={SERIES_COLORS[i % SERIES_COLORS.length]} stopOpacity={0.03} />
                                                    </linearGradient>
                                                ))}
                                            </defs>
                                            <CartesianGrid strokeDasharray="3 3" stroke="rgba(0,0,0,0.06)" />
                                            <XAxis dataKey="date" tick={{ fontSize: 11 }} tickFormatter={shortDate} minTickGap={40}
                                                label={{ value: 'Date', position: 'insideBottom', offset: 44, style: { fontSize: 11, fill: '#495057' } }} />
                                            <YAxis tick={{ fontSize: 11 }} width={64} label={{ value: 'Flow (m³/s)', angle: -90, position: 'insideLeft', style: { fontSize: 11, fill: '#495057' } }} />
                                            <Tooltip contentStyle={tooltipStyle} formatter={fmtVal} />
                                            <Legend verticalAlign="bottom" wrapperStyle={{ fontSize: 12, bottom: 0, lineHeight: '16px' }} />
                                            {dailyFlowKeys.map((k, i) => (
                                                <Area
                                                    key={k}
                                                    type="monotone"
                                                    dataKey={k}
                                                    stroke={SERIES_COLORS[i % SERIES_COLORS.length]}
                                                    fill={`url(#grad-${i})`}
                                                    strokeWidth={2}
                                                    dot={false}
                                                    isAnimationActive={false}
                                                />
                                            ))}
                                            <Brush
                                                dataKey="date"
                                                height={24}
                                                stroke="#adb5bd"
                                                travellerWidth={8}
                                                tickFormatter={shortDate}
                                            />
                                        </AreaChart>
                                    </ResponsiveContainer>
                                )
                            )}
                        </Tabs.Panel>
                    )}

                    {/* Monthly Volume — cumulative sum bar chart (flow types only) */}
                    {showFlowTabs && (
                        <Tabs.Panel value="monthlyVolume" pt="md">
                            {loading ? <ChartSkeleton /> : (
                                monthlyVolData.length === 0 ? (
                                    <Text c="dimmed" size="sm" py="md" ta="center">{t('charts.noData')}</Text>
                                ) : (
                                    <ResponsiveContainer width="100%" height={360}>
                                        <BarChart data={monthlyVolData} margin={{ top: 8, right: 24, left: 8, bottom: 56 }}>
                                            <CartesianGrid strokeDasharray="3 3" stroke="rgba(0,0,0,0.06)" />
                                            <XAxis dataKey="month" tick={{ fontSize: 11 }}
                                                label={{ value: 'Month', position: 'insideBottom', offset: -4, style: { fontSize: 11, fill: '#495057' } }} />
                                            <YAxis tick={{ fontSize: 11 }} width={64} label={{ value: 'Volume (m³)', angle: -90, position: 'insideLeft', style: { fontSize: 11, fill: '#495057' } }} />
                                            <Tooltip contentStyle={tooltipStyle} formatter={fmtVal} />
                                            <Legend verticalAlign="bottom" wrapperStyle={{ fontSize: 12, bottom: 0, lineHeight: '16px' }} />
                                            {monthlyVolKeys.map((k, i) => (
                                                <Bar
                                                    key={k}
                                                    dataKey={k}
                                                    fill={SERIES_COLORS[i % SERIES_COLORS.length]}
                                                    radius={[2, 2, 0, 0]}
                                                    isAnimationActive={false}
                                                />
                                            ))}
                                        </BarChart>
                                    </ResponsiveContainer>
                                )
                            )}
                        </Tabs.Panel>
                    )}
                </Tabs>
            </Stack>
        </Card>
        </>
    );
});

export default StationCharts;

// ─── Sub-components ───────────────────────────────────────────────────────────

function DateFilterBar({
    pendingFrom, pendingTo, onFromChange, onToChange, onApply, onReset, t,
}: {
    pendingFrom: string;
    pendingTo: string;
    onFromChange: (v: string) => void;
    onToChange: (v: string) => void;
    onApply: () => void;
    onReset: () => void;
    t: (key: string) => string;
}) {
    return (
        <Group gap="xs" wrap="wrap">
            <TextInput
                label={t('charts.from')}
                type="date"
                value={pendingFrom}
                onChange={(e) => onFromChange(e.currentTarget.value)}
                style={{ width: 160 }}
            />
            <TextInput
                label={t('charts.to')}
                type="date"
                value={pendingTo}
                onChange={(e) => onToChange(e.currentTarget.value)}
                style={{ width: 160 }}
            />
            <Button onClick={onApply} mt={22}>
                {t('charts.apply')}
            </Button>
            {(pendingFrom || pendingTo) && (
                <Button variant="subtle" onClick={onReset} mt={22}>
                    {t('charts.reset')}
                </Button>
            )}
        </Group>
    );
}

function ChartSkeleton() {
    return (
        <Stack gap="xs" py="sm">
            <Skeleton height={240} radius="sm" />
        </Stack>
    );
}

// ─── Formatters ───────────────────────────────────────────────────────────────

function shortDate(d: string): string {
    if (!d || d.length < 7) return d;
    // YYYY-MM-DD → DD/MM or YYYY-MM → MM/YY
    if (d.length === 10) {
        const [y, m, day] = d.split('-');
        return `${day}/${m}`;
    }
    if (d.length === 7) {
        const [y, m] = d.split('-');
        return `${m}/${y.slice(2)}`;
    }
    return d;
}

function fmtVal(value: unknown): string {
    const n = Number(value);
    return isNaN(n) ? String(value) : n.toLocaleString(undefined, { maximumFractionDigits: 3 });
}
