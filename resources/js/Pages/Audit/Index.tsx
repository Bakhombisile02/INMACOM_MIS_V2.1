import { Head, router } from '@inertiajs/react';
import {
    ActionIcon,
    Anchor,
    Avatar,
    Badge,
    Box,
    Button,
    Card,
    Code,
    Collapse,
    Container,
    Divider,
    Flex,
    Group,
    Pagination,
    Select,
    SimpleGrid,
    Stack,
    Text,
    TextInput,
    Title,
    Tooltip,
} from '@mantine/core';
import {
    IconChevronDown,
    IconDownload,
    IconFilterOff,
    IconShieldCheck,
} from '@tabler/icons-react';
import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { PageProps } from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────────

interface AuditActor {
    id: string;
    display_name: string;
    email: string;
    photo_url?: string | null;
    role: string;
}

interface AuditLogEntry {
    id: string;
    action_type: string;
    entity_type: string;
    entity_id: string;
    entity_label: string | null;
    previous_state: Record<string, unknown> | null;
    new_state: Record<string, unknown> | null;
    reason: string | null;
    actor_ip: string | null;
    occurred_at: string;
    actor: AuditActor | null;
}

interface PaginatedLogs {
    data: AuditLogEntry[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
}

interface Stats {
    today_count: number;
    self_approval_count: number;
    role_change_count: number;
    most_active_name: string | null;
}

interface ActorOption {
    id: string;
    display_name: string;
    email: string;
}

interface AuditFilters {
    action_type: string;
    entity_type: string;
    actor_id: string;
    from: string;
    to: string;
}

interface Props extends PageProps {
    logs: PaginatedLogs;
    filters: AuditFilters;
    stats: Stats;
    actors: ActorOption[];
    isAdmin: boolean;
}

// ─── Badge colour map ─────────────────────────────────────────────────────────

function badgeColor(actionType: string): string {
    switch (actionType) {
        case 'self_approval': return 'orange';
        case 'measurement_approved':
        case 'station_revision_approved': return 'blue';
        case 'measurement_rejected':
        case 'station_revision_rejected': return 'red';
        case 'role_change': return 'yellow';
        case 'user_deleted':
        case 'record_deactivation': return 'gray';
        case 'document_uploaded': return 'green';
        case 'document_deleted': return 'pink';
        default: return 'gray';
    }
}

function initials(name: string): string {
    return name.trim().split(' ').slice(0, 2).map(w => w.charAt(0).toUpperCase()).join('');
}

// ─── State snapshot renderer ──────────────────────────────────────────────────

function StateBlock({ label, data }: { label: string; data: Record<string, unknown> | null }) {
    const { t } = useTranslation('audit');
    if (!data) {
        return (
            <Box>
                <Text size="xs" fw={600} c="dimmed" mb={4}>{label}</Text>
                <Text size="xs" c="dimmed">{t('detail.noState')}</Text>
            </Box>
        );
    }
    return (
        <Box>
            <Text size="xs" fw={600} c="dimmed" mb={4}>{label}</Text>
            <Code block style={{ fontSize: 11 }}>
                {JSON.stringify(data, null, 2)}
            </Code>
        </Box>
    );
}

// ─── Single log row ───────────────────────────────────────────────────────────

function LogRow({ log }: { log: AuditLogEntry }) {
    const { t } = useTranslation('audit');
    const [open, setOpen] = useState(false);
    const hasDetails = log.previous_state || log.new_state || log.reason || log.actor_ip;

    const actionLabel = t(`actions.${log.action_type}`, { defaultValue: log.action_type });
    const sentence = t(`sentences.${log.action_type}`, {
        entity: log.entity_label || log.entity_id,
        defaultValue: `${actionLabel}: ${log.entity_label || log.entity_id}`,
    });

    const occurredAt = new Date(log.occurred_at);
    const timeStr = occurredAt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    return (
        <Card withBorder radius="md" py="sm" px="md">
            <Group justify="space-between" wrap="nowrap">
                <Group gap="sm" wrap="nowrap" style={{ flex: 1, minWidth: 0 }}>
                    <Avatar
                        src={log.actor?.photo_url ?? undefined}
                        radius="xl"
                        size="sm"
                        color="blue"
                    >
                        {log.actor ? initials(log.actor.display_name) : '?'}
                    </Avatar>
                    <Stack gap={2} style={{ flex: 1, minWidth: 0 }}>
                        <Group gap="xs" wrap="nowrap">
                            <Badge color={badgeColor(log.action_type)} size="xs" variant="light" style={{ flexShrink: 0 }}>
                                {actionLabel}
                            </Badge>
                            <Text size="xs" c="dimmed" style={{ flexShrink: 0 }}>{timeStr}</Text>
                        </Group>
                        <Group gap={4} wrap="nowrap">
                            <Text size="sm" fw={500} style={{ flexShrink: 0 }}>
                                {log.actor?.display_name ?? 'Unknown'}
                            </Text>
                            <Text size="sm" c="dimmed" style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                                — {sentence}
                            </Text>
                        </Group>
                    </Stack>
                </Group>
                {hasDetails && (
                    <Tooltip label={open ? t('detail.collapse') : t('detail.expand')}>
                        <ActionIcon
                            variant="subtle"
                            size="sm"
                            onClick={() => setOpen(v => !v)}
                            style={{ flexShrink: 0 }}
                        >
                            <IconChevronDown size={14} style={{ transform: open ? 'rotate(180deg)' : undefined, transition: 'transform 200ms' }} />
                        </ActionIcon>
                    </Tooltip>
                )}
            </Group>

            <Collapse in={open}>
                <Divider my="xs" />
                <SimpleGrid cols={{ base: 1, sm: 2 }} spacing="xs">
                    <StateBlock label={t('detail.before')} data={log.previous_state} />
                    <StateBlock label={t('detail.after')} data={log.new_state} />
                </SimpleGrid>
                {log.reason && (
                    <Box mt="xs">
                        <Text size="xs" fw={600} c="dimmed">{t('detail.reason')}</Text>
                        <Text size="sm">{log.reason}</Text>
                    </Box>
                )}
                {log.actor_ip && (
                    <Box mt="xs">
                        <Text size="xs" fw={600} c="dimmed">{t('detail.ip')}</Text>
                        <Text size="sm" ff="monospace">{log.actor_ip}</Text>
                    </Box>
                )}
            </Collapse>
        </Card>
    );
}

// ─── Main page ────────────────────────────────────────────────────────────────

const ACTION_TYPES = [
    'self_approval',
    'measurement_approved',
    'measurement_rejected',
    'station_revision_approved',
    'station_revision_rejected',
    'role_change',
    'user_deleted',
    'record_deactivation',
    'document_uploaded',
    'document_deleted',
];

const ENTITY_TYPES = ['Measurement', 'StationRevision', 'User', 'Station', 'Document'];

export default function AuditIndex({ logs, filters, stats, actors, isAdmin }: Props) {
    const { t } = useTranslation('audit');

    // Local filter state
    const [actionType, setActionType] = useState(filters.action_type || '');
    const [entityType, setEntityType] = useState(filters.entity_type || '');
    const [actorId, setActorId] = useState(filters.actor_id || '');
    const [fromDate, setFromDate] = useState(filters.from || '');
    const [toDate, setToDate] = useState(filters.to || '');

    function applyFilters(overrides: Partial<Record<string, string>> = {}) {
        const params: Record<string, string> = {};
        if (actionType) params.action_type = actionType;
        if (entityType) params.entity_type = entityType;
        if (actorId) params.actor_id = actorId;
        if (fromDate) params.from = fromDate;
        if (toDate) params.to = toDate;
        Object.assign(params, overrides);

        router.get(route('audit.index'), params, { preserveState: true, replace: true });
    }

    function resetFilters() {
        setActionType('');
        setEntityType('');
        setActorId('');
        setFromDate('');
        setToDate('');
        router.get(route('audit.index'), {}, { preserveState: false });
    }

    function handlePage(page: number) {
        const params: Record<string, string> = { page: String(page) };
        if (actionType) params.action_type = actionType;
        if (entityType) params.entity_type = entityType;
        if (actorId) params.actor_id = actorId;
        if (fromDate) params.from = fromDate;
        if (toDate) params.to = toDate;
        router.get(route('audit.index'), params, { preserveState: true, replace: true });
    }

    // Group logs by date
    const grouped: { date: string; entries: AuditLogEntry[] }[] = [];
    for (const log of logs.data) {
        const dateKey = new Date(log.occurred_at).toLocaleDateString('en-GB', {
            weekday: 'short', day: 'numeric', month: 'long', year: 'numeric',
        });
        const last = grouped[grouped.length - 1];
        if (last && last.date === dateKey) {
            last.entries.push(log);
        } else {
            grouped.push({ date: dateKey, entries: [log] });
        }
    }

    const hasActiveFilters = !!(actionType || entityType || actorId || fromDate || toDate);    const actionOptions = [
        { value: '', label: t('filters.all') },
        ...ACTION_TYPES.map(a => ({ value: a, label: t(`actions.${a}`) })),
    ];

    const entityOptions = [
        { value: '', label: t('filters.allEntities') },
        ...ENTITY_TYPES.map(e => ({ value: e, label: t(`entities.${e}`, { defaultValue: e }) })),
    ];

    const actorOptions = [
        { value: '', label: t('filters.allActors') },
        ...actors.map(a => ({ value: a.id, label: `${a.display_name} (${a.email})` })),
    ];

    return (
        <AuthenticatedLayout>
            <Head title={t('title')} />

            <Container size="xl" py="xl">
                {/* Header */}
                <Group justify="space-between" mb="xl">
                    <Stack gap={2}>
                        <Group gap="xs">
                            <IconShieldCheck size={24} />
                            <Title order={2}>{t('title')}</Title>
                        </Group>
                        <Text c="dimmed" size="sm">{t('subtitle')}</Text>
                    </Stack>
                    {isAdmin && (
                        <Anchor
                            href={route('audit.export') + (hasActiveFilters ? '?' + new URLSearchParams({
                                ...(actionType && { action_type: actionType }),
                                ...(entityType && { entity_type: entityType }),
                                ...(actorId && { actor_id: actorId }),
                                ...(fromDate && { from: fromDate }),
                                ...(toDate && { to: toDate }),
                            }).toString() : '')}
                            underline="never"
                        >
                            <Button leftSection={<IconDownload size={16} />} variant="default">
                                {t('export')}
                            </Button>
                        </Anchor>
                    )}
                </Group>

                {/* Stats cards */}
                <SimpleGrid cols={{ base: 2, sm: 4 }} mb="xl" spacing="md">
                    <Card withBorder radius="md" py="md">
                        <Text size="xs" c="dimmed" tt="uppercase" fw={600}>{t('stats.today')}</Text>
                        <Text size="xl" fw={700} mt={4}>{stats.today_count}</Text>
                    </Card>
                    <Card withBorder radius="md" py="md">
                        <Text size="xs" c="dimmed" tt="uppercase" fw={600}>{t('stats.selfApprovals')}</Text>
                        <Text size="xl" fw={700} mt={4} c="orange">{stats.self_approval_count}</Text>
                    </Card>
                    <Card withBorder radius="md" py="md">
                        <Text size="xs" c="dimmed" tt="uppercase" fw={600}>{t('stats.roleChanges')}</Text>
                        <Text size="xl" fw={700} mt={4} c="yellow.7">{stats.role_change_count}</Text>
                    </Card>
                    <Card withBorder radius="md" py="md">
                        <Text size="xs" c="dimmed" tt="uppercase" fw={600}>{t('stats.mostActive')}</Text>
                        <Text size="sm" fw={700} mt={4} lineClamp={1}>{stats.most_active_name ?? '—'}</Text>
                    </Card>
                </SimpleGrid>

                {/* Filters */}
                <Card withBorder radius="md" mb="xl" p="md">
                    <SimpleGrid cols={{ base: 1, xs: 2, md: 5 }} spacing="sm">
                        <Select
                            label={t('filters.actionType')}
                            data={actionOptions}
                            value={actionType}
                            onChange={v => setActionType(v ?? '')}
                            clearable={false}
                        />
                        <Select
                            label={t('filters.entityType')}
                            data={entityOptions}
                            value={entityType}
                            onChange={v => setEntityType(v ?? '')}
                            clearable={false}
                        />
                        <Select
                            label={t('filters.actor')}
                            data={actorOptions}
                            value={actorId}
                            onChange={v => setActorId(v ?? '')}
                            clearable={false}
                            searchable
                        />
                        <TextInput
                            label={t('filters.from')}
                            type="date"
                            value={fromDate}
                            onChange={e => setFromDate(e.currentTarget.value)}
                        />
                        <TextInput
                            label={t('filters.to')}
                            type="date"
                            value={toDate}
                            onChange={e => setToDate(e.currentTarget.value)}
                        />
                    </SimpleGrid>
                    <Group mt="sm" justify="flex-end" gap="xs">
                        {hasActiveFilters && (
                            <Button
                                variant="subtle"
                                leftSection={<IconFilterOff size={14} />}
                                onClick={resetFilters}
                            >
                                {t('filters.reset')}
                            </Button>
                        )}
                        <Button onClick={() => applyFilters()}>
                            {t('filters.apply')}
                        </Button>                    </Group>
                </Card>

                {/* Log timeline */}
                {logs.data.length === 0 ? (
                    <Card withBorder radius="md" p="xl" ta="center">
                        <IconShieldCheck size={40} color="var(--mantine-color-gray-5)" />
                        <Text mt="sm" c="dimmed">
                            {hasActiveFilters ? t('empty') : t('noEvents')}
                        </Text>
                    </Card>
                ) : (
                    <Stack gap="lg">
                        {grouped.map(group => (
                            <Box key={group.date}>
                                <Group gap="xs" mb="sm">
                                    <Divider style={{ flex: 1 }} />
                                    <Text size="xs" fw={600} c="dimmed" tt="uppercase">{group.date}</Text>
                                    <Divider style={{ flex: 1 }} />
                                </Group>
                                <Stack gap="xs">
                                    {group.entries.map(log => (
                                        <LogRow key={log.id} log={log} />
                                    ))}
                                </Stack>
                            </Box>
                        ))}

                        {logs.last_page > 1 && (
                            <Flex justify="center" mt="md">
                                <Pagination
                                    total={logs.last_page}
                                    value={logs.current_page}
                                    onChange={handlePage}
                                />
                            </Flex>
                        )}
                    </Stack>
                )}
            </Container>
        </AuthenticatedLayout>
    );
}
