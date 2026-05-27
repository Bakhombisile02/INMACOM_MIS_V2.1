import { Head, router, useForm } from '@inertiajs/react';
import {
    ActionIcon,
    Badge,
    Button,
    Card,
    Container,
    Flex,
    Grid,
    Group,
    Modal,
    NumberInput,
    Pagination,
    Select,
    SegmentedControl,
    SimpleGrid,
    Stack,
    Switch,
    Table,
    Tabs,
    Text,
    TextInput,
    Title,
    Tooltip,
} from '@mantine/core';
import { useDisclosure } from '@mantine/hooks';
import { notifications } from '@mantine/notifications';
import {
    IconActivity,
    IconAdjustmentsHorizontal,
    IconAlertTriangle,
    IconCheck,
    IconDroplet,
    IconEdit,
    IconPlus,
    IconRefresh,
    IconSearch,
    IconTrash,
} from '@tabler/icons-react';
import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { PageProps } from '@/types';

// Interfaces
interface ComplianceThreshold {
    id: string;
    station_id: string;
    station_code: string;
    station_name: string;
    station_country: string;
    station_basin: string;
    parameter_id: string;
    parameter_code: string;
    parameter_name: string;
    is_priority_pollutant: boolean;
    min_value: number | null;
    max_value: number | null;
    unit: string;
    notes: string;
    is_custom: boolean;
}

interface EflowRequirement {
    id: string;
    river: string;
    key_point: string;
    mean_annual_mm3: number;
    min_flow_m3_s: number;
    source_article: string;
    note: string | null;
    subcatchment_name: string;
    subcatchment_code: string;
    station_code: string | null;
    station_name: string | null;
}

interface WaterAllocation {
    id: string;
    subcatchment_id: string;
    subcatchment_name: string;
    subcatchment_code: string;
    country: string;
    user_category: string;
    user_category_name: string;
    allocation_mm3_a: number;
    effective_from: number;
    note: string | null;
}

interface WaterQualityParameter {
    id: string;
    code: string;
    name: string;
    default_unit: string;
    is_priority_pollutant: boolean;
}

interface Subcatchment {
    id: string;
    code: string;
    name: string;
}

interface UserCategory {
    code: string;
    name: string;
}

interface HazardSettings {
    flood_watch: number;
    flood_moderate: number;
    flood_severe: number;
    drought_watch: number;
    drought_moderate: number;
    drought_severe: number;
    chemical_a: number;
    chemical_b: number;
    chemical_c: number;
    chemical_d: number;
    chemical_x: number;
    coliform_orange: number;
    coliform_red: number;
    fish_kill_orange: number;
    fish_kill_red_count: number;
    fish_kill_red_mass: number;
}

interface ThresholdsProps extends PageProps {
    complianceThresholds: {
        data: ComplianceThreshold[];
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
    };
    eflowRequirements: EflowRequirement[];
    allocations: WaterAllocation[];
    parameters: WaterQualityParameter[];
    subcatchments: Subcatchment[];
    userCategories: UserCategory[];
    hazardSettings: HazardSettings;
    filters: {
        search: string;
        basin: string;
        parameter: string;
        is_priority: boolean;
        is_custom: boolean;
    };
    canManage: boolean;
}

export default function Index({
    complianceThresholds,
    eflowRequirements,
    allocations,
    parameters,
    subcatchments,
    userCategories,
    hazardSettings,
    filters,
    canManage,
}: ThresholdsProps) {
    const { t } = useTranslation('thresholds');

    // WQ View toggle: "group" or "individual"
    const [wqMode, setWqMode] = useState<'group' | 'individual'>('group');

    // Individual Local filters state
    const [search, setSearch] = useState(filters.search || '');
    const [basin, setBasin] = useState(filters.basin || '');
    const [parameter, setParameter] = useState(filters.parameter || '');
    const [isPriority, setIsPriority] = useState(filters.is_priority || false);
    const [isCustom, setIsCustom] = useState(filters.is_custom || false);

    // Edit states WQ
    const [selectedWq, setSelectedWq] = useState<ComplianceThreshold | null>(null);
    const [wqOpened, { open: openWq, close: closeWq }] = useDisclosure(false);

    // Edit states eFlow
    const [selectedEflow, setSelectedEflow] = useState<EflowRequirement | null>(null);
    const [eflowOpened, { open: openEflow, close: closeEflow }] = useDisclosure(false);

    // Allocations Modal states
    const [selectedAllocation, setSelectedAllocation] = useState<WaterAllocation | null>(null);
    const [allocCreateOpened, { open: openAllocCreate, close: closeAllocCreate }] = useDisclosure(false);
    const [allocEditOpened, { open: openAllocEdit, close: closeAllocEdit }] = useDisclosure(false);

    // Inertia forms
    const wqIndividualForm = useForm({
        min_value: '' as number | '',
        max_value: '' as number | '',
    });

    const wqGroupForm = useForm({
        parameter_code: '',
        scope: 'system' as 'system' | 'basin' | 'subcatchment',
        basin: '',
        subcatchment_id: '',
        min_value: '' as number | '',
        max_value: '' as number | '',
    });

    const eflowForm = useForm({
        mean_annual_mm3: '' as number | '',
        min_flow_m3_s: '' as number | '',
    });

    const allocationForm = useForm({
        subcatchment_id: '',
        country: '',
        user_category: '',
        allocation_mm3_a: '' as number | '',
        effective_from: new Date().getFullYear(),
        note: '',
    });

    const hazardForm = useForm({
        flood_watch: hazardSettings.flood_watch,
        flood_moderate: hazardSettings.flood_moderate,
        flood_severe: hazardSettings.flood_severe,
        drought_watch: hazardSettings.drought_watch,
        drought_moderate: hazardSettings.drought_moderate,
        drought_severe: hazardSettings.drought_severe,
        chemical_a: hazardSettings.chemical_a,
        chemical_b: hazardSettings.chemical_b,
        chemical_c: hazardSettings.chemical_c,
        chemical_d: hazardSettings.chemical_d,
        chemical_x: hazardSettings.chemical_x,
        coliform_orange: hazardSettings.coliform_orange,
        coliform_red: hazardSettings.coliform_red,
        fish_kill_orange: hazardSettings.fish_kill_orange,
        fish_kill_red_count: hazardSettings.fish_kill_red_count,
        fish_kill_red_mass: hazardSettings.fish_kill_red_mass,
    });

    // Handle individual WQ filters trigger
    const applyFilters = (updatedFilters?: Record<string, any>) => {
        const payload = {
            search: updatedFilters && 'search' in updatedFilters ? updatedFilters.search : search,
            basin: updatedFilters && 'basin' in updatedFilters ? updatedFilters.basin : basin,
            parameter: updatedFilters && 'parameter' in updatedFilters ? updatedFilters.parameter : parameter,
            is_priority: updatedFilters && 'is_priority' in updatedFilters ? updatedFilters.is_priority : isPriority,
            is_custom: updatedFilters && 'is_custom' in updatedFilters ? updatedFilters.is_custom : isCustom,
        };

        router.get(route('thresholds.index'), payload, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleSearchKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            applyFilters();
        }
    };

    const handlePageChange = (page: number) => {
        router.get(
            route('thresholds.index'),
            {
                search,
                basin,
                parameter,
                is_priority: isPriority,
                is_custom: isCustom,
                page,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    // Open individual WQ edit modal
    const handleEditWq = (row: ComplianceThreshold) => {
        setSelectedWq(row);
        wqIndividualForm.setData({
            min_value: row.min_value !== null ? row.min_value : '',
            max_value: row.max_value !== null ? row.max_value : '',
        });
        openWq();
    };

    // Save individual WQ limit
    const handleSaveWq = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedWq) return;

        wqIndividualForm.patch(route('thresholds.compliance.update', selectedWq.id), {
            onSuccess: () => {
                closeWq();
                notifications.show({
                    title: t('modals.editWqTitle'),
                    message: 'Water quality threshold updated successfully.',
                    color: 'green',
                    icon: <IconCheck size={18} />,
                    autoClose: 5000,
                });
            },
            onError: (err) => {
                notifications.show({
                    title: 'Update Failed',
                    message: Object.values(err)[0] || 'An error occurred.',
                    color: 'red',
                    icon: <IconAlertTriangle size={18} />,
                    autoClose: false,
                    withCloseButton: true,
                });
            },
        });
    };

    // Reset individual WQ limit to default
    const handleResetWq = (id: string) => {
        if (!confirm(t('wqTable.confirmReset'))) return;

        router.post(
            route('thresholds.compliance.reset', id),
            {},
            {
                onSuccess: () => {
                    notifications.show({
                        title: t('wqTable.reset'),
                        message: 'Water quality threshold reset to REIWQ default guidelines.',
                        color: 'green',
                        icon: <IconCheck size={18} />,
                        autoClose: 5000,
                    });
                },
                onError: (err) => {
                    notifications.show({
                        title: 'Reset Failed',
                        message: Object.values(err)[0] || 'An error occurred.',
                        color: 'red',
                        icon: <IconAlertTriangle size={18} />,
                        autoClose: false,
                        withCloseButton: true,
                    });
                },
            }
        );
    };

    // Save WQ Group adjustments in bulk
    const handleSaveWqGroup = (e: React.FormEvent) => {
        e.preventDefault();

        wqGroupForm.post(route('thresholds.compliance.group'), {
            onSuccess: () => {
                wqGroupForm.reset('min_value', 'max_value');
                notifications.show({
                    title: t('wqTable.groupAdjustment'),
                    message: t('wqTable.groupSuccess'),
                    color: 'green',
                    icon: <IconCheck size={18} />,
                    autoClose: 5000,
                });
            },
            onError: (err) => {
                notifications.show({
                    title: 'Group Adjustment Failed',
                    message: Object.values(err)[0] || 'An error occurred.',
                    color: 'red',
                    icon: <IconAlertTriangle size={18} />,
                    autoClose: false,
                    withCloseButton: true,
                });
            },
        });
    };

    // Open eflow edit modal
    const handleEditEflow = (row: EflowRequirement) => {
        setSelectedEflow(row);
        eflowForm.setData({
            mean_annual_mm3: row.mean_annual_mm3,
            min_flow_m3_s: row.min_flow_m3_s,
        });
        openEflow();
    };

    // Save eflow edit
    const handleSaveEflow = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedEflow) return;

        eflowForm.patch(route('thresholds.eflow.update', selectedEflow.id), {
            onSuccess: () => {
                closeEflow();
                notifications.show({
                    title: t('modals.editEflowTitle'),
                    message: 'IIMA ecological flow requirements updated successfully.',
                    color: 'green',
                    icon: <IconCheck size={18} />,
                    autoClose: 5000,
                });
            },
            onError: (err) => {
                notifications.show({
                    title: 'Update Failed',
                    message: Object.values(err)[0] || 'An error occurred.',
                    color: 'red',
                    icon: <IconAlertTriangle size={18} />,
                    autoClose: false,
                    withCloseButton: true,
                });
            },
        });
    };

    // Create a new allocation record
    const handleSaveAllocationCreate = (e: React.FormEvent) => {
        e.preventDefault();

        allocationForm.post(route('thresholds.allocations.store'), {
            onSuccess: () => {
                closeAllocCreate();
                allocationForm.reset();
                notifications.show({
                    title: t('modals.createAllocationTitle'),
                    message: 'IIMA Water allocation created successfully.',
                    color: 'green',
                    icon: <IconCheck size={18} />,
                    autoClose: 5000,
                });
            },
            onError: (err) => {
                notifications.show({
                    title: 'Creation Failed',
                    message: Object.values(err)[0] || 'An error occurred.',
                    color: 'red',
                    icon: <IconAlertTriangle size={18} />,
                    autoClose: false,
                    withCloseButton: true,
                });
            },
        });
    };

    // Open edit modal for an allocation
    const handleEditAllocation = (row: WaterAllocation) => {
        setSelectedAllocation(row);
        allocationForm.setData({
            subcatchment_id: row.subcatchment_id,
            country: row.country,
            user_category: row.user_category,
            allocation_mm3_a: row.allocation_mm3_a,
            effective_from: row.effective_from,
            note: row.note || '',
        });
        openAllocEdit();
    };

    // Save allocation edits
    const handleSaveAllocationEdit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedAllocation) return;

        allocationForm.patch(route('thresholds.allocations.update', selectedAllocation.id), {
            onSuccess: () => {
                closeAllocEdit();
                allocationForm.reset();
                notifications.show({
                    title: t('modals.editAllocationTitle'),
                    message: 'IIMA Water allocation updated successfully.',
                    color: 'green',
                    icon: <IconCheck size={18} />,
                    autoClose: 5000,
                });
            },
            onError: (err) => {
                notifications.show({
                    title: 'Update Failed',
                    message: Object.values(err)[0] || 'An error occurred.',
                    color: 'red',
                    icon: <IconAlertTriangle size={18} />,
                    autoClose: false,
                    withCloseButton: true,
                });
            },
        });
    };

    // Delete an allocation record
    const handleDeleteAllocation = (id: string) => {
        if (!confirm(t('allocationsTab.confirmDelete'))) return;

        router.delete(route('thresholds.allocations.destroy', id), {
            onSuccess: () => {
                notifications.show({
                    title: 'Record Deleted',
                    message: 'Water allocation record removed successfully.',
                    color: 'green',
                    icon: <IconCheck size={18} />,
                    autoClose: 5000,
                });
            },
            onError: (err) => {
                notifications.show({
                    title: 'Deletion Failed',
                    message: Object.values(err)[0] || 'An error occurred.',
                    color: 'red',
                    icon: <IconAlertTriangle size={18} />,
                    autoClose: false,
                    withCloseButton: true,
                });
            },
        });
    };

    // Save hazard configuration CRUD forms
    const handleSaveHazards = (e: React.FormEvent) => {
        e.preventDefault();

        hazardForm.patch(route('thresholds.hazard.update'), {
            onSuccess: () => {
                notifications.show({
                    title: t('hazardTab.saveSettings'),
                    message: 'Hazard indices and pollution categories saved successfully.',
                    color: 'green',
                    icon: <IconCheck size={18} />,
                    autoClose: 5000,
                });
            },
            onError: (err) => {
                notifications.show({
                    title: 'Update Failed',
                    message: Object.values(err)[0] || 'An error occurred.',
                    color: 'red',
                    icon: <IconAlertTriangle size={18} />,
                    autoClose: false,
                    withCloseButton: true,
                });
            },
        });
    };

    return (
        <>
            <Head title={t('title')} />

            <Container size="xl" py="xl">
                <Tabs defaultValue="wq" variant="outline" radius="md">
                    <Tabs.List mb="xl">
                        <Tabs.Tab value="wq" leftSection={<IconDroplet size={18} />}>
                            {t('tabs.waterQuality')}
                        </Tabs.Tab>
                        <Tabs.Tab value="eflow" leftSection={<IconActivity size={18} />}>
                            {t('tabs.iimaEflow')}
                        </Tabs.Tab>
                        <Tabs.Tab value="allocations" leftSection={<IconAdjustmentsHorizontal size={18} />}>
                            {t('tabs.iimaAllocations')}
                        </Tabs.Tab>
                        <Tabs.Tab value="hazard" leftSection={<IconAlertTriangle size={18} />}>
                            {t('tabs.hazardRating')}
                        </Tabs.Tab>
                    </Tabs.List>

                    {/* Tab 1: Water Quality Guidelines (Bulk Groups + Details Toggle) */}
                    <Tabs.Panel value="wq">
                        <Flex justify="center" mb="lg">
                            <SegmentedControl
                                value={wqMode}
                                onChange={(val) => setWqMode(val as 'group' | 'individual')}
                                data={[
                                    { label: t('wqTable.groupAdjustment'), value: 'group' },
                                    { label: t('wqTable.individualStations'), value: 'individual' },
                                ]}
                                size="md"
                            />
                        </Flex>

                        {/* MODE A: WQ Group / Basin-wide bulk edits */}
                        {wqMode === 'group' ? (
                            <Card withBorder radius="md" p="xl" mb="xl">
                                <Stack gap="xs" mb="lg">
                                    <Title order={3}>{t('wqTable.groupAdjustment')}</Title>
                                    <Text size="sm" c="dimmed">
                                        Bulk configure Water Quality compliance limits system-wide, basin-wide, or per subcatchment.
                                    </Text>
                                </Stack>

                                <form onSubmit={handleSaveWqGroup}>
                                    <Grid gutter="md" align="flex-end">
                                        <Grid.Col span={{ base: 12, md: 4 }}>
                                            <Select
                                                label={t('wqTable.selectParam')}
                                                placeholder={t('wqTable.paramFilter')}
                                                data={parameters.map((p) => ({ value: p.code, label: `${p.code} — ${p.name}` }))}
                                                value={wqGroupForm.data.parameter_code}
                                                onChange={(val) => wqGroupForm.setData('parameter_code', val || '')}
                                                error={wqGroupForm.errors.parameter_code}
                                                required
                                                searchable
                                            />
                                        </Grid.Col>

                                        <Grid.Col span={{ base: 12, md: 4 }}>
                                            <Select
                                                label={t('wqTable.selectScope')}
                                                placeholder="Scope"
                                                data={[
                                                    { value: 'system', label: t('wqTable.scopeSystem') },
                                                    { value: 'basin', label: t('wqTable.scopeBasin') },
                                                    { value: 'subcatchment', label: t('wqTable.scopeSubcatchment') },
                                                ]}
                                                value={wqGroupForm.data.scope}
                                                onChange={(val) => wqGroupForm.setData('scope', (val as any) || 'system')}
                                                error={wqGroupForm.errors.scope}
                                                required
                                            />
                                        </Grid.Col>

                                        {wqGroupForm.data.scope === 'basin' && (
                                            <Grid.Col span={{ base: 12, md: 4 }}>
                                                <Select
                                                    label={t('wqTable.selectBasin')}
                                                    placeholder="Basin"
                                                    data={[
                                                        { value: 'Incomati', label: 'Incomati' },
                                                        { value: 'Maputo', label: 'Maputo' },
                                                    ]}
                                                    value={wqGroupForm.data.basin}
                                                    onChange={(val) => wqGroupForm.setData('basin', val || '')}
                                                    error={wqGroupForm.errors.basin}
                                                    required
                                                />
                                            </Grid.Col>
                                        )}

                                        {wqGroupForm.data.scope === 'subcatchment' && (
                                            <Grid.Col span={{ base: 12, md: 4 }}>
                                                <Select
                                                    label={t('wqTable.selectSubcatchment')}
                                                    placeholder="Subcatchment"
                                                    data={subcatchments.map((s) => ({ value: s.id, label: `${s.code} — ${s.name}` }))}
                                                    value={wqGroupForm.data.subcatchment_id}
                                                    onChange={(val) => wqGroupForm.setData('subcatchment_id', val || '')}
                                                    error={wqGroupForm.errors.subcatchment_id}
                                                    required
                                                    searchable
                                                />
                                            </Grid.Col>
                                        )}

                                        <Grid.Col span={{ base: 12, sm: 6, md: 4 }}>
                                            <NumberInput
                                                label={t('modals.minLabel')}
                                                placeholder="e.g. 6.5"
                                                value={wqGroupForm.data.min_value}
                                                onChange={(val) => wqGroupForm.setData('min_value', val === '' ? '' : Number(val))}
                                                error={wqGroupForm.errors.min_value}
                                                decimalScale={3}
                                            />
                                        </Grid.Col>

                                        <Grid.Col span={{ base: 12, sm: 6, md: 4 }}>
                                            <NumberInput
                                                label={t('modals.maxLabel')}
                                                placeholder="e.g. 8.5"
                                                value={wqGroupForm.data.max_value}
                                                onChange={(val) => wqGroupForm.setData('max_value', val === '' ? '' : Number(val))}
                                                error={wqGroupForm.errors.max_value}
                                                decimalScale={3}
                                            />
                                        </Grid.Col>

                                        <Grid.Col span={{ base: 12, md: 4 }}>
                                            <Button type="submit" fullWidth loading={wqGroupForm.processing} leftSection={<IconCheck size={18} />}>
                                                {t('wqTable.applyGroup')}
                                            </Button>
                                        </Grid.Col>
                                    </Grid>
                                </form>
                            </Card>
                        ) : (
                            /* MODE B: WQ Station-Specific Details List */
                            <>
                                <Card withBorder radius="md" p="md" mb="lg" style={{ backdropFilter: 'blur(8px)', backgroundColor: 'rgba(255, 255, 255, 0.9)' }}>
                                    <Stack gap="md">
                                        <Grid align="flex-end">
                                            <Grid.Col span={{ base: 12, sm: 4 }}>
                                                <TextInput
                                                    label={t('wqTable.station')}
                                                    placeholder={t('wqTable.searchPlaceholder')}
                                                    leftSection={<IconSearch size={16} />}
                                                    value={search}
                                                    onChange={(e) => setSearch(e.target.value)}
                                                    onKeyDown={handleSearchKeyDown}
                                                    rightSection={
                                                        search && (
                                                            <ActionIcon size="xs" variant="subtle" onClick={() => { setSearch(''); applyFilters({ search: '' }); }}>
                                                                ×
                                                            </ActionIcon>
                                                        )
                                                    }
                                                />
                                            </Grid.Col>

                                            <Grid.Col span={{ base: 12, sm: 3 }}>
                                                <Select
                                                    label={t('wqTable.basin')}
                                                    placeholder={t('wqTable.basinFilter')}
                                                    data={[
                                                        { value: '', label: t('wqTable.basinFilter') },
                                                        { value: 'Incomati', label: 'Incomati' },
                                                        { value: 'Maputo', label: 'Maputo' },
                                                    ]}
                                                    value={basin}
                                                    onChange={(val) => { setBasin(val || ''); applyFilters({ basin: val || '' }); }}
                                                />
                                            </Grid.Col>

                                            <Grid.Col span={{ base: 12, sm: 3 }}>
                                                <Select
                                                    label={t('wqTable.parameter')}
                                                    placeholder={t('wqTable.paramFilter')}
                                                    data={[
                                                        { value: '', label: t('wqTable.paramFilter') },
                                                        ...parameters.map((p) => ({ value: p.code, label: `${p.code} — ${p.name}` })),
                                                    ]}
                                                    value={parameter}
                                                    onChange={(val) => { setParameter(val || ''); applyFilters({ parameter: val || '' }); }}
                                                    searchable
                                                />
                                            </Grid.Col>

                                            <Grid.Col span={{ base: 12, sm: 2 }}>
                                                <Button fullWidth onClick={() => applyFilters()} leftSection={<IconSearch size={16} />}>
                                                    Search
                                                </Button>
                                            </Grid.Col>
                                        </Grid>

                                        <Flex gap="md" wrap="wrap">
                                            <Switch
                                                label={t('wqTable.priorityFilter')}
                                                checked={isPriority}
                                                onChange={(e) => { setIsPriority(e.currentTarget.checked); applyFilters({ is_priority: e.currentTarget.checked }); }}
                                            />
                                            <Switch
                                                label={t('wqTable.customFilter')}
                                                checked={isCustom}
                                                onChange={(e) => { setIsCustom(e.currentTarget.checked); applyFilters({ is_custom: e.currentTarget.checked }); }}
                                            />
                                        </Flex>
                                    </Stack>
                                </Card>

                                <Card withBorder radius="md" p="0" mb="md" style={{ overflow: 'hidden' }}>
                                    <Table verticalSpacing="sm" horizontalSpacing="md" highlightOnHover striped>
                                        <Table.Thead style={{ backgroundColor: 'var(--mantine-color-gray-0)' }}>
                                            <Table.Tr>
                                                <Table.Th>{t('wqTable.station')}</Table.Th>
                                                <Table.Th>{t('wqTable.basin')}</Table.Th>
                                                <Table.Th>{t('wqTable.parameter')}</Table.Th>
                                                <Table.Th>{t('wqTable.defaultLimit')}</Table.Th>
                                                <Table.Th>{t('wqTable.type')}</Table.Th>
                                                <Table.Th>{t('wqTable.notes')}</Table.Th>
                                                {canManage && <Table.Th style={{ textAlign: 'right' }}>{t('wqTable.actions')}</Table.Th>}
                                            </Table.Tr>
                                        </Table.Thead>
                                        <Table.Tbody>
                                            {complianceThresholds.data.length > 0 ? (
                                                complianceThresholds.data.map((row) => (
                                                    <Table.Tr key={row.id}>
                                                        <Table.Td>
                                                            <Stack gap={0}>
                                                                <Text size="sm" fw={700}>{row.station_code}</Text>
                                                                <Text size="xs" c="dimmed">{row.station_name}</Text>
                                                            </Stack>
                                                        </Table.Td>
                                                        <Table.Td>
                                                            <Stack gap={0}>
                                                                <Text size="sm">{row.station_basin}</Text>
                                                                <Text size="xs" c="dimmed">{row.station_country}</Text>
                                                            </Stack>
                                                        </Table.Td>
                                                        <Table.Td>
                                                            <Stack gap={0}>
                                                                <Group gap="xs">
                                                                    <Text size="sm" fw={700}>{row.parameter_code}</Text>
                                                                    {row.is_priority_pollutant && (
                                                                        <Tooltip label={t('wqTable.priorityBadge')} position="top" withinPortal>
                                                                            <Badge size="xs" color="red" variant="light">
                                                                                Priority
                                                                            </Badge>
                                                                        </Tooltip>
                                                                    )}
                                                                </Group>
                                                                <Text size="xs" c="dimmed">{row.parameter_name}</Text>
                                                            </Stack>
                                                        </Table.Td>
                                                        <Table.Td>
                                                            <Text size="sm" fw={500}>
                                                                {row.min_value !== null ? `${row.min_value}` : '0'} – {row.max_value !== null ? `${row.max_value}` : '∞'} {row.unit}
                                                            </Text>
                                                        </Table.Td>
                                                        <Table.Td>
                                                            {row.is_custom ? (
                                                                <Badge color="blue" variant="light">
                                                                    {t('wqTable.customBadge')}
                                                                </Badge>
                                                            ) : (
                                                                <Badge color="gray" variant="light">
                                                                    {t('wqTable.defaultBadge')}
                                                                </Badge>
                                                            )}
                                                        </Table.Td>
                                                        <Table.Td>
                                                            <Text size="xs" c="dimmed" style={{ maxWidth: '220px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }} title={row.notes}>
                                                                {row.notes}
                                                            </Text>
                                                        </Table.Td>
                                                        {canManage && (
                                                            <Table.Td>
                                                                <Group gap="xs" justify="flex-end">
                                                                    <Tooltip label={t('wqTable.edit')}>
                                                                        <ActionIcon size="sm" variant="light" onClick={() => handleEditWq(row)}>
                                                                            <IconEdit size={16} />
                                                                        </ActionIcon>
                                                                    </Tooltip>
                                                                    {row.is_custom && (
                                                                        <Tooltip label={t('wqTable.reset')}>
                                                                            <ActionIcon size="sm" color="red" variant="light" onClick={() => handleResetWq(row.id)}>
                                                                                <IconRefresh size={16} />
                                                                            </ActionIcon>
                                                                        </Tooltip>
                                                                    )}
                                                                </Group>
                                                            </Table.Td>
                                                        )}
                                                    </Table.Tr>
                                                ))
                                            ) : (
                                                <Table.Tr>
                                                    <Table.Td colSpan={canManage ? 7 : 6} style={{ textAlign: 'center', padding: '24px 0' }}>
                                                        <Text c="dimmed" size="sm">
                                                            {t('wqTable.noThresholds')}
                                                        </Text>
                                                    </Table.Td>
                                                </Table.Tr>
                                            )}
                                        </Table.Tbody>
                                    </Table>
                                </Card>

                                {complianceThresholds.last_page > 1 && (
                                    <Flex justify="center" mt="md" mb="xl">
                                        <Pagination
                                            value={complianceThresholds.current_page}
                                            onChange={handlePageChange}
                                            total={complianceThresholds.last_page}
                                            size="md"
                                        />
                                    </Flex>
                                )}
                            </>
                        )}
                    </Tabs.Panel>

                    {/* Tab 2: IIMA Ecological Flows */}
                    <Tabs.Panel value="eflow">
                        <Card withBorder radius="md" p="md" mb="lg">
                            <Stack gap="xs">
                                <Title order={4}>{t('eflowTab.title')}</Title>
                                <Text size="sm" c="dimmed">
                                    {t('eflowTab.description')}
                                </Text>
                            </Stack>
                        </Card>

                        <SimpleGrid cols={{ base: 1, md: 2 }} spacing="lg" mb="xl">
                            {eflowRequirements.map((row) => {
                                const isMaputo = row.river.toLowerCase().includes('maputo') || row.river.toLowerCase().includes('usuthu') || row.river.toLowerCase().includes('pongola') || row.river.toLowerCase().includes('ngwavuma') || row.river.toLowerCase().includes('mkhondvo') || row.river.toLowerCase().includes('hlelo') || row.river.toLowerCase().includes('ngwempisi') || row.river.toLowerCase().includes('mpuluzi') || row.river.toLowerCase().includes('lusushwana');
                                const highlightColor = isMaputo ? 'rgba(210,109,84, 0.08)' : 'rgba(5,196,188, 0.08)';
                                const stripeColor = isMaputo ? 'rgb(210,109,84)' : 'rgb(5,196,188)';

                                return (
                                    <Card key={row.id} withBorder radius="md" p="md" style={{ borderLeft: `5px solid ${stripeColor}`, backgroundColor: '#fff', position: 'relative' }}>
                                        <Flex justify="space-between" align="flex-start" mb="xs">
                                            <Stack gap={0}>
                                                <Group gap="xs">
                                                    <Text size="sm" fw={800} c="dimmed" style={{ textTransform: 'uppercase' }}>
                                                        {row.river} River
                                                    </Text>
                                                    <Badge size="xs" variant="light" color={isMaputo ? 'orange' : 'teal'}>
                                                        {isMaputo ? 'Maputo Basin' : 'Incomati Basin'}
                                                    </Badge>
                                                </Group>
                                                <Title order={4} fw={700} style={{ color: 'var(--mantine-color-black)' }}>
                                                    {row.key_point}
                                                </Title>
                                            </Stack>

                                            {canManage && (
                                                <ActionIcon size="md" variant="subtle" color="gray" onClick={() => handleEditEflow(row)}>
                                                    <IconEdit size={18} />
                                                </ActionIcon>
                                            )}
                                        </Flex>

                                        <SimpleGrid cols={2} spacing="sm" mt="md" py="xs" style={{ backgroundColor: highlightColor, borderRadius: '8px' }}>
                                            <Stack gap={0} px="md" py="xs">
                                                <Text size="xs" c="dimmed" style={{ textTransform: 'uppercase', letterSpacing: '0.5px' }}>
                                                    {t('eflowTab.meanAnnual')}
                                                </Text>
                                                <Text size="lg" fw={800} style={{ color: 'var(--mantine-color-black)' }}>
                                                    {row.mean_annual_mm3} <span style={{ fontSize: '11px', fontWeight: 500 }}>million m³/a</span>
                                                </Text>
                                            </Stack>

                                            <Stack gap={0} px="md" py="xs" style={{ borderLeft: '1px solid rgba(0,0,0,0.06)' }}>
                                                <Text size="xs" c="dimmed" style={{ textTransform: 'uppercase', letterSpacing: '0.5px' }}>
                                                    {t('eflowTab.minFlow')}
                                                </Text>
                                                <Text size="lg" fw={800} style={{ color: 'var(--mantine-color-black)' }}>
                                                    {row.min_flow_m3_s} <span style={{ fontSize: '11px', fontWeight: 500 }}>m³/s</span>
                                                </Text>
                                            </Stack>
                                        </SimpleGrid>

                                        <Stack gap="xs" mt="md" px="xs">
                                            <Flex gap="xs" align="center" wrap="wrap">
                                                <Text size="xs" fw={700}>{t('eflowTab.subcatchment')}:</Text>
                                                <Badge size="sm" variant="outline" color="gray">
                                                    {row.subcatchment_code} — {row.subcatchment_name}
                                                </Badge>
                                            </Flex>

                                            {row.station_code && (
                                                <Flex gap="xs" align="center" wrap="wrap">
                                                    <Text size="xs" fw={700}>Active Gauge:</Text>
                                                    <Badge size="sm" color="blue" variant="light">
                                                        {row.station_code} ({row.station_name})
                                                    </Badge>
                                                </Flex>
                                            )}

                                            <Text size="xs" c="dimmed" mt="xs">
                                                <strong>{t('eflowTab.source')}:</strong> {row.source_article}
                                                {row.note && ` — ${row.note}`}
                                            </Text>
                                        </Stack>
                                    </Card>
                                );
                            })}
                        </SimpleGrid>
                    </Tabs.Panel>

                    {/* Tab 3: IIMA Water Allocations (Allocations CRUD) */}
                    <Tabs.Panel value="allocations">
                        <Card withBorder radius="md" p="md" mb="lg">
                            <Flex justify="space-between" align="center" wrap="wrap" gap="md">
                                <Stack gap="2px">
                                    <Title order={4}>{t('allocationsTab.title')}</Title>
                                    <Text size="sm" c="dimmed">
                                        {t('allocationsTab.description')}
                                    </Text>
                                </Stack>
                                {canManage && (
                                    <Button onClick={openAllocCreate} leftSection={<IconPlus size={18} />}>
                                        {t('allocationsTab.create')}
                                    </Button>
                                )}
                            </Flex>
                        </Card>

                        <Card withBorder radius="md" p="0" mb="xl" style={{ overflow: 'hidden' }}>
                            <Table verticalSpacing="sm" horizontalSpacing="md" highlightOnHover striped>
                                <Table.Thead style={{ backgroundColor: 'var(--mantine-color-gray-0)' }}>
                                    <Table.Tr>
                                        <Table.Th>{t('allocationsTab.subcatchment')}</Table.Th>
                                        <Table.Th>{t('allocationsTab.country')}</Table.Th>
                                        <Table.Th>{t('allocationsTab.category')}</Table.Th>
                                        <Table.Th>{t('allocationsTab.volume')}</Table.Th>
                                        <Table.Th>{t('allocationsTab.year')}</Table.Th>
                                        <Table.Th>{t('eflowTab.notes')}</Table.Th>
                                        {canManage && <Table.Th style={{ textAlign: 'right' }}>{t('wqTable.actions')}</Table.Th>}
                                    </Table.Tr>
                                </Table.Thead>
                                <Table.Tbody>
                                    {allocations.length > 0 ? (
                                        allocations.map((row) => (
                                            <Table.Tr key={row.id}>
                                                <Table.Td>
                                                    <Stack gap={0}>
                                                        <Text fw={700} size="sm">{row.subcatchment_code}</Text>
                                                        <Text size="xs" c="dimmed">{row.subcatchment_name}</Text>
                                                    </Stack>
                                                </Table.Td>
                                                <Table.Td>
                                                    <Badge variant="light" color={row.country === 'South Africa' ? 'blue' : row.country === 'Eswatini' ? 'orange' : 'teal'}>
                                                        {row.country}
                                                    </Badge>
                                                </Table.Td>
                                                <Table.Td>{row.user_category_name}</Table.Td>
                                                <Table.Td fw={700}>{row.allocation_mm3_a} million m³/a</Table.Td>
                                                <Table.Td>{row.effective_from}</Table.Td>
                                                <Table.Td>
                                                    <Text size="xs" c="dimmed" style={{ maxWidth: '200px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }} title={row.note || ''}>
                                                        {row.note || '—'}
                                                    </Text>
                                                </Table.Td>
                                                {canManage && (
                                                    <Table.Td>
                                                        <Group gap="xs" justify="flex-end">
                                                            <Tooltip label={t('wqTable.edit')}>
                                                                <ActionIcon size="sm" variant="light" onClick={() => handleEditAllocation(row)}>
                                                                    <IconEdit size={16} />
                                                                </ActionIcon>
                                                            </Tooltip>
                                                            <Tooltip label="Delete">
                                                                <ActionIcon size="sm" color="red" variant="light" onClick={() => handleDeleteAllocation(row.id)}>
                                                                    <IconTrash size={16} />
                                                                </ActionIcon>
                                                            </Tooltip>
                                                        </Group>
                                                    </Table.Td>
                                                )}
                                            </Table.Tr>
                                        ))
                                    ) : (
                                        <Table.Tr>
                                            <Table.Td colSpan={canManage ? 7 : 6} style={{ textAlign: 'center', padding: '24px 0' }}>
                                                <Text c="dimmed" size="sm">
                                                    {t('allocationsTab.noAllocations')}
                                                </Text>
                                            </Table.Td>
                                        </Table.Tr>
                                    )}
                                </Table.Tbody>
                            </Table>
                        </Card>
                    </Tabs.Panel>

                    {/* Tab 4: Hazard & Alert Matrices (Hazard Settings CRUD) */}
                    <Tabs.Panel value="hazard">
                        <form onSubmit={handleSaveHazards}>
                            <Grid gutter="xl" mb="xl">
                                {/* Section 1: Flood Alert Tiers CRUD */}
                                <Grid.Col span={{ base: 12, md: 6 }}>
                                    <Card withBorder radius="md" p="lg" h="100%">
                                        <Group gap="xs" mb="xs">
                                            <IconAlertTriangle size={18} />
                                            <Title order={4}>{t('hazardTab.floodTitle')}</Title>
                                        </Group>
                                        <Text size="sm" c="dimmed" mb="md">
                                            {t('hazardTab.floodDesc')}
                                        </Text>

                                        <Stack gap="md" mb="md">
                                            <NumberInput
                                                label="Flood Watch Index Threshold"
                                                value={hazardForm.data.flood_watch}
                                                onChange={(val) => hazardForm.setData('flood_watch', Number(val))}
                                                error={hazardForm.errors.flood_watch}
                                                required
                                                min={0}
                                                max={10}
                                                decimalScale={2}
                                            />
                                            <NumberInput
                                                label="Moderate Flooding Index Threshold"
                                                value={hazardForm.data.flood_moderate}
                                                onChange={(val) => hazardForm.setData('flood_moderate', Number(val))}
                                                error={hazardForm.errors.flood_moderate}
                                                required
                                                min={0}
                                                max={10}
                                                decimalScale={2}
                                            />
                                            <NumberInput
                                                label="Severe Flooding Index Threshold"
                                                value={hazardForm.data.flood_severe}
                                                onChange={(val) => hazardForm.setData('flood_severe', Number(val))}
                                                error={hazardForm.errors.flood_severe}
                                                required
                                                min={0}
                                                max={10}
                                                decimalScale={2}
                                            />
                                        </Stack>
                                    </Card>
                                </Grid.Col>

                                {/* Section 2: Drought Severity Bands CRUD */}
                                <Grid.Col span={{ base: 12, md: 6 }}>
                                    <Card withBorder radius="md" p="lg" h="100%">
                                        <Stack gap="xs" mb="xs">
                                            <Title order={4}>{t('hazardTab.droughtTitle')}</Title>
                                        </Stack>
                                        <Text size="sm" c="dimmed" mb="md">
                                            {t('hazardTab.droughtDesc')}
                                        </Text>

                                        <Stack gap="md" mb="md">
                                            <NumberInput
                                                label="Drought Watch Index Threshold"
                                                value={hazardForm.data.drought_watch}
                                                onChange={(val) => hazardForm.setData('drought_watch', Number(val))}
                                                error={hazardForm.errors.drought_watch}
                                                required
                                                min={0}
                                                max={10}
                                                decimalScale={2}
                                            />
                                            <NumberInput
                                                label="Moderate Drought Index Threshold"
                                                value={hazardForm.data.drought_moderate}
                                                onChange={(val) => hazardForm.setData('drought_moderate', Number(val))}
                                                error={hazardForm.errors.drought_moderate}
                                                required
                                                min={0}
                                                max={10}
                                                decimalScale={2}
                                            />
                                            <NumberInput
                                                label="Severe Drought Index Threshold"
                                                value={hazardForm.data.drought_severe}
                                                onChange={(val) => hazardForm.setData('drought_severe', Number(val))}
                                                error={hazardForm.errors.drought_severe}
                                                required
                                                min={0}
                                                max={10}
                                                decimalScale={2}
                                            />
                                        </Stack>
                                    </Card>
                                </Grid.Col>

                                {/* Section 3: Chemical Pollution Categories CRUD */}
                                <Grid.Col span={{ base: 12, md: 6 }}>
                                    <Card withBorder radius="md" p="lg" h="100%">
                                        <Stack gap="xs" mb="xs">
                                            <Title order={4}>{t('hazardTab.chemicalTitle')}</Title>
                                        </Stack>
                                        <Text size="sm" c="dimmed" mb="md">
                                            {t('hazardTab.chemicalDesc')}
                                        </Text>

                                        <Stack gap="sm" mb="md">
                                            <NumberInput
                                                label="Category A RQ (Extremely Hazardous) - kg"
                                                value={hazardForm.data.chemical_a}
                                                onChange={(val) => hazardForm.setData('chemical_a', Number(val))}
                                                error={hazardForm.errors.chemical_a}
                                                required
                                                min={0}
                                                decimalScale={3}
                                            />
                                            <NumberInput
                                                label="Category B RQ (Highly Hazardous) - kg"
                                                value={hazardForm.data.chemical_b}
                                                onChange={(val) => hazardForm.setData('chemical_b', Number(val))}
                                                error={hazardForm.errors.chemical_b}
                                                required
                                                min={0}
                                                decimalScale={3}
                                            />
                                            <NumberInput
                                                label="Category C RQ (Hazardous) - kg"
                                                value={hazardForm.data.chemical_c}
                                                onChange={(val) => hazardForm.setData('chemical_c', Number(val))}
                                                error={hazardForm.errors.chemical_c}
                                                required
                                                min={0}
                                                decimalScale={3}
                                            />
                                            <NumberInput
                                                label="Category D RQ (Moderately Hazardous) - kg"
                                                value={hazardForm.data.chemical_d}
                                                onChange={(val) => hazardForm.setData('chemical_d', Number(val))}
                                                error={hazardForm.errors.chemical_d}
                                                required
                                                min={0}
                                                decimalScale={3}
                                            />
                                            <NumberInput
                                                label="Category X RQ (Other Pollutants) - kg"
                                                value={hazardForm.data.chemical_x}
                                                onChange={(val) => hazardForm.setData('chemical_x', Number(val))}
                                                error={hazardForm.errors.chemical_x}
                                                required
                                                min={0}
                                                decimalScale={3}
                                            />
                                        </Stack>
                                    </Card>
                                </Grid.Col>

                                {/* Section 4: Biological & Fish Kills Alert Levels CRUD */}
                                <Grid.Col span={{ base: 12, md: 6 }}>
                                    <Card withBorder radius="md" p="lg" h="100%">
                                        <Stack gap="xs" mb="xs">
                                            <Title order={4}>{t('hazardTab.biologicalTitle')}</Title>
                                        </Stack>
                                        <Text size="sm" c="dimmed" mb="md">
                                            {t('hazardTab.biologicalDesc')}
                                        </Text>

                                        <Stack gap="md" mb="md">
                                            <Card withBorder radius="sm" p="xs" style={{ borderLeft: '4px solid #f08c00' }}>
                                                <Text size="xs" fw={700} mb="xs" style={{ textTransform: 'uppercase' }}>Fecal-Coliform routine limits (CFU/100 mL)</Text>
                                                <Group grow gap="sm">
                                                    <NumberInput
                                                        label="Orange Alert Threshold"
                                                        value={hazardForm.data.coliform_orange}
                                                        onChange={(val) => hazardForm.setData('coliform_orange', Number(val))}
                                                        error={hazardForm.errors.coliform_orange}
                                                        required
                                                        min={0}
                                                    />
                                                    <NumberInput
                                                        label="Red Alert Threshold"
                                                        value={hazardForm.data.coliform_red}
                                                        onChange={(val) => hazardForm.setData('coliform_red', Number(val))}
                                                        error={hazardForm.errors.coliform_red}
                                                        required
                                                        min={0}
                                                    />
                                                </Group>
                                            </Card>

                                            <Card withBorder radius="sm" p="xs" style={{ borderLeft: '4px solid #c92a2a' }}>
                                                <Text size="xs" fw={700} mb="xs" style={{ textTransform: 'uppercase' }}>Fish Kills reports limits</Text>
                                                <Stack gap="xs">
                                                    <NumberInput
                                                        label="Orange Trigger (Single reported incident)"
                                                        value={hazardForm.data.fish_kill_orange}
                                                        onChange={(val) => hazardForm.setData('fish_kill_orange', Number(val))}
                                                        error={hazardForm.errors.fish_kill_orange}
                                                        required
                                                        min={0}
                                                    />
                                                    <Group grow gap="sm">
                                                        <NumberInput
                                                            label="Red Trigger (Incidents Count)"
                                                            value={hazardForm.data.fish_kill_red_count}
                                                            onChange={(val) => hazardForm.setData('fish_kill_red_count', Number(val))}
                                                            error={hazardForm.errors.fish_kill_red_count}
                                                            required
                                                            min={0}
                                                        />
                                                        <NumberInput
                                                            label="Red Trigger (Total Fish)"
                                                            value={hazardForm.data.fish_kill_red_mass}
                                                            onChange={(val) => hazardForm.setData('fish_kill_red_mass', Number(val))}
                                                            error={hazardForm.errors.fish_kill_red_mass}
                                                            required
                                                            min={0}
                                                        />
                                                    </Group>
                                                </Stack>
                                            </Card>
                                        </Stack>
                                    </Card>
                                </Grid.Col>
                            </Grid>

                            {canManage && (
                                <Flex justify="flex-end" mb="xl">
                                    <Button type="submit" size="md" loading={hazardForm.processing} leftSection={<IconCheck size={18} />}>
                                        {t('hazardTab.saveSettings')}
                                    </Button>
                                </Flex>
                            )}
                        </form>
                    </Tabs.Panel>
                </Tabs>
            </Container>

            {/* Modal: Edit WQ Threshold */}
            <Modal opened={wqOpened} onClose={closeWq} title={<Text fw={700} size="md">{t('modals.editWqTitle')}</Text>} centered radius="md">
                {selectedWq && (
                    <form onSubmit={handleSaveWq}>
                        <Stack gap="md">
                            <Stack gap="2px">
                                <Text size="sm" fw={700}>{selectedWq.station_code} — {selectedWq.station_name}</Text>
                                <Text size="xs" c="dimmed">Parameter: {selectedWq.parameter_code} ({selectedWq.parameter_name})</Text>
                                <Text size="xs" c="dimmed">Basin: {selectedWq.station_basin} | Country: {selectedWq.station_country}</Text>
                            </Stack>

                            <NumberInput
                                label={t('modals.minLabel')}
                                description={`Default unit: ${selectedWq.unit}`}
                                value={wqIndividualForm.data.min_value}
                                onChange={(val) => wqIndividualForm.setData('min_value', val === '' ? '' : Number(val))}
                                error={wqIndividualForm.errors.min_value}
                                placeholder="e.g. 6.5"
                                decimalScale={3}
                            />

                            <NumberInput
                                label={t('modals.maxLabel')}
                                description={`Default unit: ${selectedWq.unit}`}
                                value={wqIndividualForm.data.max_value}
                                onChange={(val) => wqIndividualForm.setData('max_value', val === '' ? '' : Number(val))}
                                error={wqIndividualForm.errors.max_value}
                                placeholder="e.g. 8.5"
                                decimalScale={3}
                            />

                            <Group justify="flex-end" mt="md">
                                <Button variant="light" color="gray" onClick={closeWq}>
                                    {t('modals.cancel')}
                                </Button>
                                <Button type="submit" loading={wqIndividualForm.processing}>
                                    {t('modals.save')}
                                </Button>
                            </Group>
                        </Stack>
                    </form>
                )}
            </Modal>

            {/* Modal: Edit Ecological Flow Target */}
            <Modal opened={eflowOpened} onClose={closeEflow} title={<Text fw={700} size="md">{t('modals.editEflowTitle')}</Text>} centered radius="md">
                {selectedEflow && (
                    <form onSubmit={handleSaveEflow}>
                        <Stack gap="md">
                            <Stack gap="2px">
                                <Text size="sm" fw={700}>{selectedEflow.river} River — {selectedEflow.key_point}</Text>
                                <Text size="xs" c="dimmed">Subcatchment: {selectedEflow.subcatchment_code} ({selectedEflow.subcatchment_name})</Text>
                            </Stack>

                            <NumberInput
                                label={t('modals.meanAnnualLabel')}
                                value={eflowForm.data.mean_annual_mm3}
                                onChange={(val) => eflowForm.setData('mean_annual_mm3', val === '' ? '' : Number(val))}
                                error={eflowForm.errors.mean_annual_mm3}
                                required
                                min={0}
                                decimalScale={3}
                            />

                            <NumberInput
                                label={t('modals.minFlowLabel')}
                                value={eflowForm.data.min_flow_m3_s}
                                onChange={(val) => eflowForm.setData('min_flow_m3_s', val === '' ? '' : Number(val))}
                                error={eflowForm.errors.min_flow_m3_s}
                                required
                                min={0}
                                decimalScale={3}
                            />

                            <Group justify="flex-end" mt="md">
                                <Button variant="light" color="gray" onClick={closeEflow}>
                                    {t('modals.cancel')}
                                </Button>
                                <Button type="submit" loading={eflowForm.processing}>
                                    {t('modals.save')}
                                </Button>
                            </Group>
                        </Stack>
                    </form>
                )}
            </Modal>

            {/* Modal: Create Water Allocation */}
            <Modal opened={allocCreateOpened} onClose={closeAllocCreate} title={<Text fw={700} size="md">{t('modals.createAllocationTitle')}</Text>} centered radius="md">
                <form onSubmit={handleSaveAllocationCreate}>
                    <Stack gap="md">
                        <Select
                            label={t('modals.subcatchmentLabel')}
                            placeholder="Subcatchment"
                            data={subcatchments.map((s) => ({ value: s.id, label: `${s.code} — ${s.name}` }))}
                            value={allocationForm.data.subcatchment_id}
                            onChange={(val) => allocationForm.setData('subcatchment_id', val || '')}
                            error={allocationForm.errors.subcatchment_id}
                            required
                            searchable
                        />

                        <Select
                            label={t('modals.countryLabel')}
                            placeholder="Country"
                            data={[
                                { value: 'Eswatini', label: 'Eswatini' },
                                { value: 'South Africa', label: 'South Africa' },
                                { value: 'Mozambique', label: 'Mozambique' },
                                { value: 'KOBWA', label: 'KOBWA' },
                                { value: 'TPTC', label: 'TPTC' },
                            ]}
                            value={allocationForm.data.country}
                            onChange={(val) => allocationForm.setData('country', val || '')}
                            error={allocationForm.errors.country}
                            required
                        />

                        <Select
                            label={t('modals.categoryLabel')}
                            placeholder="Category"
                            data={userCategories.map((c) => ({ value: c.code, label: c.name }))}
                            value={allocationForm.data.user_category}
                            onChange={(val) => allocationForm.setData('user_category', val || '')}
                            error={allocationForm.errors.user_category}
                            required
                        />

                        <NumberInput
                            label={t('modals.volumeLabel')}
                            placeholder="e.g. 120.0"
                            value={allocationForm.data.allocation_mm3_a}
                            onChange={(val) => allocationForm.setData('allocation_mm3_a', val === '' ? '' : Number(val))}
                            error={allocationForm.errors.allocation_mm3_a}
                            required
                            min={0}
                            decimalScale={3}
                        />

                        <NumberInput
                            label={t('modals.yearLabel')}
                            placeholder="e.g. 2026"
                            value={allocationForm.data.effective_from}
                            onChange={(val) => allocationForm.setData('effective_from', Number(val))}
                            error={allocationForm.errors.effective_from}
                            required
                            min={1900}
                            max={2100}
                        />

                        <TextInput
                            label={t('modals.notesLabel')}
                            placeholder="e.g. IIMA Table 4-1 default"
                            value={allocationForm.data.note}
                            onChange={(e) => allocationForm.setData('note', e.target.value)}
                            error={allocationForm.errors.note}
                        />

                        <Group justify="flex-end" mt="md">
                            <Button variant="light" color="gray" onClick={closeAllocCreate}>
                                {t('modals.cancel')}
                            </Button>
                            <Button type="submit" loading={allocationForm.processing}>
                                {t('modals.save')}
                            </Button>
                        </Group>
                    </Stack>
                </form>
            </Modal>

            {/* Modal: Edit Water Allocation */}
            <Modal opened={allocEditOpened} onClose={closeAllocEdit} title={<Text fw={700} size="md">{t('modals.editAllocationTitle')}</Text>} centered radius="md">
                {selectedAllocation && (
                    <form onSubmit={handleSaveAllocationEdit}>
                        <Stack gap="md">
                            <Stack gap="2px">
                                <Text fw={700} size="sm">{selectedAllocation.subcatchment_code} — {selectedAllocation.subcatchment_name}</Text>
                                <Text size="xs" c="dimmed">Country: {selectedAllocation.country} | Category: {selectedAllocation.user_category_name}</Text>
                            </Stack>

                            <NumberInput
                                label={t('modals.volumeLabel')}
                                value={allocationForm.data.allocation_mm3_a}
                                onChange={(val) => allocationForm.setData('allocation_mm3_a', val === '' ? '' : Number(val))}
                                error={allocationForm.errors.allocation_mm3_a}
                                required
                                min={0}
                                decimalScale={3}
                            />

                            <NumberInput
                                label={t('modals.yearLabel')}
                                value={allocationForm.data.effective_from}
                                onChange={(val) => allocationForm.setData('effective_from', Number(val))}
                                error={allocationForm.errors.effective_from}
                                required
                                min={1900}
                                max={2100}
                            />

                            <TextInput
                                label={t('modals.notesLabel')}
                                value={allocationForm.data.note}
                                onChange={(e) => allocationForm.setData('note', e.target.value)}
                                error={allocationForm.errors.note}
                            />

                            <Group justify="flex-end" mt="md">
                                <Button variant="light" color="gray" onClick={closeAllocEdit}>
                                    {t('modals.cancel')}
                                </Button>
                                <Button type="submit" loading={allocationForm.processing}>
                                    {t('modals.save')}
                                </Button>
                            </Group>
                        </Stack>
                    </form>
                )}
            </Modal>
        </>
    );
}

Index.layout = (page: React.ReactNode) => <AuthenticatedLayout>{page}</AuthenticatedLayout>;
