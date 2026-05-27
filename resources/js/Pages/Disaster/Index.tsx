import React, { useMemo, useState } from 'react';
import { Head } from '@inertiajs/react';
import {
    Badge,
    Box,
    Card,
    Container,
    Divider,
    Grid,
    Group,
    Paper,
    ScrollArea,
    SegmentedControl,
    SimpleGrid,
    Stack,
    Text,
    ThemeIcon,
    Title,
} from '@mantine/core';
import {
    IconAlertTriangle,
    IconEye,
    IconFlame,
    IconMap2,
    IconTable,
} from '@tabler/icons-react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import GisMap, { type GisStationData } from '@/Components/Dashboard/GisMap';
import HazardAlertMatrix, {
    type CurrentStatus,
    type HazardType,
    type ManagementArea,
    type StatusLevel,
} from './partials/HazardAlertMatrix';

interface RecentIncident {
    id: string;
    reference: string;
    title: string;
    severity_level: string | null;
    incident_status: string;
    review_status: string;
    hazard_code: string;
    latitude: number | null;
    longitude: number | null;
    affected_radius_km: number | null;
    occurred_at: string | null;
    reported_at: string;
    resolved_at: string | null;
    area_id: string | null;
    hazard_name: string | null;
    area_name: string | null;
}

interface Stats {
    active_incidents: number;
    watch_or_above_areas: number;
    critical_areas: number;
}

interface DisasterIndexProps {
    hazardTypes: HazardType[];
    statusLevels: StatusLevel[];
    areas: ManagementArea[];
    currentStatuses: CurrentStatus[];
    recentIncidents: RecentIncident[];
    incidentStations: {
        incident_id: string;
        station_id: string;
        role: string | null;
        code: string;
        name: string;
        latitude: number;
        longitude: number;
    }[];
    stats: Stats;
    canManage: boolean;
}

function severityColor(level: string | null): string {
    switch (level?.toLowerCase()) {
        case 'critical': return '#ef4444';
        case 'high':     return '#f97316';
        case 'medium':   return '#eab308';
        case 'low':      return '#22c55e';
        default:         return '#94a3b8';
    }
}

function incidentStatusColor(status: string): string {
    switch (status) {
        case 'active':              return 'red';
        case 'reported':            return 'blue';
        case 'under_investigation': return 'indigo';
        case 'contained':           return 'orange';
        case 'resolved':            return 'green';
        case 'closed':              return 'gray';
        default:                    return 'gray';
    }
}

function formatDate(value: string | null): string {
    if (!value) return '—';
    try {
        return new Date(value).toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    } catch {
        return value;
    }
}

interface StatCardProps {
    label: string;
    value: number;
    icon: React.ReactNode;
    color: string;
}

function StatCard({ label, value, icon, color }: StatCardProps) {
    return (
        <Paper withBorder p="md" radius="md">
            <Group justify="space-between" wrap="nowrap">
                <Box>
                    <Text size="xs" c="dimmed" tt="uppercase" fw={700} mb={4}>
                        {label}
                    </Text>
                    <Text fw={700} size="xl">
                        {value}
                    </Text>
                </Box>
                <ThemeIcon color={color} variant="light" size="xl" radius="md">
                    {icon}
                </ThemeIcon>
            </Group>
        </Paper>
    );
}

const INCIDENT_MAP_LEGENDS = [
    { color: '#ef4444', label: 'Critical' },
    { color: '#f97316', label: 'High' },
    { color: '#eab308', label: 'Medium' },
    { color: '#22c55e', label: 'Low' },
    { color: '#94a3b8', label: 'Unknown' },
];

export default function DisasterIndex({
    hazardTypes,
    statusLevels,
    areas,
    currentStatuses,
    recentIncidents,
    stats,
}: DisasterIndexProps) {
    const { t } = useTranslation('disaster');
    const [activeView, setActiveView] = useState<'matrix' | 'map'>('matrix');

    const incidentMarkers = useMemo<GisStationData[]>(() =>
        recentIncidents
            .filter((i) => i.latitude != null && i.longitude != null)
            .map((i) => ({
                id: i.id,
                code: i.reference,
                name: i.title,
                latitude: i.latitude!,
                longitude: i.longitude!,
                is_real_time: false,
                value: i.affected_radius_km ?? null,
                unit: 'km radius',
                color: severityColor(i.severity_level),
                radius: (i.affected_radius_km ?? 5) * 10,
                popupData: [
                    { label: t('incidents.reference'), value: i.reference },
                    { label: t('incidents.hazard'), value: i.hazard_name ?? i.hazard_code },
                    { label: t('incidents.area'), value: i.area_name ?? '—' },
                    { label: t('incidents.status'), value: i.incident_status },
                    { label: t('incidents.reported'), value: formatDate(i.reported_at) },
                ],
            })),
        [recentIncidents, t],
    );

    return (
        <AuthenticatedLayout>
            <Head title={t('title')} />

            <Container size="xl" py="lg">
                <Title order={2} mb="lg">
                    {t('title')}
                </Title>

                {/* Stats row */}
                <SimpleGrid cols={{ base: 1, xs: 3 }} mb="lg">
                    <StatCard
                        label={t('stats.activeIncidents')}
                        value={stats.active_incidents}
                        icon={<IconAlertTriangle size={20} />}
                        color="red"
                    />
                    <StatCard
                        label={t('stats.watchOrAboveAreas')}
                        value={stats.watch_or_above_areas}
                        icon={<IconEye size={20} />}
                        color="yellow"
                    />
                    <StatCard
                        label={t('stats.criticalAreas')}
                        value={stats.critical_areas}
                        icon={<IconFlame size={20} />}
                        color="red"
                    />
                </SimpleGrid>

                <Grid gutter="md">
                    {/* Main view panel */}
                    <Grid.Col span={{ base: 12, lg: 8 }}>
                        <Paper withBorder radius="md" p="md">
                            <Group justify="space-between" mb="md" wrap="wrap" gap="xs">
                                <Text fw={600} size="sm">
                                    {activeView === 'matrix'
                                        ? t('matrix.title')
                                        : t('map.legendTitle')}
                                </Text>
                                <SegmentedControl
                                    value={activeView}
                                    onChange={(v) => setActiveView(v as 'matrix' | 'map')}
                                    data={[
                                        {
                                            value: 'matrix',
                                            label: (
                                                <Group gap={6} wrap="nowrap">
                                                    <IconTable size={14} />
                                                    <span>{t('views.matrix')}</span>
                                                </Group>
                                            ),
                                        },
                                        {
                                            value: 'map',
                                            label: (
                                                <Group gap={6} wrap="nowrap">
                                                    <IconMap2 size={14} />
                                                    <span>{t('views.map')}</span>
                                                </Group>
                                            ),
                                        },
                                    ]}
                                />
                            </Group>

                            {activeView === 'matrix' && (
                                <HazardAlertMatrix
                                    hazardTypes={hazardTypes}
                                    areas={areas}
                                    currentStatuses={currentStatuses}
                                    statusLevels={statusLevels}
                                />
                            )}

                            {activeView === 'map' && (
                                <GisMap
                                    stations={incidentMarkers}
                                    legendTitle={t('map.legendTitle')}
                                    legends={INCIDENT_MAP_LEGENDS}
                                    height={460}
                                />
                            )}
                        </Paper>
                    </Grid.Col>

                    {/* Incident feed */}
                    <Grid.Col span={{ base: 12, lg: 4 }}>
                        <Paper withBorder radius="md" p="md" h="100%">
                            <Text fw={600} size="sm" mb="md">
                                {t('incidents.title')}
                            </Text>

                            {recentIncidents.length === 0 ? (
                                <Text size="sm" c="dimmed" ta="center" py="xl">
                                    {t('incidents.empty')}
                                </Text>
                            ) : (
                                <ScrollArea h={500} offsetScrollbars>
                                    <Stack gap="xs">
                                        {recentIncidents.map((incident, idx) => (
                                            <React.Fragment key={incident.id}>
                                                {idx > 0 && <Divider />}
                                                <IncidentCard incident={incident} />
                                            </React.Fragment>
                                        ))}
                                    </Stack>
                                </ScrollArea>
                            )}
                        </Paper>
                    </Grid.Col>
                </Grid>
            </Container>
        </AuthenticatedLayout>
    );
}

function IncidentCard({ incident }: { incident: RecentIncident }) {
    const { t } = useTranslation('disaster');
    const color = severityColor(incident.severity_level);
    const isResolved = !!incident.resolved_at;

    return (
        <Card p="xs" radius="sm" withBorder={false} style={{ position: 'relative', overflow: 'visible' }}>
            <Group wrap="nowrap" gap="xs" align="flex-start">
                {/* Severity color bar */}
                <Box
                    style={{
                        width: 4,
                        borderRadius: 2,
                        backgroundColor: color,
                        alignSelf: 'stretch',
                        minHeight: 40,
                        flexShrink: 0,
                    }}
                />
                <Stack gap={2} style={{ flex: 1, minWidth: 0 }}>
                    <Group justify="space-between" wrap="nowrap" gap="xs">
                        <Text size="xs" c="dimmed" fw={500} style={{ fontFamily: 'monospace' }}>
                            {incident.reference}
                        </Text>
                        <Badge
                            size="xs"
                            color={incidentStatusColor(incident.incident_status)}
                            variant="light"
                        >
                            {t(`incidentStatus.${incident.incident_status}`, {
                                defaultValue: incident.incident_status,
                            })}
                        </Badge>
                    </Group>
                    <Text size="sm" fw={500} lineClamp={2}>
                        {incident.title}
                    </Text>
                    <Group gap="xs" wrap="wrap">
                        {incident.hazard_name && (
                            <Text size="xs" c="dimmed">{incident.hazard_name}</Text>
                        )}
                        {incident.hazard_name && incident.area_name && (
                            <Text size="xs" c="dimmed">·</Text>
                        )}
                        {incident.area_name && (
                            <Text size="xs" c="dimmed">{incident.area_name}</Text>
                        )}
                    </Group>
                    <Text size="xs" c="dimmed">
                        {isResolved
                            ? `${t('incidents.resolved')}: ${formatDate(incident.resolved_at)}`
                            : `${t('incidents.reported')}: ${formatDate(incident.reported_at)}`}
                    </Text>
                </Stack>
            </Group>
        </Card>
    );
}
