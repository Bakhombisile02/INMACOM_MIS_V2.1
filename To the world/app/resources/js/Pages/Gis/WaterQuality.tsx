import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    ActionIcon,
    Badge,
    Button,
    Card,
    Container,
    Divider,
    Flex,
    Grid,
    Group,
    Modal,
    NumberInput,
    Progress,
    ScrollArea,
    Select,
    SimpleGrid,
    Stack,
    Table,
    Tabs,
    Text,
    TextInput,
    ThemeIcon,
    Title,
    Tooltip,
} from '@mantine/core';
import { useDisclosure } from '@mantine/hooks';
import { notifications } from '@mantine/notifications';
import {
    IconActivity,
    IconAlertTriangle,
    IconArrowLeft,
    IconCalendar,
    IconCheck,
    IconClipboardList,
    IconEdit,
    IconFlask,
    IconList,
    IconPlus,
    IconRefresh,
    IconSearch,
    IconTrash,
    IconUpload,
    IconDownload,
    IconX,
} from '@tabler/icons-react';
import React, { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import GisMap, { type GisStationData } from '@/Components/Dashboard/GisMap';
import GisPageInfoDrawer from '@/Components/Gis/GisPageInfoDrawer';
import MeasurementImportModal from '@/Components/Gis/MeasurementImportModal';
import { WqHistoricalChart } from '@/Components/Gis/HistoricalCharts';

interface ReadingData {
    value: number;
    unit: string;
    date: string;
    min: number | null;
    max: number | null;
}

interface StationWqRow {
    id: string;
    code: string;
    name: string;
    latitude: number;
    longitude: number;
    country: string | null;
    river_basin: string | null;
    is_real_time: boolean;
    owner_org: string | null;
    readings: Record<string, ReadingData>;
    show_url: string;
    status: string;
    is_active: boolean;
    category: string;
    water_source: string;
    water_body_type: string;
    summary: string | null;
    telemetry_system: string | null;
    gauge_code: string | null;
}

interface WqParameter {
    id: string;
    code: string;
    name: string;
    default_unit: string;
}

interface PendingWqRow {
    id: string;
    station_id: string;
    station_code: string;
    station_name: string;
    parameter_code: string;
    parameter_name: string;
    value: number;
    unit: string;
    date: string;
    status: string;
    submitted_by: string;
    submitted_at: string;
}

interface HistoricalWqRow {
    id: string;
    station_id: string;
    station_code: string;
    station_name: string;
    parameter_code: string;
    parameter_name: string;
    value: number;
    unit: string;
    date: string;
    status: string;
    review_notes: string | null;
    submitted_by: string;
    submitted_at: string;
    reviewed_by: string | null;
    reviewed_at: string | null;
}

interface WaterQualityProps {
    stations: StationWqRow[];
    parameters: WqParameter[];
    pendingQueue: PendingWqRow[];
    historicalLogs: HistoricalWqRow[];
    canManage: boolean;
    userRole: string;
}

export default function WaterQuality({
    stations,
    parameters,
    pendingQueue,
    historicalLogs,
    canManage,
    userRole,
}: WaterQualityProps) {
    const { auth } = usePage<any>().props;
    const { t } = useTranslation('gis');
    const { t: tApprovals } = useTranslation('approvals');
    const { t: tNav } = useTranslation('navigation');

    const [selectedParam, setSelectedParam] = useState<string>('EC');
    const [selectedStationId, setSelectedStationId] = useState<string | null>(null);
    const [infoStationId, setInfoStationId] = useState<string | null>(null);
    const [historicalData, setHistoricalData] = useState<any>(null);
    const [historicalLoading, setHistoricalLoading] = useState(false);

    // Dialog state
    const [createOpened, { open: openCreate, close: closeCreate }] = useDisclosure(false);
    const [editOpened, { open: openEdit, close: closeEdit }] = useDisclosure(false);
    const [rejectOpened, { open: openReject, close: closeReject }] = useDisclosure(false);
    const [importOpen, { open: openImport, close: closeImport }] = useDisclosure(false);
    const [criticalOpen, { open: openCritical, close: closeCritical }] = useDisclosure(false);
    const [selectedMeasurement, setSelectedMeasurement] = useState<any>(null);

    // Form logic
    const createForm = useForm({
        station_id: '',
        measurement_type: 'water_quality',
        parameter_id: '',
        value: '' as number | '',
        unit: '',
        date: new Date().toISOString().split('T')[0],
    });

    const editForm = useForm({
        value: '' as number | '',
        unit: '',
        date: '',
    });

    const rejectForm = useForm({
        review_notes: '',
    });

    const parameterOptions = parameters.map((p) => ({
        value: p.code,
        label: `${p.name} (${p.default_unit})`,
    }));

    // Auto update unit when parameter is selected in create form
    useEffect(() => {
        const selParam = parameters.find(p => p.id === createForm.data.parameter_id);
        if (selParam) {
            createForm.setData('unit', selParam.default_unit || '');
        }
    }, [createForm.data.parameter_id]);

    // Mapped standard stations for the Leaflet GIS Map
    const mappedStations: GisStationData[] = stations.map((station) => {
        let color = 'rgb(127, 127, 127)'; // Default No Value
        let statusText = 'No Value';
        let displayValue: number | null = null;
        let displayUnit = '';

        const reading = station.readings[selectedParam];
        if (reading) {
            displayValue = reading.value;
            displayUnit = reading.unit;

            const val = reading.value;
            const min = reading.min;
            const max = reading.max;
            let isCompliant = true;

            if (min !== null && val < min) isCompliant = false;
            if (max !== null && val > max) isCompliant = false;

            if (isCompliant) {
                color = 'rgb(146, 208, 80)'; // Compliant (Green)
                statusText = 'Compliant';
            } else {
                color = 'rgb(255, 0, 0)'; // Non-Compliant (Red)
                statusText = 'Non-Compliant';
            }
        }

        const limitsText = reading
            ? (reading.min !== null && reading.max !== null
                ? `${reading.min} - ${reading.max}`
                : (reading.max !== null ? `<= ${reading.max}` : (reading.min !== null ? `>= ${reading.min}` : 'No Limit')))
            : 'No Limit';

        return {
            id: station.id,
            code: station.code,
            name: station.name,
            latitude: station.latitude,
            longitude: station.longitude,
            river_basin: station.river_basin,
            is_real_time: station.is_real_time,
            owner_org: station.owner_org,
            show_url: station.show_url,
            status: station.status as 'active' | 'inactive',
            is_active: station.is_active,
            category: station.category,
            water_source: station.water_source,
            water_body_type: station.water_body_type,
            summary: station.summary,
            telemetry_system: station.telemetry_system,
            gauge_code: station.gauge_code,
            value: displayValue,
            unit: displayUnit,
            color,
            popupData: [
                { label: t('waterQuality.popup.variable'), value: selectedParam },
                { label: t('waterQuality.popup.value'), value: displayValue !== null ? `${displayValue} ${displayUnit}` : t('common.noData'), color },
                { label: t('waterQuality.popup.guideline'), value: limitsText },
                { label: t('waterQuality.popup.status'), value: statusText, color },
                { label: t('waterQuality.popup.lastSampled'), value: reading ? new Date(reading.date).toLocaleString() : '—' },
            ],
        };
    });

    const criticalStations = mappedStations.filter(s => s.color === 'rgb(255, 0, 0)');

    const legends = [
        { color: 'rgb(146, 208, 80)', label: t('waterQuality.legend.compliant') },
        { color: 'rgb(255, 0, 0)', label: t('waterQuality.legend.nonCompliant') },
        { color: 'rgb(127, 127, 127)', label: t('waterQuality.legend.noValue') },
    ];

    // Trigger deep dive analytics
    const handleDeepDive = async (stationId: string) => {
        setSelectedStationId(stationId);
        setHistoricalLoading(true);
        try {
            const res = await fetch(`/stations/${stationId}/historical-data`);
            const data = await res.json();
            setHistoricalData(data);
        } catch (error) {
            console.error('Failed to load historical charts:', error);
        } finally {
            setHistoricalLoading(false);
        }
    };

    // Submitting a new measurement (clerk or auto-approve admin/manager)
    const handleCreateSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post(route('measurements.store'), {
            onSuccess: () => {
                closeCreate();
                createForm.reset();
                notifications.show({
                    title: t('waterQuality.notify.logged'),
                    message: t('waterQuality.notify.loggedMsg'),
                    color: 'green',
                    icon: <IconCheck size={18} />,
                    autoClose: 5000,
                });
            },
            onError: (err) => {
                notifications.show({
                    title: t('waterQuality.notify.logFailed'),
                    message: Object.values(err)[0] || 'An error occurred.',
                    color: 'red',
                    icon: <IconAlertTriangle size={18} />,
                });
            },
        });
    };

    // Open Edit modal
    const handleOpenEdit = (log: any) => {
        setSelectedMeasurement(log);
        editForm.setData({
            value: log.value,
            unit: log.unit,
            date: new Date(log.date).toISOString().split('T')[0],
        });
        openEdit();
    };

    // Edit discharge reading
    const handleEditSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedMeasurement) return;

        editForm.patch(route('measurements.update', selectedMeasurement.id), {
            onSuccess: () => {
                closeEdit();
                notifications.show({
                    title: t('waterQuality.notify.updated'),
                    message: t('waterQuality.notify.updatedMsg'),
                    color: 'green',
                    icon: <IconCheck size={18} />,
                    autoClose: 5000,
                });
            },
        });
    };

    // Delete discharge reading
    const handleDelete = (id: string) => {
        if (!confirm(t('waterQuality.notify.deleteConfirm'))) return;

        router.delete(route('measurements.destroy', id), {
            onSuccess: () => {
                notifications.show({
                    title: t('waterQuality.notify.deleted'),
                    message: t('waterQuality.notify.deletedMsg'),
                    color: 'green',
                    icon: <IconCheck size={18} />,
                    autoClose: 5000,
                });
            },
        });
    };

    // Approve Reading
    const handleApprove = (id: string) => {
        router.post(route('measurements.approve', id), {}, {
            onSuccess: () => {
                notifications.show({
                    title: t('waterQuality.notify.approved'),
                    message: t('waterQuality.notify.approvedMsg'),
                    color: 'green',
                    icon: <IconCheck size={18} />,
                    autoClose: 5000,
                });
            },
        });
    };

    // Open Reject Modal
    const handleOpenReject = (log: any) => {
        setSelectedMeasurement(log);
        rejectForm.setData({ review_notes: '' });
        openReject();
    };

    // Reject Reading
    const handleRejectSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedMeasurement) return;

        rejectForm.post(route('measurements.reject', selectedMeasurement.id), {
            onSuccess: () => {
                closeReject();
                notifications.show({
                    title: t('waterQuality.notify.rejected'),
                    message: t('waterQuality.notify.rejectedMsg'),
                    color: 'yellow',
                    icon: <IconX size={18} />,
                    autoClose: 5000,
                });
            },
        });
    };

    const targetStation = selectedStationId ? stations.find(s => s.id === selectedStationId) : null;
    const targetHistorical = historicalData ? historicalData.readings.filter((r: any) => r.parameter_code === selectedParam) : [];

    return (
        <>
            <Head title={tNav('waterQuality')} />

            <Container size="xl" py="xl">
                {/* Header Section */}
                <Flex justify="space-between" align="center" mb="xl">
                    <Stack gap="xs">
                        <Title order={1}>{tNav('waterQuality')}</Title>
                        <Text c="dimmed">{t('waterQuality.description')}</Text>
                    </Stack>
                    <Group gap="md">
                        {selectedStationId && (
                            <Button
                                variant="light"
                                color="gray"
                                leftSection={<IconArrowLeft size={16} />}
                                onClick={() => setSelectedStationId(null)}
                            >
                                {t('common.backToOverview')}
                            </Button>
                        )}
                        {canManage && (
                            <Button
                                variant="light"
                                leftSection={<IconDownload size={16} />}
                                onClick={openImport}
                            >
                                Import data
                            </Button>
                        )}
                        <a href={route('water-quality.export-csv')}>
                            <Button
                                variant="default"
                                leftSection={<IconUpload size={16} />}
                            >
                                {t('exportCsv')}
                            </Button>
                        </a>
                        <Button
                            leftSection={<IconPlus size={18} />}
                            onClick={() => {
                                createForm.reset();
                                openCreate();
                            }}
                        >
                            {t('waterQuality.logButton')}
                        </Button>
                    </Group>
                </Flex>

                <Tabs defaultValue="overview" keepMounted={false} variant="outline" radius="md">
                    <Tabs.List mb="xl">
                        <Tabs.Tab value="overview" leftSection={<IconList size={18} />}>
                            {tApprovals('tabs.overview')}
                        </Tabs.Tab>
                        {canManage && (
                            <Tabs.Tab
                                value="approvals"
                                leftSection={<IconClipboardList size={18} />}
                                rightSection={
                                    pendingQueue.length > 0 ? (
                                        <Badge size="xs" color="red" variant="filled" circle>
                                            {pendingQueue.length}
                                        </Badge>
                                    ) : undefined
                                }
                            >
                                {tApprovals('tabs.approvals')}
                            </Tabs.Tab>
                        )}
                    </Tabs.List>

                    <Tabs.Panel value="overview">

                {/* OVERALL VIEW */}
                {!selectedStationId ? (
                    <Grid gutter="lg">
                        {/* Left Grid: Stats + Table */}
                        <Grid.Col span={{ base: 12, lg: 5 }}>
                            <Stack gap="lg">
                                <Card withBorder radius="md" p="sm">
                                    <Select
                                        label={t('waterQuality.selector.label')}
                                        placeholder={t('waterQuality.selector.placeholder')}
                                        data={parameterOptions}
                                        value={selectedParam}
                                        onChange={(val) => setSelectedParam(val || 'EC')}
                                        size="sm"
                                    />
                                </Card>

                                {/* Aggregate stats */}
                                <SimpleGrid cols={2} spacing="md">
                                    <Card withBorder radius="lg" p="md" style={{ overflow: 'hidden' }}>
                                        <Group justify="space-between" align="flex-start" wrap="nowrap">
                                            <Stack gap="xs">
                                                <Text size="xs" c="dimmed" fw={700} style={{ textTransform: 'uppercase', letterSpacing: '0.5px' }}>
                                                    {t('waterQuality.stats.activeStations')}
                                                </Text>
                                                <Text fw={800} style={{ fontSize: '1.6rem', lineHeight: 1 }}>
                                                    {stations.filter(s => s.readings[selectedParam]).length} <span style={{ fontSize: '1rem', fontWeight: 500 }}>/ {stations.length}</span>
                                                </Text>
                                            </Stack>
                                            <ThemeIcon variant="light" size="xl" radius="md" color="teal">
                                                <IconFlask size={22} />
                                            </ThemeIcon>
                                        </Group>
                                        <Group gap="xs" mt="xs" align="center">
                                            <IconCheck size={14} color="var(--mantine-color-teal-7)" />
                                            <Text size="11px" c="dimmed">
                                                <Text span c="teal.7" fw={700}>{t('waterQuality.stats.sampled')}</Text> {t('waterQuality.stats.parameterResultsOnline')}
                                            </Text>
                                        </Group>
                                    </Card>

                                    <Card withBorder radius="lg" p="md" style={{ overflow: 'hidden', cursor: criticalStations.length > 0 ? 'pointer' : 'default' }} onClick={criticalStations.length > 0 ? openCritical : undefined}>
                                        <Group justify="space-between" align="flex-start" wrap="nowrap">
                                            <Stack gap="xs">
                                                <Text size="xs" c="dimmed" fw={700} style={{ textTransform: 'uppercase', letterSpacing: '0.5px' }}>
                                                    {t('waterQuality.stats.alertExceedances')}
                                                </Text>
                                                <Text fw={800} style={{ fontSize: '1.6rem', lineHeight: 1 }} c={mappedStations.filter(s => s.color === 'rgb(255, 0, 0)').length > 0 ? 'red.7' : 'dark'}>
                                                    {mappedStations.filter(s => s.color === 'rgb(255, 0, 0)').length} <span style={{ fontSize: '1rem', fontWeight: 500 }}>/ {mappedStations.filter(s => s.value !== null).length}</span>
                                                </Text>
                                            </Stack>
                                            <ThemeIcon variant="light" size="xl" radius="md" color={mappedStations.filter(s => s.color === 'rgb(255, 0, 0)').length > 0 ? 'red' : 'gray'}>
                                                <IconAlertTriangle size={22} />
                                            </ThemeIcon>
                                        </Group>
                                        <Group gap="xs" mt="xs" align="center">
                                            {mappedStations.filter(s => s.color === 'rgb(255, 0, 0)').length > 0 ? (
                                                <>
                                                    <IconAlertTriangle size={14} color="var(--mantine-color-red-7)" />
                                                    <Text size="11px" c="dimmed">
                                                        <Text span c="red.7" fw={700}>{t('waterQuality.stats.guidelineViolations')}</Text> {t('waterQuality.stats.activeVariableMargins')}
                                                    </Text>
                                                </>
                                            ) : (
                                                <>
                                                    <IconCheck size={14} color="var(--mantine-color-teal-7)" />
                                                    <Text size="11px" c="dimmed">
                                                        <Text span c="teal.7" fw={700}>{t('waterQuality.stats.compliant')}</Text> {t('waterQuality.stats.activeVariableMargins')}
                                                    </Text>
                                                </>
                                            )}
                                        </Group>
                                    </Card>
                                </SimpleGrid>

                                <Card withBorder radius="md" p="md">
                                    <Title order={4} mb="xs">{t('waterQuality.registry.title')}</Title>
                                    <ScrollArea h={380}>
                                        <Table highlightOnHover verticalSpacing="xs" withRowBorders={false} withTableBorder={false}>
                                            <Table.Thead style={{ backgroundColor: 'var(--mantine-color-gray-0)' }}>
                                                <Table.Tr>
                                                    <Table.Th style={{ fontSize: 11 }}>{t('common.table.code')}</Table.Th>
                                                    <Table.Th style={{ fontSize: 11 }}>{t('common.table.stationName')}</Table.Th>
                                                    <Table.Th style={{ fontSize: 11 }}>{t('common.table.basin')}</Table.Th>
                                                    <Table.Th style={{ fontSize: 11 }}>{t('common.table.value')}</Table.Th>
                                                </Table.Tr>
                                            </Table.Thead>
                                            <Table.Tbody>
                                                {mappedStations.map((station) => (
                                                    <Table.Tr
                                                        key={station.id}
                                                        style={{ cursor: 'pointer' }}
                                                        onClick={() => setInfoStationId(station.id)}
                                                    >
                                                        <Table.Td ff="monospace"><Text size="xs" fw={700}>{station.code}</Text></Table.Td>
                                                        <Table.Td>
                                                            <Text size="xs" fw={600}>{station.name}</Text>
                                                        </Table.Td>
                                                        <Table.Td>
                                                            <Badge size="xs" color={station.river_basin === 'Maputo' ? 'orange' : 'teal'}>
                                                                {station.river_basin}
                                                            </Badge>
                                                        </Table.Td>
                                                        <Table.Td>
                                                            <Text size="xs" fw={800} style={{ color: station.color }}>
                                                                {station.value !== null ? `${station.value} ${station.unit}` : t('common.noData')}
                                                            </Text>
                                                        </Table.Td>
                                                    </Table.Tr>
                                                ))}
                                            </Table.Tbody>
                                        </Table>
                                    </ScrollArea>
                                </Card>
                            </Stack>
                        </Grid.Col>

                        {/* Right Grid: GIS Leaflet map */}
                        <Grid.Col span={{ base: 12, lg: 7 }}>
                            <GisMap
                                stations={mappedStations}
                                legendTitle={`${selectedParam} Guidelines`}
                                legends={legends}
                                height={580}
                                selectedId={infoStationId}
                                onMarkerClick={(id) => setInfoStationId(id)}
                                onDeselect={() => setInfoStationId(null)}
                                canManage={canManage}
                                renderFullscreenDrawer={(fsId, onClose) => (
                                    <GisPageInfoDrawer
                                        station={fsId ? mappedStations.find(s => s.id === fsId) ?? null : null}
                                        onClose={onClose}
                                        onViewDetails={() => { if (fsId) handleDeepDive(fsId); onClose(); }}
                                        withinPortal={false}
                                        zIndex={5000}
                                    />
                                )}
                            />
                        </Grid.Col>
                    </Grid>
                ) : (
                    /* DEEP-DIVE STATION SINGLE VIEW */
                    <Stack gap="lg">
                        {targetStation && (
                            <Card withBorder radius="md" p="lg">
                                <Flex justify="space-between" align="flex-start" wrap="wrap" gap="md">
                                    <Stack gap="4px">
                                        <Group gap="xs">
                                            <Title order={3}>{targetStation.code} — {targetStation.name}</Title>
                                            <Badge color={targetStation.river_basin === 'Maputo' ? 'orange' : 'teal'}>{targetStation.river_basin} Basin</Badge>
                                        </Group>
                                        <Text size="sm" c="dimmed">
                                            Operating Organization: {targetStation.owner_org ?? '—'} | Coordinates: {targetStation.latitude.toFixed(5)}, {targetStation.longitude.toFixed(5)}
                                        </Text>
                                    </Stack>
                                    <Stack align="flex-end" gap={2}>
                                        <Text size="xs" fw={700} c="dimmed">{t('waterQuality.detail.guidanceLimit')}</Text>
                                        <Title order={2} c="red.7">
                                            {targetStation.readings[selectedParam] 
                                                ? (targetStation.readings[selectedParam].min !== null || targetStation.readings[selectedParam].max !== null
                                                    ? `${targetStation.readings[selectedParam].min ?? 0} - ${targetStation.readings[selectedParam].max ?? '∞'} ${targetStation.readings[selectedParam].unit}`
                                                    : t('waterQuality.detail.noLimit'))
                                                : t('waterQuality.detail.noLimit')}
                                        </Title>
                                    </Stack>
                                </Flex>
                            </Card>
                        )}

                        <Grid gutter="lg">
                            {/* Left panel: Detailed Charts + Exceedance matrix */}
                            <Grid.Col span={{ base: 12, lg: 8 }}>
                                <Stack gap="lg">
                                    <Card withBorder radius="md" p="md">
                                        <Group justify="space-between" mb="md">
                                            <Title order={4}>{selectedParam} Historical Trends</Title>
                                            <Select
                                                placeholder={t('waterQuality.detail.parameterPlaceholder')}
                                                data={parameterOptions}
                                                value={selectedParam}
                                                onChange={(val) => setSelectedParam(val || 'EC')}
                                                size="xs"
                                                style={{ width: 180 }}
                                            />
                                        </Group>
                                        {historicalLoading ? (
                                            <Flex justify="center" align="center" h={240}><IconRefresh className="animate-spin" /></Flex>
                                        ) : (
                                            <WqHistoricalChart
                                                data={targetHistorical}
                                                parameter={selectedParam}
                                            />
                                        )}
                                    </Card>

                                    {/* Exceedance Matrix */}
                                    <Card withBorder radius="md" p="md">
                                        <Title order={4} mb="md">{t('waterQuality.matrix.title')}</Title>
                                        <Table highlightOnHover verticalSpacing="xs" withRowBorders={false} withTableBorder={false}>
                                            <Table.Thead style={{ backgroundColor: 'var(--mantine-color-gray-0)' }}>
                                                <Table.Tr>
                                                    <Table.Th style={{ fontSize: 11 }}>{t('waterQuality.matrix.paramCode')}</Table.Th>
                                                    <Table.Th style={{ fontSize: 11 }}>{t('waterQuality.matrix.totalSamples')}</Table.Th>
                                                    <Table.Th style={{ fontSize: 11 }}>{t('waterQuality.matrix.exceedances')}</Table.Th>
                                                    <Table.Th style={{ fontSize: 11 }}>{t('waterQuality.matrix.minValue')}</Table.Th>
                                                    <Table.Th style={{ fontSize: 11 }}>{t('waterQuality.matrix.maxValue')}</Table.Th>
                                                    <Table.Th style={{ fontSize: 11 }}>{t('waterQuality.matrix.compliance')}</Table.Th>
                                                </Table.Tr>
                                            </Table.Thead>
                                            <Table.Tbody>
                                                {historicalData?.wq_matrix && historicalData.wq_matrix.length > 0 ? (
                                                    historicalData.wq_matrix.map((row: any, idx: number) => {
                                                        const ratio = ((row.total_samples - row.exceedances) / row.total_samples) * 100;
                                                        return (
                                                            <Table.Tr key={idx}>
                                                                <Table.Td><Text size="xs" fw={700}>{row.parameter}</Text></Table.Td>
                                                                <Table.Td><Text size="xs">{row.total_samples} {t('waterQuality.matrix.samples')}</Text></Table.Td>
                                                                <Table.Td><Text size="xs" fw={700} c={row.exceedances > 0 ? 'red.7' : 'green.7'}>{row.exceedances} {t('waterQuality.matrix.alerts')}</Text></Table.Td>
                                                                <Table.Td><Text size="xs">{row.min_value.toFixed(2)}</Text></Table.Td>
                                                                <Table.Td><Text size="xs">{row.max_value.toFixed(2)}</Text></Table.Td>
                                                                <Table.Td style={{ width: 180 }}>
                                                                    <Group justify="space-between" mb={4}>
                                                                        <Text size="xs" c="teal.7" fw={800}>
                                                                            {ratio.toFixed(0)}{t('waterQuality.matrix.compliantPct')}
                                                                        </Text>
                                                                        {row.exceedances > 0 && (
                                                                            <Text size="10px" c="red.7" fw={700}>
                                                                                {(100 - ratio).toFixed(0)}{t('waterQuality.matrix.alertPct')}
                                                                            </Text>
                                                                        )}
                                                                    </Group>
                                                                    <Progress.Root size="sm">
                                                                        <Progress.Section
                                                                            value={ratio}
                                                                            color="teal"
                                                                            aria-label="Compliant ratio"
                                                                        />
                                                                        {ratio < 100 && (
                                                                            <Progress.Section
                                                                                value={100 - ratio}
                                                                                color="red"
                                                                                aria-label="Exceedance ratio"
                                                                            />
                                                                        )}
                                                                    </Progress.Root>
                                                                </Table.Td>
                                                            </Table.Tr>
                                                        );
                                                    })
                                                ) : (
                                                    <Table.Tr>
                                                        <Table.Td colSpan={6} style={{ textAlign: 'center', padding: 16 }}>
                                                            <Text size="xs" c="dimmed">{t('waterQuality.matrix.empty')}</Text>
                                                        </Table.Td>
                                                    </Table.Tr>
                                                )}
                                            </Table.Tbody>
                                        </Table>
                                    </Card>
                                </Stack>
                            </Grid.Col>

                            {/* Right panel: Static Map Close-up */}
                            <Grid.Col span={{ base: 12, lg: 4 }}>
                                <Card withBorder radius="md" p="0" style={{ overflow: 'hidden', height: 320 }}>
                                    <GisMap
                                        stations={mappedStations.filter(s => s.id === selectedStationId)}
                                        legendTitle="WQ Station Center"
                                        legends={legends}
                                        height={320}
                                        defaultLegendCollapsed
                                    />
                                </Card>
                            </Grid.Col>
                        </Grid>
                    </Stack>
                )}

                {/* RECENT HISTORICAL DISCHARGE CRUD TABLE */}
                <Card withBorder radius="md" p="md" mt="xl">
                    <Title order={4} mb="md">{t('waterQuality.history.title')}</Title>
                    <ScrollArea h={400}>
                        <Table highlightOnHover verticalSpacing="xs" withRowBorders={false} withTableBorder={false}>
                            <Table.Thead style={{ backgroundColor: 'var(--mantine-color-gray-0)' }}>
                                <Table.Tr>
                                    <Table.Th style={{ fontSize: 11 }}>{t('common.table.station')}</Table.Th>
                                    <Table.Th style={{ fontSize: 11 }}>{t('waterQuality.history.variableHeader')}</Table.Th>
                                    <Table.Th style={{ fontSize: 11 }}>{t('waterQuality.history.valueHeader')}</Table.Th>
                                    <Table.Th style={{ fontSize: 11 }}>{t('common.table.readingDate')}</Table.Th>
                                    <Table.Th style={{ fontSize: 11 }}>{t('common.table.status')}</Table.Th>
                                    <Table.Th style={{ fontSize: 11 }}>{t('common.table.loggedBy')}</Table.Th>
                                    <Table.Th style={{ fontSize: 11 }}>{t('common.table.reviewedBy')}</Table.Th>
                                    <Table.Th style={{ fontSize: 11 }}>{t('common.table.reviewNotes')}</Table.Th>
                                    <Table.Th style={{ fontSize: 11, textAlign: 'right' }}>{t('common.table.actions')}</Table.Th>
                                </Table.Tr>
                            </Table.Thead>
                            <Table.Tbody>
                                {historicalLogs.length > 0 ? (
                                    historicalLogs.map((row) => {
                                        const isOwner = row.submitted_by === auth.user?.display_name;
                                        const canEdit = isOwner || canManage;

                                        return (
                                            <Table.Tr key={row.id}>
                                                <Table.Td>
                                                    <Text size="xs" fw={700}>{row.station_code}</Text>
                                                </Table.Td>
                                                <Table.Td><Text size="xs" fw={700}>{row.parameter_code}</Text></Table.Td>
                                                <Table.Td><Text size="xs" fw={700}>{row.value} {row.unit}</Text></Table.Td>
                                                <Table.Td><Text size="xs">{new Date(row.date).toLocaleDateString()}</Text></Table.Td>
                                                <Table.Td>
                                                    {row.status === 'approved' ? (
                                                        <Badge color="green" variant="light" leftSection={<IconCheck size={10} />}>{t('common.status.approved')}</Badge>
                                                    ) : row.status === 'rejected' ? (
                                                        <Badge color="red" variant="light" leftSection={<IconX size={10} />}>{t('common.status.rejected')}</Badge>
                                                    ) : (
                                                        <Badge color="amber" variant="light" leftSection={<IconAlertTriangle size={10} />}>{t('common.status.pending')}</Badge>
                                                    )}
                                                </Table.Td>
                                                <Table.Td><Text size="xs">{row.submitted_by}</Text></Table.Td>
                                                <Table.Td><Text size="xs">{row.reviewed_by || '—'}</Text></Table.Td>
                                                <Table.Td>
                                                    <Text size="xs" c="dimmed" style={{ maxWidth: '150px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }} title={row.review_notes || ''}>
                                                        {row.review_notes || '—'}
                                                    </Text>
                                                </Table.Td>
                                                <Table.Td>
                                                    <Group gap="xs" justify="flex-end">
                                                        {canEdit && (
                                                            <Tooltip label={t('common.actions.edit')}>
                                                                <ActionIcon size="sm" variant="light" onClick={() => handleOpenEdit(row)}>
                                                                    <IconEdit size={14} />
                                                                </ActionIcon>
                                                            </Tooltip>
                                                        )}
                                                        {canManage && (
                                                            <Tooltip label={t('common.actions.delete')}>
                                                                <ActionIcon size="sm" color="red" variant="light" onClick={() => handleDelete(row.id)}>
                                                                    <IconTrash size={14} />
                                                                </ActionIcon>
                                                            </Tooltip>
                                                        )}
                                                    </Group>
                                                </Table.Td>
                                            </Table.Tr>
                                        );
                                    })
                                ) : (
                                    <Table.Tr>
                                        <Table.Td colSpan={9} style={{ textAlign: 'center', padding: 16 }}>
                                            <Text size="xs" c="dimmed">{t('waterQuality.empty.measurements')}</Text>
                                        </Table.Td>
                                    </Table.Tr>
                                )}
                            </Table.Tbody>
                        </Table>
                    </ScrollArea>
                </Card>
                    </Tabs.Panel>

                    <Tabs.Panel value="approvals">
                        {pendingQueue.length === 0 && (
                            <Card withBorder radius="md" p="xl">
                                <Text c="dimmed" ta="center">{tApprovals('queue.noPending')}</Text>
                            </Card>
                        )}
                {canManage && pendingQueue.length > 0 && (
                    <Card withBorder radius="md" p="md" mt="xl" style={{ borderLeft: '5px solid #d97706' }}>
                        <Title order={4} mb="md" c="amber.8">{t('waterQuality.queue.title')} ({pendingQueue.length})</Title>
                        <Table highlightOnHover verticalSpacing="xs" withRowBorders={false} withTableBorder={false}>
                            <Table.Thead style={{ backgroundColor: 'var(--mantine-color-gray-0)' }}>
                                <Table.Tr>
                                    <Table.Th style={{ fontSize: 11 }}>{t('common.table.station')}</Table.Th>
                                    <Table.Th style={{ fontSize: 11 }}>{t('waterQuality.queue.variableHeader')}</Table.Th>
                                    <Table.Th style={{ fontSize: 11 }}>{t('waterQuality.queue.valueHeader')}</Table.Th>
                                    <Table.Th style={{ fontSize: 11 }}>{t('common.table.readingDate')}</Table.Th>
                                    <Table.Th style={{ fontSize: 11 }}>{t('common.table.submittedBy')}</Table.Th>
                                    <Table.Th style={{ fontSize: 11 }}>{t('common.table.loggedAt')}</Table.Th>
                                    <Table.Th style={{ fontSize: 11, textAlign: 'right' }}>{t('common.table.actions')}</Table.Th>
                                </Table.Tr>
                            </Table.Thead>
                            <Table.Tbody>
                                {pendingQueue.map((row) => (
                                    <Table.Tr key={row.id}>
                                        <Table.Td>
                                            <Text size="xs" fw={700}>{row.station_code}</Text>
                                            <Text size="10px" c="dimmed">{row.station_name}</Text>
                                        </Table.Td>
                                        <Table.Td><Text size="xs" fw={700}>{row.parameter_code} — {row.parameter_name}</Text></Table.Td>
                                        <Table.Td><Text size="xs" fw={800} c="blue.7">{row.value} {row.unit}</Text></Table.Td>
                                        <Table.Td><Text size="xs">{new Date(row.date).toLocaleDateString()}</Text></Table.Td>
                                        <Table.Td><Text size="xs">{row.submitted_by}</Text></Table.Td>
                                        <Table.Td><Text size="xs">{new Date(row.submitted_at).toLocaleString()}</Text></Table.Td>
                                        <Table.Td>
                                            <Group gap="xs" justify="flex-end">
                                                <Button size="xs" color="green" leftSection={<IconCheck size={12} />} onClick={() => handleApprove(row.id)}>
                                                    {t('common.actions.approve')}
                                                </Button>
                                                <Button size="xs" color="red" variant="light" leftSection={<IconX size={12} />} onClick={() => handleOpenReject(row)}>
                                                    {t('common.actions.reject')}
                                                </Button>
                                            </Group>
                                        </Table.Td>
                                    </Table.Tr>
                                ))}
                            </Table.Tbody>
                        </Table>
                    </Card>
                )}
                    </Tabs.Panel>
                </Tabs>
            </Container>

            {/* Modal: Log WQ Sample */}
            <Modal opened={createOpened} onClose={closeCreate} title={<Text fw={700} size="md">{t('waterQuality.modal.log')}</Text>} centered radius="md">
                <form onSubmit={handleCreateSubmit}>
                    <Stack gap="md">
                        <Select
                            label={t('waterQuality.modal.selectStation')}
                            placeholder={t('waterQuality.modal.stationPlaceholder')}
                            data={stations.map((s) => ({ value: s.id, label: `${s.code} — ${s.name}` }))}
                            value={createForm.data.station_id}
                            onChange={(val) => createForm.setData('station_id', val || '')}
                            error={createForm.errors.station_id}
                            required
                            searchable
                        />

                        <Select
                            label={t('waterQuality.modal.selectVariable')}
                            placeholder={t('waterQuality.modal.variablePlaceholder')}
                            data={parameters.map((p) => ({ value: p.id, label: `${p.code} — ${p.name}` }))}
                            value={createForm.data.parameter_id}
                            onChange={(val) => createForm.setData('parameter_id', val || '')}
                            error={createForm.errors.parameter_id}
                            required
                            searchable
                        />

                        <NumberInput
                            label={`${t('waterQuality.modal.valueLabel')} ${createForm.data.unit ? `(${createForm.data.unit})` : ''}`}
                            placeholder={t('waterQuality.modal.valuePlaceholder')}
                            value={createForm.data.value}
                            onChange={(val) => createForm.setData('value', val === '' ? '' : Number(val))}
                            error={createForm.errors.value}
                            required
                            decimalScale={3}
                        />

                        <TextInput
                            label={t('waterQuality.modal.samplingDate')}
                            type="date"
                            leftSection={<IconCalendar size={16} />}
                            value={createForm.data.date}
                            onChange={(e) => createForm.setData('date', e.target.value)}
                            error={createForm.errors.date}
                            required
                        />

                        <Group justify="flex-end" mt="md">
                            <Button variant="light" color="gray" onClick={closeCreate}>
                                {t('common.actions.cancel')}
                            </Button>
                            <Button type="submit" loading={createForm.processing}>
                                {t('common.actions.saveRecord')}
                            </Button>
                        </Group>
                    </Stack>
                </form>
            </Modal>

            {/* Modal: Edit WQ Sample */}
            <Modal opened={editOpened} onClose={closeEdit} title={<Text fw={700} size="md">{t('waterQuality.modal.edit')}</Text>} centered radius="md">
                {selectedMeasurement && (
                    <form onSubmit={handleEditSubmit}>
                        <Stack gap="md">
                            <Stack gap={2} mb="xs">
                                <Text size="xs" fw={700} c="dimmed">{t('waterQuality.modal.stationAndVariable')}</Text>
                                <Text size="sm" fw={700}>{selectedMeasurement.station_code} — {selectedMeasurement.station_name}</Text>
                                <Text size="xs" c="dimmed">{t('waterQuality.modal.parameter')} {selectedMeasurement.parameter_code} ({selectedMeasurement.parameter_name})</Text>
                            </Stack>

                            <NumberInput
                                label={`${t('waterQuality.modal.valueLabel')} (${editForm.data.unit})`}
                                value={editForm.data.value}
                                onChange={(val) => editForm.setData('value', val === '' ? '' : Number(val))}
                                error={editForm.errors.value}
                                required
                                decimalScale={3}
                            />

                            <TextInput
                                label={t('waterQuality.modal.samplingDate')}
                                type="date"
                                leftSection={<IconCalendar size={16} />}
                                value={editForm.data.date}
                                onChange={(e) => editForm.setData('date', e.target.value)}
                                error={editForm.errors.date}
                                required
                            />

                            <Group justify="flex-end" mt="md">
                                <Button variant="light" color="gray" onClick={closeEdit}>
                                    {t('common.actions.cancel')}
                                </Button>
                                <Button type="submit" loading={editForm.processing}>
                                    {t('common.actions.updateRecord')}
                                </Button>
                            </Group>
                        </Stack>
                    </form>
                )}
            </Modal>

            {/* Modal: Reject Submission */}
            <Modal opened={rejectOpened} onClose={closeReject} title={<Text fw={700} size="md" c="red.7">{t('waterQuality.modal.reject')}</Text>} centered radius="md">
                {selectedMeasurement && (
                    <form onSubmit={handleRejectSubmit}>
                        <Stack gap="md">
                            <Text size="xs" mb="xs">{t('common.modal.rejectHelp')}</Text>

                            <TextInput
                                label={t('common.modal.reviewNotesLabel')}
                                placeholder={t('waterQuality.modal.rejectPlaceholder')}
                                value={rejectForm.data.review_notes}
                                onChange={(e) => rejectForm.setData('review_notes', e.target.value)}
                                error={rejectForm.errors.review_notes}
                                required
                            />

                            <Group justify="flex-end" mt="md">
                                <Button variant="light" color="gray" onClick={closeReject}>
                                    {t('common.actions.cancel')}
                                </Button>
                                <Button type="submit" color="red" loading={rejectForm.processing}>
                                    {t('common.actions.confirmReject')}
                                </Button>
                            </Group>
                        </Stack>
                    </form>
                )}
            </Modal>

            {/* Station Info Drawer */}
            <GisPageInfoDrawer
                station={infoStationId ? mappedStations.find((s) => s.id === infoStationId) ?? null : null}
                onClose={() => setInfoStationId(null)}
                onViewDetails={() => { handleDeepDive(infoStationId!); setInfoStationId(null); }}
            />

            {/* Measurement Import Modal */}
            {/* Critical Stations Modal */}
            <Modal
                opened={criticalOpen}
                onClose={closeCritical}
                title={
                    <Group gap="xs">
                        <IconAlertTriangle size={18} color="var(--mantine-color-red-7)" />
                        <Title order={5}>{t('waterQuality.stats.alertExceedances')} — {criticalStations.length}</Title>
                    </Group>
                }
                zIndex={2000}
            >
                <Table highlightOnHover verticalSpacing="xs">
                    <Table.Thead>
                        <Table.Tr>
                            <Table.Th style={{ fontSize: 11 }}>Code</Table.Th>
                            <Table.Th style={{ fontSize: 11 }}>Station</Table.Th>
                            <Table.Th style={{ fontSize: 11 }}>Basin</Table.Th>
                            <Table.Th style={{ fontSize: 11 }}>Value</Table.Th>
                        </Table.Tr>
                    </Table.Thead>
                    <Table.Tbody>
                        {criticalStations.map((s) => (
                            <Table.Tr
                                key={s.id}
                                style={{ cursor: 'pointer' }}
                                onClick={() => { setInfoStationId(s.id); closeCritical(); }}
                            >
                                <Table.Td><Text ff="monospace" fw={700} fz="xs">{s.code}</Text></Table.Td>
                                <Table.Td><Text fz="xs">{s.name}</Text></Table.Td>
                                <Table.Td><Text fz="xs">{s.river_basin ?? '—'}</Text></Table.Td>
                                <Table.Td><Text fz="xs" c="red.7" fw={700}>{s.value?.toFixed(2)} {s.unit}</Text></Table.Td>
                            </Table.Tr>
                        ))}
                    </Table.Tbody>
                </Table>
            </Modal>

            <MeasurementImportModal type="wq" opened={importOpen} onClose={closeImport} />
        </>
    );
}

WaterQuality.layout = (page: React.ReactNode) => <AuthenticatedLayout>{page}</AuthenticatedLayout>;
