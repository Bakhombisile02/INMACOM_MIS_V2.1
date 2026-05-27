import { useMemo, useState } from 'react';
import { Head } from '@inertiajs/react';
import {
    Badge,
    Button,
    Container,
    Grid,
    Group,
    ScrollArea,
    Stack,
    Table,
    Tabs,
    Text,
    Title,
    TextInput,
    Pagination,
} from '@mantine/core';
import { IconPlus, IconSearch, IconChevronUp, IconChevronDown, IconSelector, IconTableImport, IconClipboardList, IconList } from '@tabler/icons-react';
import { useTranslation } from 'react-i18next';
import MultiStationMap, { type StationMapMarker } from '@/Components/Dashboard/MultiStationMap';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ApprovalsTab, { type PendingStationRevisionRow } from '@/Components/Approvals/ApprovalsTab';
import { usePermissions } from '@/lib/permissions';

import StationFormModal from './partials/StationFormModal';
import StationImportModal from './partials/StationImportModal';
import StationInfoDrawer from '@/Components/Dashboard/StationInfoDrawer';

interface StationListRow {
    id: string;
    code: string;
    name: string;
    country?: string | null;
    category: string;
    water_source: string;
    water_body_type: string;
    status: 'active' | 'inactive';
    is_active: boolean;
    is_real_time: boolean;
    latitude?: number | null;
    longitude?: number | null;
    show_url: string;
    summary?: string | null;
    river_basin?: string | null;
    telemetry_system?: string | null;
    gauge_code?: string | null;
    owner_org?: string | null;
}

interface StationsIndexProps {
    stations: StationListRow[];
    canManage: boolean;
    isAdmin: boolean;
    pendingRevisions?: PendingStationRevisionRow[];
}

export default function StationsIndex({ stations, canManage, pendingRevisions = [] }: StationsIndexProps) {
    const { t } = useTranslation('stations');
    const { t: tApprovals } = useTranslation('approvals');
    const { t: tNav } = useTranslation('navigation');
    const permissions = usePermissions();

    // Search query state
    const [searchQuery, setSearchQuery] = useState('');

    // Pagination state
    const [activePage, setActivePage] = useState(1);

    // Sorting state
    const [sortBy, setSortBy] = useState<keyof StationListRow | null>(null);
    const [sortOrder, setSortOrder] = useState<'asc' | 'desc'>('asc');

    // Selected marker state (for drawer)
    const [selectedId, setSelectedId] = useState<string | null>(null);

    // Create modal state
    const [createOpen, setCreateOpen] = useState(false);

    // Import modal state
    const [importOpen, setImportOpen] = useState(false);

    // Search station list by name or code
    const searched = useMemo(() => {
        if (!searchQuery) return stations;
        const q = searchQuery.toLowerCase().trim();
        return stations.filter(
            (s) =>
                s.name.toLowerCase().includes(q) ||
                s.code.toLowerCase().includes(q),
        );
    }, [stations, searchQuery]);

    // Sort searched station list
    const sorted = useMemo(() => {
        if (!sortBy) return searched;
        const clone = [...searched];
        clone.sort((a, b) => {
            const valA = a[sortBy] ?? '';
            const valB = b[sortBy] ?? '';

            if (typeof valA === 'string' && typeof valB === 'string') {
                return sortOrder === 'asc'
                    ? valA.localeCompare(valB)
                    : valB.localeCompare(valA);
            }

            if (valA < valB) return sortOrder === 'asc' ? -1 : 1;
            if (valA > valB) return sortOrder === 'asc' ? 1 : -1;
            return 0;
        });
        return clone;
    }, [searched, sortBy, sortOrder]);

    // 4. Paginate sorted list (10 results per page)
    const itemsPerPage = 10;
    const totalPages = Math.ceil(sorted.length / itemsPerPage);
    const paginated = useMemo(() => {
        const start = (activePage - 1) * itemsPerPage;
        return sorted.slice(start, start + itemsPerPage);
    }, [sorted, activePage]);

    // Handle sort column click
    const handleSort = (field: keyof StationListRow) => {
        if (sortBy === field) {
            setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc');
        } else {
            setSortBy(field);
            setSortOrder('asc');
        }
        setActivePage(1); // Reset to first page when sort changes
    };

    // Map marker data from filtered and searched list
    const mapMarkers: StationMapMarker[] = useMemo(
        () =>
            searched.map((s) => ({
                id: s.id,
                code: s.code,
                name: s.name,
                latitude: s.latitude ?? null,
                longitude: s.longitude ?? null,
                status: s.status,
                is_active: s.is_active,
                is_real_time: s.is_real_time,
                country: s.country,
                category: s.category,
                water_source: s.water_source,
                water_body_type: s.water_body_type,
                summary: s.summary,
                show_url: s.show_url,
                river_basin: s.river_basin,
                telemetry_system: s.telemetry_system,
                gauge_code: s.gauge_code,
                owner_org: s.owner_org,
            })),
        [searched],
    );

    const renderSortIcon = (field: keyof StationListRow) => {
        if (sortBy !== field) {
            return <IconSelector size={14} style={{ opacity: 0.4 }} />;
        }
        return sortOrder === 'asc' ? <IconChevronUp size={14} /> : <IconChevronDown size={14} />;
    };

    return (
        <>
            <Head title={tNav('stations')} />

            <Container size="xl" py="xl">
                <Group justify="space-between" align="flex-start" mb="lg">
                    <div>
                        <Title order={1}>{t('title')}</Title>
                        <Text c="dimmed">{t('subtitle')}</Text>
                    </div>
                    {canManage && (
                        <Group gap="xs">
                            <Button variant="default" leftSection={<IconTableImport size={16} />} onClick={() => setImportOpen(true)}>
                                Import stations
                            </Button>
                            <Button leftSection={<IconPlus size={16} />} onClick={() => setCreateOpen(true)}>
                                {t('actions.create')}
                            </Button>
                        </Group>
                    )}
                </Group>

                <Tabs defaultValue="overview" keepMounted={false} variant="outline" radius="md">
                    <Tabs.List mb="xl">
                        <Tabs.Tab value="overview" leftSection={<IconList size={18} />}>
                            {tApprovals('tabs.overview')}
                        </Tabs.Tab>
                        {permissions.canApprove && (
                            <Tabs.Tab
                                value="approvals"
                                leftSection={<IconClipboardList size={18} />}
                                rightSection={
                                    pendingRevisions.length > 0 ? (
                                        <Badge size="xs" color="red" variant="filled" circle>
                                            {pendingRevisions.length}
                                        </Badge>
                                    ) : undefined
                                }
                            >
                                {tApprovals('tabs.approvals')}
                            </Tabs.Tab>
                        )}
                    </Tabs.List>

                    <Tabs.Panel value="overview">
                <Grid gutter="md">
                    {/* Left column — map */}
                    <Grid.Col span={{ base: 12, lg: 6 }}>
                        <div style={{ position: 'sticky', top: 80 }}>
                            <MultiStationMap
                                stations={mapMarkers}
                                selectedId={selectedId}
                                onMarkerClick={(id) => setSelectedId(id)}
                                onDeselect={() => setSelectedId(null)}
                                canManage={canManage}
                                height={540}
                            />
                        </div>
                    </Grid.Col>

                    {/* Right column — search + table */}
                    <Grid.Col span={{ base: 12, lg: 6 }}>
                        <Stack gap="md">
                            {/* Search textbox */}
                            <TextInput
                                placeholder={t('search')}
                                value={searchQuery}
                                onChange={(e) => {
                                    setSearchQuery(e.currentTarget.value);
                                    setActivePage(1);
                                }}
                                leftSection={<IconSearch size={16} />}
                            />

                            {/* Station table */}
                            <ScrollArea>
                                <Table
                                    horizontalSpacing="sm"
                                    verticalSpacing="xs"
                                    withTableBorder={false}
                                    withRowBorders
                                    highlightOnHover
                                    style={{ cursor: 'pointer' }}
                                >
                                    <Table.Thead>
                                        <Table.Tr>
                                            <Table.Th onClick={() => handleSort('code')} style={{ userSelect: 'none' }}>
                                                <Group gap={4}>
                                                    <Text size="xs" fw={700}>{t('columns.code')}</Text>
                                                    {renderSortIcon('code')}
                                                </Group>
                                            </Table.Th>
                                            <Table.Th onClick={() => handleSort('name')} style={{ userSelect: 'none' }}>
                                                <Group gap={4}>
                                                    <Text size="xs" fw={700}>{t('columns.name')}</Text>
                                                    {renderSortIcon('name')}
                                                </Group>
                                            </Table.Th>
                                            <Table.Th onClick={() => handleSort('country')} style={{ userSelect: 'none' }}>
                                                <Group gap={4}>
                                                    <Text size="xs" fw={700}>{t('columns.country')}</Text>
                                                    {renderSortIcon('country')}
                                                </Group>
                                            </Table.Th>
                                            <Table.Th onClick={() => handleSort('status')} style={{ userSelect: 'none' }}>
                                                <Group gap={4}>
                                                    <Text size="xs" fw={700}>{t('columns.status')}</Text>
                                                    {renderSortIcon('status')}
                                                </Group>
                                            </Table.Th>
                                            <Table.Th onClick={() => handleSort('is_real_time')} style={{ userSelect: 'none' }}>
                                                <Group gap={4}>
                                                    <Text size="xs" fw={700}>{t('columns.realtime')}</Text>
                                                    {renderSortIcon('is_real_time')}
                                                </Group>
                                            </Table.Th>
                                        </Table.Tr>
                                    </Table.Thead>
                                    <Table.Tbody>
                                        {paginated.length === 0 ? (
                                            <Table.Tr>
                                                <Table.Td colSpan={5}>
                                                    <Text ta="center" c="dimmed" size="sm" py="md">
                                                        {t('empty')}
                                                    </Text>
                                                </Table.Td>
                                            </Table.Tr>
                                        ) : (
                                            paginated.map((station) => (
                                                <Table.Tr
                                                    key={station.id}
                                                    onClick={() => setSelectedId(station.id)}
                                                    style={{
                                                        cursor: 'pointer',
                                                        backgroundColor:
                                                            station.id === selectedId
                                                                ? 'var(--mantine-color-blue-0)'
                                                                : undefined,
                                                    }}
                                                >
                                                    <Table.Td>
                                                        <Text size="sm" ff="monospace">
                                                            {station.code}
                                                        </Text>
                                                    </Table.Td>
                                                    <Table.Td>
                                                        <Text size="sm" fw={500}>
                                                            {station.name}
                                                        </Text>
                                                    </Table.Td>
                                                    <Table.Td>
                                                        <Text size="sm">{station.country ?? '—'}</Text>
                                                    </Table.Td>
                                                    <Table.Td>
                                                        <Badge
                                                            size="sm"
                                                            color={
                                                                station.status === 'active' ? 'green' : 'gray'
                                                            }
                                                            variant="light"
                                                        >
                                                            {t(`status.${station.status}`)}
                                                        </Badge>
                                                    </Table.Td>
                                                    <Table.Td>
                                                        {station.is_real_time && (
                                                            <Badge size="sm" color="teal" variant="light">
                                                                {t('detail.realtime')}
                                                            </Badge>
                                                        )}
                                                    </Table.Td>
                                                </Table.Tr>
                                            ))
                                        )}
                                    </Table.Tbody>
                                </Table>
                            </ScrollArea>

                            {/* Pagination and records details */}
                            {totalPages > 1 && (
                                <Group justify="center" py="xs">
                                    <Pagination
                                        total={totalPages}
                                        value={activePage}
                                        onChange={setActivePage}
                                        size="sm"
                                        withEdges
                                    />
                                </Group>
                            )}

                            <Group justify="space-between">
                                <Text size="xs" c="dimmed">
                                    {sorted.length === stations.length
                                        ? t('showingAll', { count: stations.length }) || `Showing all ${stations.length} stations`
                                        : t('showingFiltered', { filtered: sorted.length, total: stations.length }) || `Showing ${sorted.length} of ${stations.length} stations`}
                                </Text>
                            </Group>
                        </Stack>
                    </Grid.Col>
                </Grid>
                    </Tabs.Panel>

                    {permissions.canApprove && (
                        <Tabs.Panel value="approvals">
                            <ApprovalsTab stationRevisions={pendingRevisions} />
                        </Tabs.Panel>
                    )}
                </Tabs>
            </Container>

            <StationInfoDrawer
                station={mapMarkers.find((s) => s.id === selectedId) ?? null}
                onClose={() => setSelectedId(null)}
            />
            {canManage && (
                <>
                    <StationFormModal opened={createOpen} onClose={() => setCreateOpen(false)} />
                    <StationImportModal opened={importOpen} onClose={() => setImportOpen(false)} />
                </>
            )}
        </>
    );
}

StationsIndex.layout = (page: React.ReactNode) => <AuthenticatedLayout>{page}</AuthenticatedLayout>;
