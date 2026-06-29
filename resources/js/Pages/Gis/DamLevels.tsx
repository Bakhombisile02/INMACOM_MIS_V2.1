import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    ActionIcon,
    Anchor,
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
    IconList,
    IconPlus,
    IconRefresh,
    IconRipple,
    IconSearch,
    IconTrash,
    IconDownload,
    IconUpload,
    IconX,
} from '@tabler/icons-react';
import React, { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import GisMap, { type GisStationData } from '@/Components/Dashboard/GisMap';
import GisPageInfoDrawer from '@/Components/Gis/GisPageInfoDrawer';
import MeasurementImportModal from '@/Components/Gis/MeasurementImportModal';
import { DamDrawdownChart } from '@/Components/Gis/HistoricalCharts';
import {
    filterAndSortHistoryRows,
    getHistoryDateBounds,
    isDateWithinBounds,
    type HistoryRangeOption,
    type HistorySortOption,
} from '@/Components/Gis/historyFilters';

interface StationDamRow {
    id: string;
    code: string;
    name: string;
    latitude: number;
    longitude: number;
    country: string | null;
    river_basin: string | null;
    is_real_time: boolean;
    owner_org: string | null;
    value: number | null;
    unit: string;
    date: string | null;
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

interface PendingDamRow {
    id: string;
    station_id: string;
    station_code: string;
    station_name: string;
    value: number;
    unit: string;
    date: string;
    status: string;
    submitted_by: string;
    submitted_at: string;
}

interface HistoricalDamRow {
    id: string;
    station_id: string;
    station_code: string;
    station_name: string;
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

interface DamLevelsProps {
    stations: StationDamRow[];
    pendingQueue: PendingDamRow[];
    historicalLogs: HistoricalDamRow[];
    canManage: boolean;
    userRole: string;
}

export default function DamLevels({
    stations,
    pendingQueue,
    historicalLogs,
    canManage,
    userRole,
}: DamLevelsProps) {
    const { auth } = usePage<any>().props;
    const { t } = useTranslation('gis');
    const { t: tApprovals } = useTranslation('approvals');
    const { t: tNav } = useTranslation('navigation');

    const [selectedStationId, setSelectedStationId] = useState<string | null>(null);
    const [infoStationId, setInfoStationId] = useState<string | null>(null);
    const [historicalData, setHistoricalData] = useState<any>(null);
    const [historicalLoading, setHistoricalLoading] = useState(false);
    const [historySearch, setHistorySearch] = useState('');
    const [historySort, setHistorySort] = useState<HistorySortOption>('date_desc');
    const [historyRange, setHistoryRange] = useState<HistoryRangeOption>('all');
    const [historyFrom, setHistoryFrom] = useState('');
    const [historyTo, setHistoryTo] = useState('');

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
        measurement_type: 'dam_level',
        value: '' as number | '',
        unit: '%',
        date: new Date().toISOString().split('T')[0],
    });

    const editForm = useForm({
        value: '' as number | '',
        unit: '%',
        date: '',
    });

    const rejectForm = useForm({
        review_notes: '',
    });

    // Mapped standard stations for the Leaflet GIS Map
    const mappedStations: GisStationData[] = stations.map((station) => {
        let color = 'rgb(127, 127, 127)'; // Default No Value
        let severityText = 'No Value';

        const val = station.value;
        if (val !== null) {
            if (val > 0 && val < 20) {
                color = 'rgb(255, 0, 0)'; // Very Low
                severityText = 'Very Low (<20%)';
            } else if (val >= 20 && val <= 40) {
                color = 'rgb(255, 192, 0)'; // Low
                severityText = 'Low (20-40%)';
            } else if (val > 40 && val < 60) {
                color = 'rgb(146, 208, 80)'; // Normal
                severityText = 'Normal (40-60%)';
            } else if (val >= 60 && val <= 80) {
                color = 'rgb(51, 153, 255)'; // Above Normal
                severityText = 'Above Normal (60-80%)';
            } else if (val > 80 && val <= 100) {
                color = 'rgb(51, 153, 255)'; // High
                severityText = 'High (80-100%)';
            } else if (val > 100) {
                color = 'rgb(0, 51, 204)'; // Imminent Spill / Overflow
                severityText = 'Imminent Spill (>100%)';
            }
        }

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
            value: station.value,
            unit: station.unit,
            color,
            popupData: [
                { label: 'Storage Capacity', value: station.value !== null ? `${station.value} ${station.unit}` : 'No Data', color },
                { label: 'Status', value: severityText, color },
                { label: 'Operating Org', value: station.owner_org ?? '—' },
                { label: 'Last Reading', value: station.date ? new Date(station.date).toLocaleString() : '—' },
            ],
        };
    });

    const criticalStations = stations.filter(s => s.value !== null && s.value < 40);

    const legends = [
        { color: 'rgb(0, 51, 204)', label: 'Imminent Spill (>100%)' },
        { color: 'rgb(51, 153, 255)', label: 'High / Above Normal (60-100%)' },
        { color: 'rgb(146, 208, 80)', label: 'Normal (40-60%)' },
        { color: 'rgb(255, 192, 0)', label: 'Low (20-40%)' },
        { color: 'rgb(255, 0, 0)', label: 'Very Low (<20%)' },
        { color: 'rgb(127, 127, 127)', label: 'No Value' },
    ];

    // Trigger deep dive analytics
    const handleDeepDive = async (stationId: string) => {
        setSelectedStationId(stationId);
        setHistoricalLoading(true);
        try {
            const res = await fetch(`/stations/${stationId}/historical-data?type=dam_level`);
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
                    title: t('dam.notify.logged'),
                    message: t('dam.notify.loggedMsg'),
                    color: 'green',
                    icon: <IconCheck size={18} />,
                    autoClose: 5000,
                });
            },
            onError: (err) => {
                notifications.show({
                    title: t('dam.notify.logFailed'),
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
                    title: t('dam.notify.updated'),
                    message: t('dam.notify.updatedMsg'),
                    color: 'green',
                    icon: <IconCheck size={18} />,
                    autoClose: 5000,
                });
            },
        });
    };

    // Delete discharge reading
    const handleDelete = (id: string) => {
        if (!confirm(t('dam.notify.deleteConfirm'))) return;

        router.delete(route('measurements.destroy', id), {
            onSuccess: () => {
                notifications.show({
                    title: t('dam.notify.deleted'),
                    message: t('dam.notify.deletedMsg'),
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
                    title: t('dam.notify.approved'),
                    message: t('dam.notify.approvedMsg'),
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
                    title: t('dam.notify.rejected'),
                    message: t('dam.notify.rejectedMsg'),
                    color: 'yellow',
                    icon: <IconX size={18} />,
                    autoClose: 5000,
                });
            },
        });
    };

    const targetStation = selectedStationId ? stations.find(s => s.id === selectedStationId) : null;
    const targetHistorical = useMemo(
        () => (historicalData?.readings ?? []).filter((r: any) => r.measurement_type === 'dam_level'),
        [historicalData],
    );

    const historyDateBounds = useMemo(
        () => getHistoryDateBounds(historyRange, historyFrom, historyTo),
        [historyRange, historyFrom, historyTo],
    );

    const filteredChartHistory = useMemo(
        () => targetHistorical.filter((row: any) => isDateWithinBounds(row.date, historyDateBounds)),
        [targetHistorical, historyDateBounds],
    );

    const historyRows = useMemo(() => {
        if (!selectedStationId) {
            return historicalLogs;
        }

        const stationRows = (historicalData?.history_logs as HistoricalDamRow[] | undefined)
            ?? historicalLogs.filter((row) => row.station_id === selectedStationId);

        return stationRows;
    }, [selectedStationId, historicalData, historicalLogs]);

    const filteredHistoryRows = useMemo(
        () => filterAndSortHistoryRows(historyRows, {
            search: historySearch,
            sort: historySort,
            bounds: historyDateBounds,
            searchText: (row) => [
                row.station_code,
                row.station_name,
                row.status,
                row.submitted_by ?? '',
                row.reviewed_by ?? '',
                row.review_notes ?? '',
            ].join(' '),
        }),
        [historyRows, historySearch, historySort, historyDateBounds],
    );

    const historySortOptions = [
        { value: 'date_desc', label: t('common.history.sort.dateDesc') },
        { value: 'date_asc', label: t('common.history.sort.dateAsc') },
        { value: 'value_desc', label: t('common.history.sort.valueDesc') },
        { value: 'value_asc', label: t('common.history.sort.valueAsc') },
    ];

    const historyRangeOptions = [
        { value: 'all', label: t('common.history.range.all') },
        { value: '30d', label: t('common.history.range.last30') },
        { value: '90d', label: t('common.history.range.last90') },
        { value: '180d', label: t('common.history.range.last180') },
        { value: '365d', label: t('common.history.range.last365') },
        { value: 'custom', label: t('common.history.range.custom') },
    ];

    return (
        <>
            <Head title={tNav('damLevels')} />

            <Container size="xl" py="xl">
                {/* Header Section */}
                <Flex justify="space-between" align="center" mb="xl">
                    <Stack gap="xs">
                        <Title order={1}>{tNav('damLevels')}</Title>
                        <Text c="dimmed">{t('dam.description')}</Text>
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
                        <a href={route('dam-levels.export-csv')}>
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
                            {t('dam.logButton')}
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
                                {/* Aggregate stats */}
                                <SimpleGrid cols={2} spacing="md">
                                    <Card withBorder radius="lg" p="md" style={{ overflow: 'hidden' }}>
                                        <Group justify="space-between" align="flex-start" wrap="nowrap">
                                            <Stack gap="xs">
                                                <Text size="xs" c="dimmed" fw={700} style={{ textTransform: 'uppercase', letterSpacing: '0.5px' }}>
                                                    {t('dam.stats.basinStorage')}
                                                </Text>
                                                <Text fw={800} style={{ fontSize: '1.6rem', lineHeight: 1 }}>
                                                    {(stations.filter(s => s.value !== null).reduce((sum, s) => sum + (s.value ?? 0), 0) / Math.max(1, stations.filter(s => s.value !== null).length)).toFixed(1)} <span style={{ fontSize: '1rem', fontWeight: 500 }}>%</span>
                                                </Text>
                                            </Stack>
                                            <ThemeIcon variant="light" size="xl" radius="md" color="blue">
                                                <IconRipple size={22} />
                                            </ThemeIcon>
                                        </Group>
                                        <Text size="11px" c="dimmed" mt="sm" style={{ display: 'flex', alignItems: 'center', gap: 4 }}>
                                            <IconCheck size={14} color="var(--mantine-color-teal-7)" /> <Text span c="teal.7" fw={700}>{t('common.active')}</Text> {t('dam.stats.activeStorageCapacity')}
                                        </Text>
                                    </Card>

                                    <Card withBorder radius="lg" p="md" style={{ overflow: 'hidden', cursor: criticalStations.length > 0 ? 'pointer' : 'default' }} onClick={criticalStations.length > 0 ? openCritical : undefined}>
                                        <Group justify="space-between" align="flex-start" wrap="nowrap">
                                            <Stack gap="xs">
                                                <Text size="xs" c="dimmed" fw={700} style={{ textTransform: 'uppercase', letterSpacing: '0.5px' }}>
                                                    {t('dam.stats.criticalReservoirs')}
                                                </Text>
                                                <Text fw={800} style={{ fontSize: '1.6rem', lineHeight: 1 }} c={stations.filter(s => s.value !== null && s.value < 40).length > 0 ? 'orange.8' : 'dark'}>
                                                    {stations.filter(s => s.value !== null && s.value < 40).length} <span style={{ fontSize: '1rem', fontWeight: 500 }}>/ {stations.filter(s => s.value !== null).length}</span>
                                                </Text>
                                            </Stack>
                                            <ThemeIcon variant="light" size="xl" radius="md" color={stations.filter(s => s.value !== null && s.value < 40).length > 0 ? 'orange' : 'gray'}>
                                                <IconAlertTriangle size={22} />
                                            </ThemeIcon>
                                        </Group>
                                        <Text size="11px" c="dimmed" mt="sm" style={{ display: 'flex', alignItems: 'center', gap: 4 }}>
                                            {stations.filter(s => s.value !== null && s.value < 40).length > 0 ? (
                                                <span style={{ display: 'inline-flex', alignItems: 'center', gap: 4, color: 'var(--mantine-color-orange-8)', fontWeight: 700 }}>
                                                    <IconAlertTriangle size={14} /> {t('dam.stats.storageBelow40')}
                                                </span>
                                            ) : (
                                                <span style={{ display: 'inline-flex', alignItems: 'center', gap: 4, color: 'var(--mantine-color-teal-7)', fontWeight: 700 }}>
                                                    <IconCheck size={14} /> {t('common.stable')}
                                                </span>
                                            )} {t('dam.stats.reservoirLevels')}
                                        </Text>
                                    </Card>
                                </SimpleGrid>
 
                                <Card withBorder radius="md" p="md">
                                    <Title order={4} mb="xs">{t('dam.registry.title')}</Title>
                                    <ScrollArea h={450}>
                                        <Table highlightOnHover verticalSpacing="xs" withRowBorders={false} withTableBorder={false}>
                                            <Table.Thead style={{ backgroundColor: 'var(--mantine-color-gray-0)' }}>
                                                <Table.Tr>
                                                    <Table.Th style={{ fontSize: 11 }}>{t('common.table.code')}</Table.Th>
                                                    <Table.Th style={{ fontSize: 11 }}>{t('dam.registry.damName')}</Table.Th>
                                                    <Table.Th style={{ fontSize: 11 }}>{t('common.table.basin')}</Table.Th>
                                                    <Table.Th style={{ fontSize: 11 }}>{t('dam.registry.storage')}</Table.Th>
                                                </Table.Tr>
                                            </Table.Thead>
                                            <Table.Tbody>
                                                {mappedStations.map((station) => (
                                                    <Table.Tr
                                                        key={station.id}
                                                        style={{ cursor: 'pointer' }}
                                                        onClick={() => setInfoStationId(station.id)}
                                                    >
                                                        <Table.Td>
                                                            <Anchor component="button" fz="xs" fw={700} ff="monospace" onClick={(e) => { e.stopPropagation(); setInfoStationId(station.id); }}>
                                                                {station.code}
                                                            </Anchor>
                                                        </Table.Td>
                                                        <Table.Td>
                                                            <Anchor component="button" fz="xs" fw={600} onClick={(e) => { e.stopPropagation(); setInfoStationId(station.id); }}>
                                                                {station.name}
                                                            </Anchor>
                                                        </Table.Td>
                                                        <Table.Td>
                                                            <Badge size="xs" color={station.river_basin === 'Maputo' ? 'orange' : 'teal'}>
                                                                {station.river_basin}
                                                            </Badge>
                                                        </Table.Td>
                                                        <Table.Td style={{ width: 180 }}>
                                                            {station.value !== null ? (
                                                                <>
                                                                    <Group justify="space-between" mb={4}>
                                                                        <Text size="xs" c={station.value < 40 ? 'red.7' : 'teal.7'} fw={800}>
                                                                            {station.value.toFixed(0)}%
                                                                        </Text>
                                                                        <Text size="10px" c="dimmed" fw={600}>
                                                                            {(100 - Math.min(100, station.value)).toFixed(0)}{t('dam.registry.emptyPct')}
                                                                        </Text>
                                                                    </Group>
                                                                    <Progress.Root size="sm">
                                                                        <Progress.Section
                                                                            value={Math.min(100, station.value)}
                                                                            color={station.value < 40 ? 'red' : 'teal'}
                                                                            aria-label="Active storage"
                                                                        />
                                                                        {station.value < 100 && (
                                                                            <Progress.Section
                                                                                value={100 - Math.min(100, station.value)}
                                                                                color="rgba(0,0,0,0.06)"
                                                                                aria-label="Empty storage"
                                                                            />
                                                                        )}
                                                                    </Progress.Root>
                                                                </>
                                                            ) : (
                                                                <Text size="xs" c="dimmed" fw={700}>{t('common.noData')}</Text>
                                                            )}
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
                                legendTitle={t('dam.legend.title')}
                                legends={legends}
                                height={560}
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
                                        <Text size="xs" fw={700} c="dimmed">Latest Verified Storage</Text>
                                        <Title order={2} c="blue.7">{targetStation.value !== null ? `${targetStation.value} %` : 'N/A'}</Title>
                                    </Stack>
                                </Flex>
                            </Card>
                        )}

                        <Grid gutter="lg">
                            {/* Left panel: Detailed Charts */}
                            <Grid.Col span={{ base: 12, lg: 8 }}>
                                <SimpleGrid cols={{ base: 1, md: 1 }} spacing="lg">
                                    <Card withBorder radius="md" p="md">
                                        <Title order={4} mb="xs">{t('dam.chart.drawdownTitle')}</Title>
                                        <Text size="xs" c="dimmed" mb="md">
                                            {t('dam.chart.drawdownDesc')}
                                        </Text>
                                        {historicalLoading ? (
                                            <Flex justify="center" align="center" h={240}><IconRefresh className="animate-spin" /></Flex>
                                        ) : (
                                            <DamDrawdownChart data={filteredChartHistory} />
                                        )}
                                    </Card>
                                </SimpleGrid>
                            </Grid.Col>

                            {/* Right panel: Static Map Close-up */}
                            <Grid.Col span={{ base: 12, lg: 4 }}>
                                <Card withBorder radius="md" p="0" style={{ overflow: 'hidden', height: 320 }}>
                                    <GisMap
                                        stations={mappedStations.filter(s => s.id === selectedStationId)}
                                        legendTitle={t('dam.legend.title')}
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
                    <Title order={4} mb="md">{t('dam.history.title')}</Title>
                    <Grid gutter="sm" mb="sm">
                        <Grid.Col span={{ base: 12, md: 4 }}>
                            <TextInput
                                label={t('common.history.searchLabel')}
                                placeholder={t('common.history.searchPlaceholder')}
                                leftSection={<IconSearch size={16} />}
                                value={historySearch}
                                onChange={(event) => setHistorySearch(event.currentTarget.value)}
                            />
                        </Grid.Col>
                        <Grid.Col span={{ base: 12, md: 3 }}>
                            <Select
                                label={t('common.history.sortLabel')}
                                data={historySortOptions}
                                value={historySort}
                                onChange={(value) => setHistorySort((value as HistorySortOption) || 'date_desc')}
                            />
                        </Grid.Col>
                        <Grid.Col span={{ base: 12, md: 3 }}>
                            <Select
                                label={t('common.history.rangeLabel')}
                                data={historyRangeOptions}
                                value={historyRange}
                                onChange={(value) => setHistoryRange((value as HistoryRangeOption) || 'all')}
                            />
                        </Grid.Col>
                        {historyRange === 'custom' && (
                            <>
                                <Grid.Col span={{ base: 12, md: 1 }}>
                                    <TextInput
                                        label={t('common.history.fromLabel')}
                                        type="date"
                                        value={historyFrom}
                                        onChange={(event) => setHistoryFrom(event.currentTarget.value)}
                                    />
                                </Grid.Col>
                                <Grid.Col span={{ base: 12, md: 1 }}>
                                    <TextInput
                                        label={t('common.history.toLabel')}
                                        type="date"
                                        value={historyTo}
                                        onChange={(event) => setHistoryTo(event.currentTarget.value)}
                                    />
                                </Grid.Col>
                            </>
                        )}
                    </Grid>
                    <Text size="xs" c="dimmed" mb="sm">{t('common.history.records', { count: filteredHistoryRows.length })}</Text>
                    <ScrollArea h={400}>
                        <Table highlightOnHover verticalSpacing="xs" withRowBorders={false} withTableBorder={false}>
                            <Table.Thead style={{ backgroundColor: 'var(--mantine-color-gray-0)' }}>
                                <Table.Tr>
                                    <Table.Th style={{ fontSize: 11 }}>{t('dam.registry.reservoirHeader')}</Table.Th>
                                    <Table.Th style={{ fontSize: 11 }}>{t('dam.history.storageHeader')}</Table.Th>
                                    <Table.Th style={{ fontSize: 11 }}>{t('common.table.readingDate')}</Table.Th>
                                    <Table.Th style={{ fontSize: 11 }}>{t('common.table.status')}</Table.Th>
                                    <Table.Th style={{ fontSize: 11 }}>{t('common.table.loggedBy')}</Table.Th>
                                    <Table.Th style={{ fontSize: 11 }}>{t('common.table.reviewedBy')}</Table.Th>
                                    <Table.Th style={{ fontSize: 11 }}>{t('common.table.reviewNotes')}</Table.Th>
                                    <Table.Th style={{ fontSize: 11, textAlign: 'right' }}>{t('common.table.actions')}</Table.Th>
                                </Table.Tr>
                            </Table.Thead>
                            <Table.Tbody>
                                {filteredHistoryRows.length > 0 ? (
                                    filteredHistoryRows.map((row) => {
                                        const isOwner = row.submitted_by === auth.user?.display_name;
                                        const canEdit = isOwner || canManage;

                                        return (
                                            <Table.Tr key={row.id}>
                                                <Table.Td>
                                                    <Text size="xs" fw={700}>{row.station_code}</Text>
                                                </Table.Td>
                                                <Table.Td fw={700}>{row.value} {row.unit}</Table.Td>
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
                                                    <Text size="10px" c="dimmed" style={{ maxWidth: '150px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }} title={row.review_notes || ''}>
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
                                        <Table.Td colSpan={8} style={{ textAlign: 'center', padding: 16 }}>
                                            <Text size="xs" c="dimmed">{t('dam.empty.measurements')}</Text>
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
                        <Title order={4} mb="md" c="amber.8">{t('dam.queue.title')} ({pendingQueue.length})</Title>
                        <Table highlightOnHover verticalSpacing="xs" withRowBorders={false} withTableBorder={false}>
                            <Table.Thead style={{ backgroundColor: 'var(--mantine-color-gray-0)' }}>
                                <Table.Tr>
                                    <Table.Th style={{ fontSize: 11 }}>{t('dam.registry.reservoirHeader')}</Table.Th>
                                    <Table.Th style={{ fontSize: 11 }}>{t('dam.queue.storagePctHeader')}</Table.Th>
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
                                        <Table.Td fw={800} c="blue.7">{row.value} {row.unit}</Table.Td>
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

            {/* Modal: Log Storage */}
            <Modal opened={createOpened} onClose={closeCreate} title={<Text fw={700} size="md">{t('dam.modal.log')}</Text>} centered radius="md">
                <form onSubmit={handleCreateSubmit}>
                    <Stack gap="md">
                        <Select
                            label={t('dam.modal.selectStation')}
                            placeholder={t('dam.modal.stationPlaceholder')}
                            data={stations.map((s) => ({ value: s.id, label: `${s.code} — ${s.name}` }))}
                            value={createForm.data.station_id}
                            onChange={(val) => createForm.setData('station_id', val || '')}
                            error={createForm.errors.station_id}
                            required
                            searchable
                        />

                        <NumberInput
                            label={t('dam.modal.valueLabel')}
                            placeholder={t('dam.modal.valuePlaceholder')}
                            value={createForm.data.value}
                            onChange={(val) => createForm.setData('value', val === '' ? '' : Number(val))}
                            error={createForm.errors.value}
                            required
                            min={0}
                            max={120}
                            decimalScale={2}
                        />

                        <TextInput
                            label={t('common.modal.readingDateLabel')}
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

            {/* Modal: Edit Storage */}
            <Modal opened={editOpened} onClose={closeEdit} title={<Text fw={700} size="md">{t('dam.modal.edit')}</Text>} centered radius="md">
                {selectedMeasurement && (
                    <form onSubmit={handleEditSubmit}>
                        <Stack gap="md">
                            <Stack gap={2} mb="xs">
                                <Text size="xs" fw={700} c="dimmed">{t('common.modal.gaugingStation')}</Text>
                                <Text size="sm" fw={700}>{selectedMeasurement.station_code} — {selectedMeasurement.station_name}</Text>
                            </Stack>

                            <NumberInput
                                label={t('dam.modal.valueLabel')}
                                value={editForm.data.value}
                                onChange={(val) => editForm.setData('value', val === '' ? '' : Number(val))}
                                error={editForm.errors.value}
                                required
                                min={0}
                                max={120}
                                decimalScale={2}
                            />

                            <TextInput
                                label={t('common.modal.readingDateLabel')}
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
            <Modal opened={rejectOpened} onClose={closeReject} title={<Text fw={700} size="md" c="red.7">{t('dam.modal.reject')}</Text>} centered radius="md">
                {selectedMeasurement && (
                    <form onSubmit={handleRejectSubmit}>
                        <Stack gap="md">
                            <Text size="xs" mb="xs">{t('common.modal.rejectHelp')}</Text>

                            <TextInput
                                label={t('common.modal.reviewNotesLabel')}
                                placeholder={t('common.modal.reviewNotesPlaceholder')}
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
                        <IconAlertTriangle size={18} color="var(--mantine-color-orange-7)" />
                        <Title order={5}>{t('dam.stats.criticalReservoirs')} — {criticalStations.length}</Title>
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
                            <Table.Th style={{ fontSize: 11 }}>Storage</Table.Th>
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
                                <Table.Td><Text fz="xs" c="orange.7" fw={700}>{s.value?.toFixed(1)} {s.unit}</Text></Table.Td>
                            </Table.Tr>
                        ))}
                    </Table.Tbody>
                </Table>
            </Modal>

            <MeasurementImportModal type="dam" opened={importOpen} onClose={closeImport} />
        </>
    );
}

DamLevels.layout = (page: React.ReactNode) => <AuthenticatedLayout>{page}</AuthenticatedLayout>;
