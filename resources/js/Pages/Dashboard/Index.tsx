import { Head, Link } from '@inertiajs/react';
import {
    IconAlertTriangle,
    IconDroplet,
    IconFileText,
    IconMapPin,
    IconCheck,
    IconX,
    IconActivity,
    IconClock,
    IconBuildingBridge,
    IconWaveSine,
    IconFilter,
    IconChevronRight,
    IconExclamationCircle,
    IconDatabase,
    IconBellRinging,
    IconArrowUpRight,
    IconScale
} from '@tabler/icons-react';
import {
    Card,
    Container,
    Group,
    SimpleGrid,
    Stack,
    Text,
    Title,
    Tabs,
    Badge,
    Progress,
    RingProgress,
    ThemeIcon,
    Table,
    Button,
    Grid,
    Divider,
    Box,
    Select,
    Alert
} from '@mantine/core';
import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { PageProps } from '@/types';
import classes from './Index.module.css';

interface Reservoir {
    id: string;
    code: string;
    name: string;
    country: string;
    river_basin: string;
    latest_value: number;
    unit: string;
    fsc: number;
    date: string;
}

interface RessanoGarciaFlow {
    value: number;
    unit: string;
    date: string;
    min_required: number;
    is_compliant: boolean;
}

interface EflowPoint {
    id: string;
    code: string;
    name: string;
    river: string;
    country: string;
    min_flow_m3_s: number | null;
    current_flow: number | null;
    is_compliant: boolean | null;
    date: string | null;
}

interface HazardStatus {
    id: string;
    hazard_code: string;
    hazard_name: string;
    area_name: string;
    area_code: string;
    level_code: string;
    level_name: string;
    severity: number;
    color: string;
    score: number;
    calculated_at: string;
}

interface ActiveIncident {
    id: string;
    reference: string;
    title: string;
    hazard_code: string;
    hazard_name: string;
    incident_status: string;
    severity_level: string;
    reported_at: string;
    area_name: string;
    pollutant_name?: string | null;
    estimated_mass_kg?: number | null;
    fish_kill_observed?: boolean | null;
    waterborne_disease_reported?: boolean | null;
}

interface VerificationQueue {
    measurements: number;
    incidents: number;
}

interface DashboardIndexProps extends PageProps {
    summary: {
        stations: number;
        measurements: number;
        incidents: number;
        documents: number;
    };
    reservoirs: Reservoir[];
    ressanoGarciaFlow: RessanoGarciaFlow | null;
    eflowPoints: EflowPoint[];
    hazardStatuses: HazardStatus[];
    activeIncidents: ActiveIncident[];
    verificationQueue: VerificationQueue;
}

export default function DashboardIndex({
    summary,
    reservoirs,
    ressanoGarciaFlow,
    eflowPoints,
    hazardStatuses,
    activeIncidents,
    verificationQueue,
    permissions
}: DashboardIndexProps) {
    const { t } = useTranslation('app');
    const { t: tNav } = useTranslation('navigation');
    const [activeTab, setActiveTab] = useState<string | null>('overview');
    const [basinFilter, setBasinFilter] = useState<string>('all');

    // Filter ecological flow points based on selected basin filter
    const filteredEflowPoints = eflowPoints.filter((point) => {
        if (basinFilter === 'all') return true;
        if (basinFilter === 'incomati') {
            return (
                point.river.toLowerCase().includes('incomati') ||
                point.river.toLowerCase().includes('komati') ||
                point.river.toLowerCase().includes('sabie') ||
                point.river.toLowerCase().includes('croc') ||
                point.river.toLowerCase().includes('nwaswitsontso')
            );
        }
        if (basinFilter === 'maputo') {
            return (
                point.river.toLowerCase().includes('maputo') ||
                point.river.toLowerCase().includes('usuthu') ||
                point.river.toLowerCase().includes('pongola')
            );
        }
        return true;
    });

    // Helper to render severity badge
    const getSeverityColor = (severity: string | number) => {
        const sev = String(severity).toLowerCase();
        if (sev.includes('severe') || sev === '3' || sev === 'high') return 'red';
        if (sev.includes('moderate') || sev === '2' || sev === 'medium') return 'orange';
        if (sev.includes('watch') || sev === '1' || sev === 'low') return 'yellow';
        return 'teal';
    };

    const getHazardBadgeColor = (levelCode: string) => {
        const code = levelCode.toLowerCase();
        if (code.includes('severe') || code.includes('extreme') || code === 'red') return 'red';
        if (code.includes('moderate') || code === 'orange') return 'orange';
        if (code.includes('watch') || code === 'yellow') return 'yellow';
        return 'teal';
    };

    const formatDateTime = (dateStr: string | null) => {
        if (!dateStr) return '-';
        try {
            const date = new Date(dateStr);
            return date.toLocaleDateString(undefined, {
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch {
            return dateStr;
        }
    };

    return (
        <>
            <Head title={tNav('dashboard')} />

            <Container size="xl" py="xl">
                {/* Immersive Glass Header Panel */}
                <Stack gap="xs" mb="xl">
                    <Title order={1} style={{ letterSpacing: '-0.02em', fontWeight: 800 }}>
                        {t('dashboard.title')}
                    </Title>
                    <Text c="dimmed" size="lg">
                        {t('dashboard.subtitle')}
                    </Text>
                </Stack>

                {/* Main 4-Tab Navigation */}
                <Tabs value={activeTab} onChange={setActiveTab} variant="outline" radius="md" mb="xl">
                    <Tabs.List>
                        <Tabs.Tab value="overview" leftSection={<IconActivity size={16} />}>
                            {t('dashboard.tabs.overview')}
                        </Tabs.Tab>
                        <Tabs.Tab value="hydrology" leftSection={<IconDroplet size={16} />}>
                            {t('dashboard.tabs.hydrology')}
                        </Tabs.Tab>
                        <Tabs.Tab value="treaty" leftSection={<IconBuildingBridge size={16} />}>
                            {t('dashboard.tabs.treaty')}
                        </Tabs.Tab>
                        <Tabs.Tab value="hazards" leftSection={<IconAlertTriangle size={16} />}>
                            {t('dashboard.tabs.hazards')}
                            {activeIncidents.length > 0 && (
                                <Badge size="xs" color="red" variant="filled" circle ml={6} className={classes.pulseDot}>
                                    {activeIncidents.length}
                                </Badge>
                            )}
                        </Tabs.Tab>
                    </Tabs.List>

                    {/* OVERVIEW TAB */}
                    <Tabs.Panel value="overview" pt="lg" className={classes.tabContent}>
                        {/* Summary KPI Grid */}
                        <SimpleGrid cols={{ base: 1, sm: 2, lg: 4 }} spacing="md" mb="xl">
                            <Card className={classes.metricCard} withBorder radius="md" p="md">
                                <Group justify="space-between">
                                    <Text fw={600} size="sm" c="dimmed">
                                        {t('dashboard.cards.stations')}
                                    </Text>
                                    <ThemeIcon variant="light" color="blue" size="lg" radius="md">
                                        <IconMapPin size={20} />
                                    </ThemeIcon>
                                </Group>
                                <Title order={2} mt="sm" style={{ fontSize: '2rem' }}>
                                    {summary.stations}
                                </Title>
                                <Text size="sm" mt="sm">
                                    <Link href={route('stations.index')} style={{ color: 'var(--mantine-color-blue-6)', textDecoration: 'none', fontWeight: 600 }}>
                                        {t('dashboard.cards.openStations')} &rarr;
                                    </Link>
                                </Text>
                            </Card>

                            <Card className={classes.metricCard} withBorder radius="md" p="md">
                                <Group justify="space-between">
                                    <Text fw={600} size="sm" c="dimmed">
                                        {t('dashboard.cards.measurements')}
                                    </Text>
                                    <ThemeIcon variant="light" color="teal" size="lg" radius="md">
                                        <IconDroplet size={20} />
                                    </ThemeIcon>
                                </Group>
                                <Title order={2} mt="sm" style={{ fontSize: '2rem' }}>
                                    {summary.measurements}
                                </Title>
                                <Text size="sm" mt="sm" c="dimmed">
                                    {t('dashboard.cards.liveData')}
                                </Text>
                            </Card>

                            <Card className={classes.metricCard} withBorder radius="md" p="md">
                                <Group justify="space-between">
                                    <Text fw={600} size="sm" c="dimmed">
                                        {t('dashboard.cards.incidents')}
                                    </Text>
                                    <ThemeIcon variant="light" color="red" size="lg" radius="md">
                                        <IconAlertTriangle size={20} />
                                    </ThemeIcon>
                                </Group>
                                <Title order={2} mt="sm" style={{ fontSize: '2rem' }}>
                                    {summary.incidents}
                                </Title>
                                <Text size="sm" mt="sm" c="dimmed">
                                    {t('dashboard.cards.monitorAlerts')}
                                </Text>
                            </Card>

                            <Card className={classes.metricCard} withBorder radius="md" p="md">
                                <Group justify="space-between">
                                    <Text fw={600} size="sm" c="dimmed">
                                        {t('dashboard.cards.documents')}
                                    </Text>
                                    <ThemeIcon variant="light" color="grape" size="lg" radius="md">
                                        <IconFileText size={20} />
                                    </ThemeIcon>
                                </Group>
                                <Title order={2} mt="sm" style={{ fontSize: '2rem' }}>
                                    {summary.documents}
                                </Title>
                                <Text size="sm" mt="sm">
                                    <Link href={route('library')} style={{ color: 'var(--mantine-color-grape-6)', textDecoration: 'none', fontWeight: 600 }}>
                                        {t('dashboard.cards.openLibrary')} &rarr;
                                    </Link>
                                </Text>
                            </Card>
                        </SimpleGrid>

                        <Grid gutter="md">
                            {/* Ressano Garcia Gauge - Critical Mainstem Compliance */}
                            <Grid.Col span={{ base: 12, lg: 8 }}>
                                <Card
                                    withBorder
                                    radius="md"
                                    p="xl"
                                    className={`${classes.metricCard} ${classes.ressanoGarciaCard}`}
                                    data-compliant={ressanoGarciaFlow ? String(ressanoGarciaFlow.is_compliant) : 'true'}
                                >
                                    <Group justify="space-between" align="flex-start" mb="md">
                                        <Stack gap="xs">
                                            <Badge color={ressanoGarciaFlow?.is_compliant ? 'teal' : 'red'} size="lg" radius="md" variant="filled">
                                                {ressanoGarciaFlow?.is_compliant
                                                    ? t('dashboard.ressanoGarcia.compliant')
                                                    : t('dashboard.ressanoGarcia.nonCompliant')}
                                            </Badge>
                                            <Title order={3} style={{ fontWeight: 700 }}>
                                                {t('dashboard.ressanoGarcia.title')}
                                            </Title>
                                        </Stack>
                                        <ThemeIcon
                                            variant="light"
                                            color={ressanoGarciaFlow?.is_compliant ? 'teal' : 'red'}
                                            size="xl"
                                            radius="md"
                                        >
                                            {ressanoGarciaFlow?.is_compliant ? <IconCheck size={28} /> : <IconAlertTriangle size={28} />}
                                        </ThemeIcon>
                                    </Group>

                                    <Divider my="md" />

                                    {ressanoGarciaFlow ? (
                                        <Grid gutter="xl" align="center">
                                            <Grid.Col span={{ base: 12, sm: 6 }}>
                                                <Group gap="lg">
                                                    <RingProgress
                                                        size={140}
                                                        thickness={12}
                                                        roundCaps
                                                        sections={[
                                                            {
                                                                value: Math.min((ressanoGarciaFlow.value / 5.2) * 100, 100),
                                                                color: ressanoGarciaFlow.is_compliant ? 'teal' : 'red'
                                                            }
                                                        ]}
                                                        label={
                                                            <div style={{ textAlign: 'center' }}>
                                                                <Text size="xl" fw={800} style={{ lineHeight: 1 }}>
                                                                    {ressanoGarciaFlow.value.toFixed(1)}
                                                                </Text>
                                                                <Text size="xs" c="dimmed">
                                                                    {ressanoGarciaFlow.unit}
                                                                </Text>
                                                            </div>
                                                        }
                                                    />
                                                    <Stack gap={2}>
                                                        <Text size="sm" c="dimmed" fw={500}>
                                                            {t('dashboard.ressanoGarcia.currentFlow')}
                                                        </Text>
                                                        <Text size="xl" fw={700}>
                                                            {ressanoGarciaFlow.value} {ressanoGarciaFlow.unit}
                                                        </Text>
                                                    </Stack>
                                                </Group>
                                            </Grid.Col>

                                            <Grid.Col span={{ base: 12, sm: 6 }}>
                                                <Stack gap="sm">
                                                    <Group justify="space-between">
                                                        <Text size="sm" c="dimmed" fw={500}>
                                                            {t('dashboard.ressanoGarcia.minRequired')}
                                                        </Text>
                                                        <Text size="sm" fw={700}>
                                                            {ressanoGarciaFlow.min_required} {ressanoGarciaFlow.unit}
                                                        </Text>
                                                    </Group>
                                                    <Progress
                                                        value={(ressanoGarciaFlow.min_required / 5.2) * 100}
                                                        color="gray"
                                                        size="xs"
                                                        radius="xl"
                                                    />
                                                    <Group gap="xs" mt="xs">
                                                        <IconClock size={14} style={{ color: 'var(--mantine-color-gray-5)' }} />
                                                        <Text size="xs" c="dimmed">
                                                            {t('dashboard.ressanoGarcia.lastUpdated')}:{' '}
                                                            <Text span fw={600}>
                                                                {formatDateTime(ressanoGarciaFlow.date)}
                                                            </Text>
                                                        </Text>
                                                    </Group>
                                                </Stack>
                                            </Grid.Col>
                                        </Grid>
                                    ) : (
                                        <Text c="dimmed" py="md" ta="center">
                                            {t('dashboard.ressanoGarcia.noData')}
                                        </Text>
                                    )}
                                </Card>
                            </Grid.Col>

                            {/* Verification Pipeline Queue */}
                            <Grid.Col span={{ base: 12, lg: 4 }}>
                                <Card
                                    withBorder
                                    radius="md"
                                    p="xl"
                                    className={`${classes.metricCard} ${
                                        verificationQueue.measurements > 0 || verificationQueue.incidents > 0 ? classes.glowPulse : ''
                                    }`}
                                >
                                    <Group justify="space-between" mb="xs">
                                        <Title order={3} style={{ fontWeight: 700 }}>
                                            {t('dashboard.verification.title')}
                                        </Title>
                                        <ThemeIcon variant="light" color="yellow" size="lg" radius="md">
                                            <IconDatabase size={20} />
                                        </ThemeIcon>
                                    </Group>
                                    <Text size="sm" c="dimmed" mb="lg">
                                        {t('dashboard.verification.desc')}
                                    </Text>

                                    <Stack gap="md" mb="xl">
                                        <Group justify="space-between">
                                            <Group gap="xs">
                                                <span
                                                    className={classes.statusIndicator}
                                                    data-status={verificationQueue.measurements > 0 ? 'pending' : 'compliant'}
                                                />
                                                <Text size="sm" fw={500}>
                                                    {t('dashboard.verification.pendingMeasurements')}
                                                </Text>
                                            </Group>
                                            <Badge color={verificationQueue.measurements > 0 ? 'yellow' : 'teal'} variant="light" size="lg">
                                                {verificationQueue.measurements}
                                            </Badge>
                                        </Group>

                                        <Group justify="space-between">
                                            <Group gap="xs">
                                                <span
                                                    className={classes.statusIndicator}
                                                    data-status={verificationQueue.incidents > 0 ? 'pending' : 'compliant'}
                                                />
                                                <Text size="sm" fw={500}>
                                                    {t('dashboard.verification.pendingIncidents')}
                                                </Text>
                                            </Group>
                                            <Badge color={verificationQueue.incidents > 0 ? 'yellow' : 'teal'} variant="light" size="lg">
                                                {verificationQueue.incidents}
                                            </Badge>
                                        </Group>
                                    </Stack>

                                    <Divider my="md" />

                                    {verificationQueue.measurements > 0 || verificationQueue.incidents > 0 ? (
                                        <Button
                                            component={Link}
                                            href={verificationQueue.measurements > 0 ? route('flow-levels.index') : route('disaster.index')}
                                            color="yellow"
                                            fullWidth
                                            leftSection={<IconScale size={18} />}
                                        >
                                            {t('dashboard.verification.reviewAction')}
                                        </Button>
                                    ) : (
                                        <Alert color="teal" icon={<IconCheck size={18} />} radius="md">
                                            {t('dashboard.verification.allClear')}
                                        </Alert>
                                    )}
                                </Card>
                            </Grid.Col>
                        </Grid>
                    </Tabs.Panel>

                    {/* HYDROLOGY & RESERVOIRS TAB */}
                    <Tabs.Panel value="hydrology" pt="lg" className={classes.tabContent}>
                        <Card withBorder radius="md" p="xl" className={classes.metricCard}>
                            <Stack gap="xs" mb="xl">
                                <Title order={2} style={{ fontWeight: 800 }}>
                                    {t('dashboard.reservoirs.title')}
                                </Title>
                                <Text c="dimmed" size="sm">
                                    {t('dashboard.reservoirs.subtitle')}
                                </Text>
                            </Stack>

                            {reservoirs.length > 0 ? (
                                <SimpleGrid cols={{ base: 1, sm: 2, lg: 3 }} spacing="lg">
                                    {reservoirs.map((res) => {
                                        const pct = res.fsc ? Math.round((res.latest_value / res.fsc) * 100) : 0;
                                        return (
                                            <Card key={res.id} withBorder radius="md" p="md" className={classes.metricCard}>
                                                <Group justify="space-between" mb="xs">
                                                    <Stack gap={2}>
                                                        <Text fw={700} size="md">
                                                            {res.name}
                                                        </Text>
                                                        <Text size="xs" c="dimmed">
                                                            {res.code} &bull; {res.country}
                                                        </Text>
                                                    </Stack>
                                                    <Badge color={pct > 75 ? 'teal' : pct > 40 ? 'blue' : 'orange'} variant="light">
                                                        {res.river_basin}
                                                    </Badge>
                                                </Group>

                                                <Divider my="sm" />

                                                <div className={classes.damArcContainer}>
                                                    <RingProgress
                                                        size={130}
                                                        thickness={10}
                                                        roundCaps
                                                        sections={[
                                                            {
                                                                value: Math.min(pct, 100),
                                                                color: pct > 80 ? 'teal' : pct > 45 ? 'blue' : pct > 25 ? 'orange' : 'red'
                                                            }
                                                        ]}
                                                        label={
                                                            <div className={classes.damFillText}>
                                                                <Text size="xl" fw={800} style={{ lineHeight: 1 }}>
                                                                    {pct}%
                                                                </Text>
                                                                <Text size="xs" c="dimmed">
                                                                    filled
                                                                </Text>
                                                            </div>
                                                        }
                                                    />
                                                </div>

                                                <Stack gap="xs" mt="xs">
                                                    <Group justify="space-between">
                                                        <Text size="xs" c="dimmed">
                                                            {t('dashboard.reservoirs.currentStorage')}
                                                        </Text>
                                                        <Text size="sm" fw={700}>
                                                            {res.latest_value.toFixed(1)} {res.unit}
                                                        </Text>
                                                    </Group>

                                                    <Group justify="space-between">
                                                        <Text size="xs" c="dimmed">
                                                            {t('dashboard.reservoirs.fsc')}
                                                        </Text>
                                                        <Text size="sm" fw={600}>
                                                            {res.fsc.toFixed(1)} {res.unit}
                                                        </Text>
                                                    </Group>

                                                    <Group gap="xs" mt="xs">
                                                        <IconClock size={12} style={{ color: 'var(--mantine-color-gray-5)' }} />
                                                        <Text size="xs" c="dimmed">
                                                            {t('dashboard.ressanoGarcia.lastUpdated')}:{' '}
                                                            <Text span fw={600}>
                                                                {formatDateTime(res.date)}
                                                            </Text>
                                                        </Text>
                                                    </Group>
                                                </Stack>
                                            </Card>
                                        );
                                    })}
                                </SimpleGrid>
                            ) : (
                                <Text c="dimmed" py="xl" ta="center">
                                    {t('dashboard.reservoirs.noReservoirs')}
                                </Text>
                            )}
                        </Card>
                    </Tabs.Panel>

                    {/* TREATY COMPLIANCE (IIMA) TAB */}
                    <Tabs.Panel value="treaty" pt="lg" className={classes.tabContent}>
                        <Card withBorder radius="md" p="xl" className={classes.metricCard}>
                            <Grid gutter="md" align="center" mb="xl">
                                <Grid.Col span={{ base: 12, md: 8 }}>
                                    <Title order={2} style={{ fontWeight: 800 }}>
                                        {t('dashboard.compliance.title')}
                                    </Title>
                                    <Text c="dimmed" size="sm">
                                        {t('dashboard.compliance.subtitle')}
                                    </Text>
                                </Grid.Col>

                                <Grid.Col span={{ base: 12, md: 4 }}>
                                    <Group justify="flex-end">
                                        <ThemeIcon variant="light" size="sm" color="blue">
                                            <IconFilter size={16} />
                                        </ThemeIcon>
                                        <Select
                                            value={basinFilter}
                                            onChange={(val) => setBasinFilter(val || 'all')}
                                            data={[
                                                { value: 'all', label: t('dashboard.compliance.allBasins') },
                                                { value: 'incomati', label: t('dashboard.compliance.incomati') },
                                                { value: 'maputo', label: t('dashboard.compliance.maputo') }
                                            ]}
                                            style={{ width: 220 }}
                                        />
                                    </Group>
                                </Grid.Col>
                            </Grid>

                            <Box style={{ overflowX: 'auto' }}>
                                <Table striped highlightOnHover className={classes.complianceTable}>
                                    <Table.Thead>
                                        <Table.Tr>
                                            <Table.Th>{t('dashboard.compliance.keyPoint')}</Table.Th>
                                            <Table.Th>{t('dashboard.compliance.river')}</Table.Th>
                                            <Table.Th>{t('dashboard.compliance.country')}</Table.Th>
                                            <Table.Th>{t('dashboard.compliance.minRequiredEflow')}</Table.Th>
                                            <Table.Th>{t('dashboard.compliance.currentEflow')}</Table.Th>
                                            <Table.Th>{t('dashboard.compliance.status')}</Table.Th>
                                            <Table.Th>{t('dashboard.compliance.lastChecked')}</Table.Th>
                                        </Table.Tr>
                                    </Table.Thead>
                                    <Table.Tbody>
                                        {filteredEflowPoints.map((point) => {
                                            const status =
                                                point.is_compliant === null
                                                    ? 'unknown'
                                                    : point.is_compliant
                                                    ? 'compliant'
                                                    : 'non_compliant';

                                            return (
                                                <Table.Tr key={point.id}>
                                                    <Table.Td>
                                                        <Text fw={600} size="sm">
                                                            {point.code}
                                                        </Text>
                                                        <Text size="xs" c="dimmed">
                                                            {point.name}
                                                        </Text>
                                                    </Table.Td>
                                                    <Table.Td>{point.river}</Table.Td>
                                                    <Table.Td>
                                                        <Badge variant="outline" size="sm">
                                                            {point.country}
                                                        </Badge>
                                                    </Table.Td>
                                                    <Table.Td fw={600}>
                                                        {point.min_flow_m3_s !== null
                                                            ? `${point.min_flow_m3_s.toFixed(2)} m³/s`
                                                            : t('dashboard.compliance.noRequirement')}
                                                    </Table.Td>
                                                    <Table.Td>
                                                        {point.current_flow !== null ? (
                                                            <Text fw={700}>
                                                                {point.current_flow.toFixed(2)} m³/s
                                                            </Text>
                                                        ) : (
                                                            <Text c="dimmed" size="xs" fs="italic">
                                                                {t('dashboard.compliance.noFlowData')}
                                                            </Text>
                                                        )}
                                                    </Table.Td>
                                                    <Table.Td>
                                                        <Group gap="xs">
                                                            <span className={classes.statusIndicator} data-status={status} />
                                                            <Badge
                                                                color={
                                                                    status === 'compliant'
                                                                        ? 'teal'
                                                                        : status === 'non_compliant'
                                                                        ? 'red'
                                                                        : 'gray'
                                                                }
                                                                variant="light"
                                                                size="sm"
                                                            >
                                                                {status === 'compliant'
                                                                    ? t('dashboard.compliance.compliant')
                                                                    : status === 'non_compliant'
                                                                    ? t('dashboard.compliance.nonCompliant')
                                                                    : '-'}
                                                            </Badge>
                                                        </Group>
                                                    </Table.Td>
                                                    <Table.Td>
                                                        <Text size="xs" c="dimmed">
                                                            {formatDateTime(point.date)}
                                                        </Text>
                                                    </Table.Td>
                                                </Table.Tr>
                                            );
                                        })}
                                    </Table.Tbody>
                                </Table>
                            </Box>
                        </Card>
                    </Tabs.Panel>

                    {/* HAZARDS & INCIDENTS TAB */}
                    <Tabs.Panel value="hazards" pt="lg" className={classes.tabContent}>
                        <Grid gutter="lg">
                            {/* Subcatchment Hazard Levels */}
                            <Grid.Col span={{ base: 12, lg: 6 }}>
                                <Card withBorder radius="md" p="xl" className={classes.metricCard}>
                                    <Stack gap="xs" mb="lg">
                                        <Title order={2} style={{ fontWeight: 800 }}>
                                            {t('dashboard.hazards.subcatchmentHazardScores')}
                                        </Title>
                                        <Text c="dimmed" size="sm">
                                            {t('dashboard.hazards.subtitle')}
                                        </Text>
                                    </Stack>

                                    <Stack gap="md">
                                        {hazardStatuses.map((h) => {
                                            const badgeColor = getHazardBadgeColor(h.level_code);
                                            const isFlood = h.hazard_code.toLowerCase() === 'flood';
                                            const scoreLabel = isFlood
                                                ? t('dashboard.hazards.floodWeight')
                                                : t('dashboard.hazards.droughtScore');

                                            return (
                                                <Card
                                                    key={h.id}
                                                    withBorder
                                                    radius="md"
                                                    p="md"
                                                    className={`${classes.metricCard} ${
                                                        h.level_code === 'severe'
                                                            ? classes.glowPulseSevere
                                                            : h.level_code === 'moderate'
                                                            ? classes.glowPulse
                                                            : ''
                                                    }`}
                                                >
                                                    <Group justify="space-between" mb="xs">
                                                        <Stack gap={2}>
                                                            <Text fw={700} size="md">
                                                                {h.area_name}
                                                            </Text>
                                                            <Text size="xs" c="dimmed">
                                                                {h.area_code} &bull; {h.hazard_name}
                                                            </Text>
                                                        </Stack>
                                                        <Badge color={badgeColor} variant="filled" size="md">
                                                            {h.level_name || h.level_code}
                                                        </Badge>
                                                    </Group>

                                                    <Stack gap={4}>
                                                        <Group justify="space-between">
                                                            <Text size="xs" c="dimmed">
                                                                {scoreLabel}
                                                            </Text>
                                                            <Text size="sm" fw={700}>
                                                                {h.score.toFixed(2)} / 10
                                                            </Text>
                                                        </Group>
                                                        <Progress
                                                            value={h.score * 10}
                                                            color={badgeColor}
                                                            size="sm"
                                                            radius="xl"
                                                        />
                                                    </Stack>

                                                    <Group gap="xs" mt="sm" justify="flex-end">
                                                        <IconClock size={12} style={{ color: 'var(--mantine-color-gray-5)' }} />
                                                        <Text size="xs" c="dimmed">
                                                            {t('dashboard.hazards.calculated')}:{' '}
                                                            <Text span fw={600}>
                                                                {formatDateTime(h.calculated_at)}
                                                            </Text>
                                                        </Text>
                                                    </Group>
                                                </Card>
                                            );
                                        })}
                                    </Stack>
                                </Card>
                            </Grid.Col>

                            {/* Active disaster incidents */}
                            <Grid.Col span={{ base: 12, lg: 6 }}>
                                <Card withBorder radius="md" p="xl" className={classes.metricCard}>
                                    <Title order={2} mb="lg" style={{ fontWeight: 800 }}>
                                        {t('dashboard.hazards.activeIncidents')}
                                    </Title>

                                    {activeIncidents.length > 0 ? (
                                        <Stack gap="md">
                                            {activeIncidents.map((incident) => {
                                                const isPollution = incident.hazard_code.toLowerCase() === 'pollution';
                                                const isSevere = incident.severity_level.toLowerCase() === 'severe' || incident.severity_level.toLowerCase() === 'high';

                                                return (
                                                    <Card
                                                        key={incident.id}
                                                        withBorder
                                                        radius="md"
                                                        p="md"
                                                        className={`${classes.metricCard} ${
                                                            isSevere ? classes.glowPulseSevere : ''
                                                        }`}
                                                    >
                                                        <Group justify="space-between" mb="xs">
                                                            <Stack gap={2}>
                                                                <Text fw={700} size="md">
                                                                    {incident.title}
                                                                </Text>
                                                                <Text size="xs" c="dimmed">
                                                                    {t('dashboard.hazards.reference')}: {incident.reference} &bull;{' '}
                                                                    {incident.area_name}
                                                                </Text>
                                                            </Stack>
                                                            <Badge
                                                                color={getSeverityColor(incident.severity_level)}
                                                                variant="filled"
                                                            >
                                                                {incident.severity_level}
                                                            </Badge>
                                                        </Group>

                                                        <Group gap="xs" mb="sm">
                                                            <Badge color="blue" size="xs" variant="outline">
                                                                {incident.hazard_name}
                                                            </Badge>
                                                            <Badge color="gray" size="xs" variant="light">
                                                                {incident.incident_status}
                                                            </Badge>
                                                        </Group>

                                                        {isPollution && (
                                                            <Box
                                                                p="xs"
                                                                mb="sm"
                                                                style={{
                                                                    borderRadius: 'var(--mantine-radius-md)',
                                                                    backgroundColor: 'light-dark(var(--mantine-color-red-0), rgba(250, 82, 82, 0.05))',
                                                                    border: '1px solid light-dark(var(--mantine-color-red-1), rgba(250, 82, 82, 0.15))'
                                                                }}
                                                            >
                                                                <Text size="xs" fw={700} c="red" mb={6}>
                                                                    {t('dashboard.hazards.chemicalDetails')}
                                                                </Text>
                                                                <Grid gutter="xs">
                                                                    <Grid.Col span={6}>
                                                                        <Text size="xs" c="dimmed">
                                                                            Pollutant:
                                                                        </Text>
                                                                        <Text size="xs" fw={600}>
                                                                            {incident.pollutant_name || '-'}
                                                                        </Text>
                                                                    </Grid.Col>
                                                                    <Grid.Col span={6}>
                                                                        <Text size="xs" c="dimmed">
                                                                            {t('dashboard.hazards.mass')}:
                                                                        </Text>
                                                                        <Text size="xs" fw={600}>
                                                                            {incident.estimated_mass_kg !== null
                                                                                ? `${incident.estimated_mass_kg} kg`
                                                                                : '-'}
                                                                        </Text>
                                                                    </Grid.Col>
                                                                    <Grid.Col span={6}>
                                                                        <Text size="xs" c="dimmed">
                                                                            {t('dashboard.hazards.fishKill')}:
                                                                        </Text>
                                                                        <Text size="xs" fw={600}>
                                                                            {incident.fish_kill_observed
                                                                                ? t('dashboard.hazards.yes')
                                                                                : t('dashboard.hazards.no')}
                                                                        </Text>
                                                                    </Grid.Col>
                                                                    <Grid.Col span={6}>
                                                                        <Text size="xs" c="dimmed">
                                                                            {t('dashboard.hazards.waterborne')}:
                                                                        </Text>
                                                                        <Text size="xs" fw={600}>
                                                                            {incident.waterborne_disease_reported
                                                                                ? t('dashboard.hazards.yes')
                                                                                : t('dashboard.hazards.no')}
                                                                        </Text>
                                                                    </Grid.Col>
                                                                </Grid>
                                                            </Box>
                                                        )}

                                                        <Group gap="xs" mt="xs" justify="space-between">
                                                            <Group gap="xs">
                                                                <IconClock size={12} style={{ color: 'var(--mantine-color-gray-5)' }} />
                                                                <Text size="xs" c="dimmed">
                                                                    {t('dashboard.hazards.reported')}:{' '}
                                                                    <Text span fw={600}>
                                                                        {formatDateTime(incident.reported_at)}
                                                                    </Text>
                                                                </Text>
                                                            </Group>
                                                            <Button
                                                                component={Link}
                                                                href={route('disaster.index')}
                                                                size="xs"
                                                                variant="subtle"
                                                                color="red"
                                                                rightSection={<IconChevronRight size={12} />}
                                                            >
                                                                Details
                                                            </Button>
                                                        </Group>
                                                    </Card>
                                                );
                                            })}
                                        </Stack>
                                    ) : (
                                        <Alert color="teal" icon={<IconCheck size={18} />} radius="md">
                                            {t('dashboard.hazards.noIncidents')}
                                        </Alert>
                                    )}
                                </Card>
                            </Grid.Col>
                        </Grid>
                    </Tabs.Panel>
                </Tabs>
            </Container>
        </>
    );
}

DashboardIndex.layout = (page: React.ReactNode) => <AuthenticatedLayout>{page}</AuthenticatedLayout>;
