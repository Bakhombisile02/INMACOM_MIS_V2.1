/**
 * HistoricalCharts — Recharts implementations of the specialised "Historical
 * Data" charts shown on each GIS module page. These replace the previous
 * hand-rolled SVG components so the GIS pages share the polished look and feel
 * of the Station Show page (axis labels, tooltips, legends, consistent palette).
 *
 * Exported components:
 *   • DamDrawdownChart       — Reservoir drawdown + 30-day variability signals
 *   • FlowDurationChart      — Flow Duration Curve (FDC, % exceedance)
 *   • FlowDailyDischargeChart— Historical Daily Discharge time series
 *   • WqHistoricalChart      — Water quality parameter historical trends
 *   • RainfallHydrographChart— Daily events (bars) + cumulative (line)
 *   • AquiferTrendChart      — Groundwater piezometric depth trend
 *
 * Each component renders a <ResponsiveContainer> sized to fill its parent and
 * gracefully degrades to an empty-state message when no data is provided.
 */

import { Center, Text } from '@mantine/core';
import {
    Area,
    AreaChart,
    Bar,
    CartesianGrid,
    ComposedChart,
    Legend,
    Line,
    LineChart,
    ReferenceLine,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

// ─── Shared styling ──────────────────────────────────────────────────────────

const tooltipStyle = {
    fontSize: 12,
    borderRadius: 8,
    border: '1px solid rgba(0,0,0,0.08)',
    background: '#ffffff',
};

const axisTick = { fontSize: 11, fill: '#495057' };
const axisLabelStyle = { fontSize: 11, fill: '#495057' };
const gridStroke = 'rgba(0,0,0,0.06)';

function EmptyState({ height = 240, message }: { height?: number; message: string }) {
    return (
        <Center h={height} style={{ backgroundColor: 'rgba(0,0,0,0.02)', borderRadius: 8 }}>
            <Text c="dimmed" size="xs">{message}</Text>
        </Center>
    );
}

const shortDate = (iso: string | number) => {
    const d = new Date(iso);
    if (isNaN(d.getTime())) return String(iso);
    return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
};

function sampleStdDev(values: number[]) {
    if (values.length < 2) return 0;

    const mean = values.reduce((sum, v) => sum + v, 0) / values.length;
    const variance = values.reduce((sum, v) => sum + (v - mean) ** 2, 0) / (values.length - 1);

    return Math.sqrt(variance);
}

// ─── 1. Dam Reservoir Drawdown + Rolling Variability Signals ──────────────────

interface DrawdownRow { date: string; value: number; [k: string]: unknown }

export function DamDrawdownChart({
    data,
    height = 260,
    criticalLimit = 20,
}: {
    data: DrawdownRow[];
    height?: number;
    criticalLimit?: number;
}) {
    if (!data || data.length === 0) {
        return <EmptyState height={height} message="No historical records available for charting." />;
    }

    const sorted = [...data]
        .sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime())
        .map((d) => ({ ...d, value: Number(d.value) }));

    const rollingWindowDays = 30;
    const chartData = sorted.map((d, i) => {
        const start = Math.max(0, i - rollingWindowDays + 1);
        const windowValues = sorted.slice(start, i + 1).map((row) => row.value);
        const rollingMean = windowValues.reduce((sum, value) => sum + value, 0) / windowValues.length;
        const rollingStdDev = sampleStdDev(windowValues);
        const lowerBand = Math.max(0, rollingMean - rollingStdDev);

        return {
            date: d.date,
            'Storage (%)': d.value,
            '30-day mean': Number(rollingMean.toFixed(2)),
            '30-day low band (1 SD)': Number(lowerBand.toFixed(2)),
        };
    });

    return (
        <ResponsiveContainer width="100%" height={height}>
            <AreaChart data={chartData} margin={{ top: 8, right: 24, left: 8, bottom: 56 }}>
                <defs>
                    <linearGradient id="damGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%"  stopColor="#2b8a3e" stopOpacity={0.25} />
                        <stop offset="95%" stopColor="#2b8a3e" stopOpacity={0.02} />
                    </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" stroke={gridStroke} />
                <XAxis dataKey="date" tick={axisTick} tickFormatter={shortDate} minTickGap={32}
                    label={{ value: 'Date', position: 'insideBottom', offset: -4, style: axisLabelStyle }} />
                <YAxis tick={axisTick} width={60} domain={[0, 100]}
                    label={{ value: 'Storage (%)', angle: -90, position: 'insideLeft', style: axisLabelStyle }} />
                <Tooltip contentStyle={tooltipStyle}
                    labelFormatter={(v) => new Date(v as string).toLocaleDateString()}
                    formatter={(val, key) => [`${Number(val).toFixed(2)} %`, key]} />
                <Legend verticalAlign="bottom" wrapperStyle={{ fontSize: 12, bottom: 0, lineHeight: "16px" }} />
                <ReferenceLine y={criticalLimit} stroke="#e03131" strokeDasharray="5 3"
                    label={{ value: `Critical Limit (${criticalLimit}%)`, position: 'insideTopRight', fill: '#e03131', fontSize: 10 }} />
                <Area type="monotone" dataKey="Storage (%)" stroke="#2b8a3e" strokeWidth={2}
                    fill="url(#damGrad)" dot={{ r: 2 }} activeDot={{ r: 4 }} isAnimationActive={false} />
                <Line type="monotone" dataKey="30-day mean" stroke="#f08c00" strokeWidth={1.5}
                    dot={false} isAnimationActive={false} />
                <Line type="monotone" dataKey="30-day low band (1 SD)" stroke="#e03131" strokeWidth={1.5}
                    strokeDasharray="6 4" dot={false} isAnimationActive={false} />
            </AreaChart>
        </ResponsiveContainer>
    );
}

// ─── 2. Flow Duration Curve (FDC) ────────────────────────────────────────────

interface FdcRow { percentile: number; value: number }

export function FlowDurationChart({
    data,
    height = 260,
    limit = null,
    limitLabel = 'IIMA Limit',
}: {
    data: FdcRow[];
    height?: number;
    limit?: number | null;
    limitLabel?: string;
}) {
    if (!data || data.length === 0) {
        return <EmptyState height={height} message="No historical records available for charting." />;
    }

    return (
        <ResponsiveContainer width="100%" height={height}>
            <AreaChart data={data} margin={{ top: 8, right: 24, left: 8, bottom: 56 }}>
                <defs>
                    <linearGradient id="fdcGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%"  stopColor="#0b7285" stopOpacity={0.25} />
                        <stop offset="95%" stopColor="#0b7285" stopOpacity={0.02} />
                    </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" stroke={gridStroke} />
                <XAxis dataKey="percentile" tick={axisTick}
                    tickFormatter={(v) => `${v}%`}
                    label={{ value: '% time exceeded', position: 'insideBottom', offset: -4, style: axisLabelStyle }} />
                <YAxis tick={axisTick} width={60}
                    label={{ value: 'Flow (m³/s)', angle: -90, position: 'insideLeft', style: axisLabelStyle }} />
                <Tooltip contentStyle={tooltipStyle}
                    labelFormatter={(v) => `${v}% exceedance`}
                    formatter={(val) => [`${val} m³/s`, 'Flow']} />
                <Legend verticalAlign="bottom" wrapperStyle={{ fontSize: 12, bottom: 0, lineHeight: "16px" }} />
                {limit !== null && (
                    <ReferenceLine y={limit} stroke="#e03131" strokeDasharray="5 3"
                        label={{ value: `${limitLabel} (${limit} m³/s)`, position: 'insideTopRight', fill: '#e03131', fontSize: 10 }} />
                )}
                <Area name="Flow (m³/s)" type="monotone" dataKey="value" stroke="#0b7285" strokeWidth={2}
                    fill="url(#fdcGrad)" dot={{ r: 2 }} activeDot={{ r: 4 }} isAnimationActive={false} />
            </AreaChart>
        </ResponsiveContainer>
    );
}

// ─── 3. Historical Daily Discharge ───────────────────────────────────────────

interface DischargeRow { date: string; value: number }

export function FlowDailyDischargeChart({
    data,
    height = 260,
    limit = null,
    limitLabel = 'IIMA Limit',
}: {
    data: DischargeRow[];
    height?: number;
    limit?: number | null;
    limitLabel?: string;
}) {
    if (!data || data.length === 0) {
        return <EmptyState height={height} message="No historical records available for charting." />;
    }

    const sorted = [...data]
        .sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime())
        .map((d) => ({ ...d, value: Number(d.value) }));

    return (
        <ResponsiveContainer width="100%" height={height}>
            <AreaChart data={sorted} margin={{ top: 8, right: 24, left: 8, bottom: 56 }}>
                <defs>
                    <linearGradient id="dischGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%"  stopColor="#1c7ed6" stopOpacity={0.25} />
                        <stop offset="95%" stopColor="#1c7ed6" stopOpacity={0.02} />
                    </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" stroke={gridStroke} />
                <XAxis dataKey="date" tick={axisTick} tickFormatter={shortDate} minTickGap={32}
                    label={{ value: 'Date', position: 'insideBottom', offset: -4, style: axisLabelStyle }} />
                <YAxis tick={axisTick} width={60}
                    label={{ value: 'Discharge (m³/s)', angle: -90, position: 'insideLeft', style: axisLabelStyle }} />
                <Tooltip contentStyle={tooltipStyle}
                    labelFormatter={(v) => new Date(v as string).toLocaleDateString()}
                    formatter={(val) => [`${val} m³/s`, 'Discharge']} />
                <Legend verticalAlign="bottom" wrapperStyle={{ fontSize: 12, bottom: 0, lineHeight: "16px" }} />
                {limit !== null && (
                    <ReferenceLine y={limit} stroke="#e03131" strokeDasharray="5 3"
                        label={{ value: `${limitLabel} (${limit} m³/s)`, position: 'insideTopRight', fill: '#e03131', fontSize: 10 }} />
                )}
                <Area name="Discharge (m³/s)" type="monotone" dataKey="value" stroke="#1c7ed6" strokeWidth={2}
                    fill="url(#dischGrad)" dot={{ r: 2 }} activeDot={{ r: 4 }} isAnimationActive={false} />
            </AreaChart>
        </ResponsiveContainer>
    );
}

// ─── 4. Water Quality Historical Trends ──────────────────────────────────────

interface WqRow { date: string; value: number; unit?: string }

export function WqHistoricalChart({
    data,
    parameter = 'Value',
    unit,
    height = 260,
    threshold = null,
}: {
    data: WqRow[];
    parameter?: string;
    unit?: string;
    height?: number;
    threshold?: number | null;
}) {
    if (!data || data.length === 0) {
        return <EmptyState height={height} message="No historical records available for charting." />;
    }

    const sorted = [...data]
        .sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime())
        .map((d) => ({ ...d, value: Number(d.value) }));

    const yLabel = unit ? `${parameter} (${unit})` : parameter;

    return (
        <ResponsiveContainer width="100%" height={height}>
            <AreaChart data={sorted} margin={{ top: 8, right: 24, left: 8, bottom: 56 }}>
                <defs>
                    <linearGradient id="wqGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%"  stopColor="#e8590c" stopOpacity={0.25} />
                        <stop offset="95%" stopColor="#e8590c" stopOpacity={0.02} />
                    </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" stroke={gridStroke} />
                <XAxis dataKey="date" tick={axisTick} tickFormatter={shortDate} minTickGap={32}
                    label={{ value: 'Date', position: 'insideBottom', offset: -4, style: axisLabelStyle }} />
                <YAxis tick={axisTick} width={64}
                    label={{ value: yLabel, angle: -90, position: 'insideLeft', style: axisLabelStyle }} />
                <Tooltip contentStyle={tooltipStyle}
                    labelFormatter={(v) => new Date(v as string).toLocaleDateString()}
                    formatter={(val) => [unit ? `${val} ${unit}` : String(val), parameter]} />
                <Legend verticalAlign="bottom" wrapperStyle={{ fontSize: 12, bottom: 0, lineHeight: "16px" }} />
                {threshold !== null && (
                    <ReferenceLine y={threshold} stroke="#e03131" strokeDasharray="5 3"
                        label={{ value: `REIWQ Limit (${threshold}${unit ? ' ' + unit : ''})`, position: 'insideTopRight', fill: '#e03131', fontSize: 10 }} />
                )}
                <Area name={parameter} type="monotone" dataKey="value" stroke="#e8590c" strokeWidth={2}
                    fill="url(#wqGrad)" dot={{ r: 2 }} activeDot={{ r: 4 }} isAnimationActive={false} />
            </AreaChart>
        </ResponsiveContainer>
    );
}

// ─── 5. Rainfall Hydrograph (Events vs Cumulative) ───────────────────────────

interface RainRow { date: string; value: number }

export function RainfallHydrographChart({
    data,
    height = 280,
}: {
    data: RainRow[];
    height?: number;
}) {
    if (!data || data.length === 0) {
        return <EmptyState height={height} message="No historical precipitation records available for charting." />;
    }

    const sorted = [...data]
        .sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime());

    let cum = 0;
    const chartData = sorted.map((r) => {
        const daily = Number(r.value);
        cum += daily;
        return {
            date: r.date,
            'Daily (mm)': parseFloat(daily.toFixed(2)),
            'Cumulative (mm)': parseFloat(cum.toFixed(2)),
        };
    });

    return (
        <ResponsiveContainer width="100%" height={height}>
            <ComposedChart data={chartData} margin={{ top: 8, right: 32, left: 8, bottom: 56 }}>
                <CartesianGrid strokeDasharray="3 3" stroke={gridStroke} />
                <XAxis dataKey="date" tick={axisTick} tickFormatter={shortDate} minTickGap={32}
                    label={{ value: 'Date', position: 'insideBottom', offset: -4, style: axisLabelStyle }} />
                <YAxis yAxisId="left" tick={axisTick} width={60}
                    label={{ value: 'Cumulative (mm)', angle: -90, position: 'insideLeft', style: axisLabelStyle }} />
                <YAxis yAxisId="right" orientation="right" tick={axisTick} width={60}
                    label={{ value: 'Daily (mm)', angle: 90, position: 'insideRight', style: axisLabelStyle }} />
                <Tooltip contentStyle={tooltipStyle}
                    labelFormatter={(v) => new Date(v as string).toLocaleDateString()} />
                <Legend verticalAlign="bottom" wrapperStyle={{ fontSize: 12, bottom: 0, lineHeight: "16px" }} />
                <Bar yAxisId="right" dataKey="Daily (mm)" fill="#339af0" opacity={0.75}
                    radius={[2, 2, 0, 0]} isAnimationActive={false} />
                <Line yAxisId="left" type="monotone" dataKey="Cumulative (mm)" stroke="#0c8599"
                    strokeWidth={2.5} dot={{ r: 2 }} activeDot={{ r: 4 }} isAnimationActive={false} />
            </ComposedChart>
        </ResponsiveContainer>
    );
}

// ─── 6. Groundwater Aquifer / Piezometric Trend ──────────────────────────────

interface AquiferRow { date: string; value: number }

export function AquiferTrendChart({
    readings,
    height = 260,
    threshold = 10,
}: {
    readings: AquiferRow[];
    height?: number;
    threshold?: number;
}) {
    if (!readings || readings.length === 0) {
        return <EmptyState height={height} message="No piezometric readings available for charting." />;
    }

    const sorted = [...readings]
        .sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime())
        .map((d) => ({ ...d, value: Number(d.value) }));

    return (
        <ResponsiveContainer width="100%" height={height}>
            <LineChart data={sorted} margin={{ top: 8, right: 24, left: 8, bottom: 56 }}>
                <CartesianGrid strokeDasharray="3 3" stroke={gridStroke} />
                <XAxis dataKey="date" tick={axisTick} tickFormatter={shortDate} minTickGap={32}
                    label={{ value: 'Date', position: 'insideBottom', offset: -4, style: axisLabelStyle }} />
                <YAxis tick={axisTick} width={64}
                    label={{ value: 'Depth (m)', angle: -90, position: 'insideLeft', style: axisLabelStyle }} />
                <Tooltip contentStyle={tooltipStyle}
                    labelFormatter={(v) => new Date(v as string).toLocaleDateString()}
                    formatter={(val) => [`${val} m`, 'Depth']} />
                <Legend verticalAlign="bottom" wrapperStyle={{ fontSize: 12, bottom: 0, lineHeight: "16px" }} />
                <ReferenceLine y={threshold} stroke="#e03131" strokeDasharray="5 3"
                    label={{ value: `Critical Alert Limit (${threshold} m)`, position: 'insideTopRight', fill: '#e03131', fontSize: 10 }} />
                <Line name="Depth (m)" type="monotone" dataKey="value" stroke="#1c7ed6" strokeWidth={2.5}
                    dot={{ r: 2 }} activeDot={{ r: 5 }} isAnimationActive={false} />
            </LineChart>
        </ResponsiveContainer>
    );
}
