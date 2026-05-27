import { useRef, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Badge,
    Button,
    Card,
    Center,
    Container,
    Divider,
    Grid,
    Group,
    Loader,
    Modal,
    Stack,
    Table,
    Text,
    Title,
} from '@mantine/core';
import { notifications } from '@mantine/notifications';
import { IconAlertTriangle, IconCheck, IconEdit, IconFileText, IconTrash, IconUpload } from '@tabler/icons-react';
import { useTranslation } from 'react-i18next';
import StationMap from '@/Components/Dashboard/StationMap';
import StationCharts, { type StationChartsHandle } from '@/Components/Stations/StationCharts';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { generateStationReport } from '@/lib/reportGenerator';
import { type PageProps } from '@/types';
import { type StationFormData } from './partials/StationFormModal';
import StationFormModal from './partials/StationFormModal';

interface StationDetails {
    id: string;
    code: string;
    name: string;
    country?: string | null;
    category: string;
    water_source: string;
    water_body_type: string;
    summary?: string | null;
    owner_org?: string | null;
    river_basin?: string | null;
    telemetry_system?: string | null;
    gauge_code?: string | null;
    is_active: boolean;
    is_real_time: boolean;
    latitude?: number | null;
    longitude?: number | null;
}

interface Capability {
    measurement_type: string;
    is_primary: boolean;
    installed_at?: string | null;
    notes?: string | null;
}

interface OperationalStatus {
    status: string;
    reason?: string | null;
    started_at?: string | null;
    expected_resolution_at?: string | null;
    resolved_at?: string | null;
    reported_by?: string | null;
}

interface ManagementArea {
    code: string;
    name: string;
    basin?: string | null;
    country?: string | null;
}

interface EflowKeyPoint {
    code: string;
    name: string;
    river?: string | null;
    country?: string | null;
    note?: string | null;
}

interface MeasurementRow {
    measurement_type: string;
    value: number;
    unit: string;
    status: string;
    date: string;
}

interface StationsShowProps {
    station: StationDetails;
    recentMeasurements: MeasurementRow[];
    capabilities: Capability[];
    operationalStatuses: OperationalStatus[];
    managementAreas: ManagementArea[];
    eflowKeyPoint: EflowKeyPoint | null;
    canManage: boolean;
    isAdmin: boolean;
}

const OP_STATUS_COLORS: Record<string, string> = {
    operational: 'green',
    degraded: 'yellow',
    offline: 'red',
};

function renderDetail(label: string, value: string | number | null | undefined) {
    return (
        <Group justify="space-between" align="flex-start" wrap="nowrap">
            <Text c="dimmed" size="sm">
                {label}
            </Text>
            <Text size="sm" ta="right">
                {value ?? '-'}
            </Text>
        </Group>
    );
}

export default function StationsShow({
    station,
    recentMeasurements,
    capabilities,
    operationalStatuses,
    managementAreas,
    eflowKeyPoint,
    canManage,
    isAdmin,
}: StationsShowProps) {
    const { t } = useTranslation('stations');
    const { auth } = usePage<PageProps>().props;
    const [editOpen, setEditOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const [reportLoading, setReportLoading] = useState(false);
    const chartsRef = useRef<StationChartsHandle>(null);

    const stationFormData: StationFormData = { ...station };

    const handleGenerateReport = async () => {
        if (!auth.user) return;
        setReportLoading(true);
        try {
            // Give the browser a tick to paint the loading overlay before heavy work
            await new Promise((r) => setTimeout(r, 50));
            const chartImages = await (chartsRef.current?.captureCharts() ?? []);
            await generateStationReport(
                {
                    code: station.code,
                    name: station.name,
                    country: station.country,
                    category: station.category,
                    water_source: station.water_source,
                },
                {
                    display_name: auth.user.display_name,
                    email: auth.user.email,
                    role: auth.user.role,
                },
                chartImages,
                'station-report-content',
            );
        } catch {
            notifications.show({
                color: 'red',
                icon: <IconAlertTriangle size={16} />,
                message: 'Failed to generate report.',
                autoClose: false,
                withCloseButton: true,
            });
        } finally {
            setReportLoading(false);
        }
    };

    const handleDelete = () => {
        setDeleting(true);
        router.delete(route('stations.destroy', station.id), {
            onSuccess: () => {
                notifications.show({
                    color: 'green',
                    icon: <IconCheck size={16} />,
                    message: `${station.name} deleted.`,
                    autoClose: 5000,
                });
            },
            onError: () => {
                setDeleting(false);
                setDeleteOpen(false);
                notifications.show({
                    color: 'red',
                    icon: <IconAlertTriangle size={16} />,
                    message: 'Failed to delete station.',
                    autoClose: false,
                    withCloseButton: true,
                });
            },
        });
    };

    const currentOpStatus = operationalStatuses[0] ?? null;

    return (
        <>
            <Head title={`${station.name} - ${t('title')}`} />

            <Container size="xl" py="xl">
                <Stack gap="md" id="station-report-content">
                    <Group justify="space-between" align="flex-start">
                        <div>
                            <Text size="sm" c="dimmed">
                                {station.code}
                            </Text>
                            <Title order={1}>{station.name}</Title>
                            <Text c="dimmed">{t('detail.subtitle')}</Text>
                        </div>
                        <Group gap="xs" data-report-exclude="true">
                            <Badge color={station.is_active ? 'green' : 'gray'} variant="light">
                                {station.is_active ? t('status.active') : t('status.inactive')}
                            </Badge>
                            <Badge color={station.is_real_time ? 'teal' : 'blue'} variant="light">
                                {station.is_real_time ? t('detail.realtime') : t('detail.notRealtime')}
                            </Badge>
                            <Button
                                size="xs"
                                variant="light"
                                leftSection={<IconFileText size={14} />}
                                loading={reportLoading}
                                onClick={handleGenerateReport}
                            >
                                {reportLoading ? t('report.generating') : t('report.button')}
                            </Button>
                            <a href={route('stations.export-csv', station.id)}>
                                <Button
                                    size="xs"
                                    variant="default"
                                    leftSection={<IconUpload size={14} />}
                                >
                                    {t('export.button')}
                                </Button>
                            </a>
                            {canManage && (
                                <Button
                                    size="xs"
                                    variant="light"
                                    leftSection={<IconEdit size={14} />}
                                    onClick={() => setEditOpen(true)}
                                >
                                    {t('actions.edit')}
                                </Button>
                            )}
                            {isAdmin && (
                                <Button
                                    size="xs"
                                    color="red"
                                    variant="light"
                                    leftSection={<IconTrash size={14} />}
                                    onClick={() => setDeleteOpen(true)}
                                >
                                    {t('actions.delete')}
                                </Button>
                            )}
                        </Group>
                    </Group>

                    <Text size="sm">
                        <Link href={route('stations.index')}>{t('actions.backToList')}</Link>
                    </Text>

                    <Grid gutter="md">
                        <Grid.Col span={{ base: 12, lg: 5 }}>
                            <Card withBorder radius="md" p="md">
                                <Stack gap="xs">
                                    {renderDetail(t('detail.country'), station.country)}
                                    <Divider />
                                    {renderDetail(t('detail.category'), station.category)}
                                    <Divider />
                                    {renderDetail(t('detail.waterSource'), station.water_source)}
                                    <Divider />
                                    {renderDetail(t('detail.waterBodyType'), station.water_body_type)}
                                    <Divider />
                                    {renderDetail(t('detail.riverBasin'), station.river_basin)}
                                    <Divider />
                                    {renderDetail(t('detail.ownerOrg'), station.owner_org)}
                                    <Divider />
                                    {renderDetail(t('detail.telemetrySystem'), station.telemetry_system)}
                                    <Divider />
                                    {renderDetail(t('detail.gaugeCode'), station.gauge_code)}
                                    <Divider />
                                    {renderDetail(t('detail.latitude'), station.latitude?.toFixed(6))}
                                    <Divider />
                                    {renderDetail(t('detail.longitude'), station.longitude?.toFixed(6))}
                                    {station.summary && (
                                        <>
                                            <Divider />
                                            <Text size="sm">
                                                {station.summary.replace(/\s*\[Inferred Metadata[^\]]*\]/, '').trim()}
                                            </Text>
                                            {station.summary.includes('Inferred') && (
                                                <Badge color="yellow" variant="light" size="sm">
                                                    {t('detail.inferredMetadata')}
                                                </Badge>
                                            )}
                                        </>
                                    )}
                                </Stack>
                            </Card>
                        </Grid.Col>

                        <Grid.Col span={{ base: 12, lg: 7 }}>
                            <Card withBorder radius="md" p="md">
                                <Stack gap="sm">
                                    <Title order={4}>{t('map.title')}</Title>
                                    <StationMap
                                        latitude={station.latitude}
                                        longitude={station.longitude}
                                        label={station.name}
                                        code={station.code}
                                        status={station.is_active ? 'active' : 'inactive'}
                                        summary={station.summary}
                                        noCoordinatesLabel={t('map.noCoordinates')}
                                    />
                                </Stack>
                            </Card>
                        </Grid.Col>
                    </Grid>

                    {/* Capabilities */}
                    <Card withBorder radius="md" p="md">
                        <Stack gap="sm">
                            <Title order={4}>{t('capabilities.title')}</Title>
                            {capabilities.length === 0 ? (
                                <Text c="dimmed" size="sm">
                                    {t('capabilities.empty')}
                                </Text>
                            ) : (
                                <Table horizontalSpacing="md" verticalSpacing="xs" withTableBorder>
                                    <Table.Thead>
                                        <Table.Tr>
                                            <Table.Th>{t('capabilities.type')}</Table.Th>
                                            <Table.Th>{t('capabilities.isPrimary')}</Table.Th>
                                            <Table.Th>{t('capabilities.installedAt')}</Table.Th>
                                            <Table.Th>{t('capabilities.notes')}</Table.Th>
                                        </Table.Tr>
                                    </Table.Thead>
                                    <Table.Tbody>
                                        {capabilities.map((cap) => (
                                            <Table.Tr key={cap.measurement_type}>
                                                <Table.Td>
                                                    <Text size="sm" ff="monospace">
                                                        {cap.measurement_type}
                                                    </Text>
                                                </Table.Td>
                                                <Table.Td>
                                                    {cap.is_primary && (
                                                        <Badge size="sm" color="blue" variant="light">
                                                            Primary
                                                        </Badge>
                                                    )}
                                                </Table.Td>
                                                <Table.Td>
                                                    <Text size="sm">{cap.installed_at ?? '—'}</Text>
                                                </Table.Td>
                                                <Table.Td>
                                                    <Text size="sm">{cap.notes ?? '—'}</Text>
                                                </Table.Td>
                                            </Table.Tr>
                                        ))}
                                    </Table.Tbody>
                                </Table>
                            )}
                        </Stack>
                    </Card>

                    {/* Operational status */}
                    <Card withBorder radius="md" p="md">
                        <Stack gap="sm">
                            <Group justify="space-between">
                                <Title order={4}>{t('operationalStatus.title')}</Title>
                                {currentOpStatus && (
                                    <Badge
                                        color={OP_STATUS_COLORS[currentOpStatus.status] ?? 'gray'}
                                        variant="filled"
                                    >
                                        {t(
                                            `operationalStatus.statuses.${currentOpStatus.status}`,
                                            currentOpStatus.status,
                                        )}
                                    </Badge>
                                )}
                            </Group>
                            {operationalStatuses.length === 0 ? (
                                <Text c="dimmed" size="sm">
                                    {t('operationalStatus.empty')}
                                </Text>
                            ) : (
                                <Table horizontalSpacing="md" verticalSpacing="xs" withTableBorder>
                                    <Table.Thead>
                                        <Table.Tr>
                                            <Table.Th>{t('operationalStatus.history')}</Table.Th>
                                            <Table.Th>{t('operationalStatus.reason')}</Table.Th>
                                            <Table.Th>{t('operationalStatus.startedAt')}</Table.Th>
                                            <Table.Th>{t('operationalStatus.resolvedAt')}</Table.Th>
                                            <Table.Th>{t('operationalStatus.reportedBy')}</Table.Th>
                                        </Table.Tr>
                                    </Table.Thead>
                                    <Table.Tbody>
                                        {operationalStatuses.map((os, i) => (
                                            <Table.Tr key={i}>
                                                <Table.Td>
                                                    <Badge
                                                        size="sm"
                                                        color={OP_STATUS_COLORS[os.status] ?? 'gray'}
                                                        variant="light"
                                                    >
                                                        {t(
                                                            `operationalStatus.statuses.${os.status}`,
                                                            os.status,
                                                        )}
                                                    </Badge>
                                                </Table.Td>
                                                <Table.Td>
                                                    <Text size="sm">{os.reason ?? '—'}</Text>
                                                </Table.Td>
                                                <Table.Td>
                                                    <Text size="sm">{os.started_at ?? '—'}</Text>
                                                </Table.Td>
                                                <Table.Td>
                                                    <Text size="sm">{os.resolved_at ?? '—'}</Text>
                                                </Table.Td>
                                                <Table.Td>
                                                    <Text size="sm">{os.reported_by ?? '—'}</Text>
                                                </Table.Td>
                                            </Table.Tr>
                                        ))}
                                    </Table.Tbody>
                                </Table>
                            )}
                        </Stack>
                    </Card>

                    {/* Management areas */}
                    <Card withBorder radius="md" p="md">
                        <Stack gap="sm">
                            <Title order={4}>{t('managementAreas.title')}</Title>
                            {managementAreas.length === 0 ? (
                                <Text c="dimmed" size="sm">
                                    {t('managementAreas.empty')}
                                </Text>
                            ) : (
                                <Group gap="xs">
                                    {managementAreas.map((area) => (
                                        <Badge
                                            key={area.code}
                                            variant="outline"
                                            color="indigo"
                                            size="md"
                                        >
                                            {area.code} — {area.name}
                                            {area.country ? ` (${area.country})` : ''}
                                        </Badge>
                                    ))}
                                </Group>
                            )}
                        </Stack>
                    </Card>

                    {/* IIMA compliance */}
                    <Card withBorder radius="md" p="md">
                        <Stack gap="sm">
                            <Title order={4}>{t('iima.title')}</Title>
                            {eflowKeyPoint ? (
                                <Stack gap="xs">
                                    {renderDetail(t('iima.keyPoint'), `${eflowKeyPoint.code} — ${eflowKeyPoint.name}`)}
                                    <Divider />
                                    {renderDetail(t('iima.river'), eflowKeyPoint.river)}
                                    {eflowKeyPoint.note && (
                                        <>
                                            <Divider />
                                            {renderDetail(t('iima.note'), eflowKeyPoint.note)}
                                        </>
                                    )}
                                </Stack>
                            ) : (
                                <Text c="dimmed" size="sm">
                                    {t('iima.notCompliance')}
                                </Text>
                            )}
                        </Stack>
                    </Card>

                    {/* Recent measurements */}
                    <Card withBorder radius="md" p="md" data-report-exclude="true">
                        <Stack gap="sm">
                            <Title order={4}>{t('measurements.title')}</Title>
                            {recentMeasurements.length === 0 ? (
                                <Text c="dimmed">{t('measurements.empty')}</Text>
                            ) : (
                                <Table horizontalSpacing="md" verticalSpacing="xs" withTableBorder>
                                    <Table.Thead>
                                        <Table.Tr>
                                            <Table.Th>{t('measurements.columns.type')}</Table.Th>
                                            <Table.Th>{t('measurements.columns.value')}</Table.Th>
                                            <Table.Th>{t('measurements.columns.unit')}</Table.Th>
                                            <Table.Th>{t('measurements.columns.status')}</Table.Th>
                                            <Table.Th>{t('measurements.columns.date')}</Table.Th>
                                        </Table.Tr>
                                    </Table.Thead>
                                    <Table.Tbody>
                                        {recentMeasurements.map((measurement, index) => (
                                            <Table.Tr key={`${measurement.date}-${index}`}>
                                                <Table.Td>{measurement.measurement_type}</Table.Td>
                                                <Table.Td>{measurement.value}</Table.Td>
                                                <Table.Td>{measurement.unit}</Table.Td>
                                                <Table.Td>{measurement.status}</Table.Td>
                                                <Table.Td>{measurement.date}</Table.Td>
                                            </Table.Tr>
                                        ))}
                                    </Table.Tbody>
                                </Table>
                            )}
                        </Stack>
                    </Card>

                    {/* Historical charts */}
                    <StationCharts
                        ref={chartsRef}
                        stationId={station.id}
                        capabilities={capabilities}
                    />
                </Stack>
            </Container>

            {/* Edit modal */}
            {canManage && (
                <StationFormModal
                    opened={editOpen}
                    onClose={() => setEditOpen(false)}
                    station={stationFormData}
                />
            )}

            {/* Delete confirmation */}
            <Modal
                opened={deleteOpen}
                onClose={() => setDeleteOpen(false)}
                title={<Title order={4}>{t('actions.delete')}</Title>}
                size="sm"
            >
                <Stack gap="md">
                    <Text size="sm">{t('actions.deleteConfirm')}</Text>
                    <Group justify="flex-end">
                        <Button variant="subtle" onClick={() => setDeleteOpen(false)}>
                            {t('form.cancel')}
                        </Button>
                        <Button color="red" loading={deleting} onClick={handleDelete}>
                            {t('actions.delete')}
                        </Button>
                    </Group>
                </Stack>
            </Modal>

            {/* Report generation loading overlay */}
            <Modal
                opened={reportLoading}
                onClose={() => { /* not closable while generating */ }}
                withCloseButton={false}
                closeOnClickOutside={false}
                closeOnEscape={false}
                centered
                size="sm"
                overlayProps={{ backgroundOpacity: 0.55, blur: 3 }}
            >
                <Center py="md">
                    <Stack align="center" gap="md">
                        <Loader size="lg" />
                        <Text size="sm" c="dimmed">{t('report.generating')}</Text>
                    </Stack>
                </Center>
            </Modal>
        </>
    );
}

StationsShow.layout = (page: React.ReactNode) => <AuthenticatedLayout>{page}</AuthenticatedLayout>;
