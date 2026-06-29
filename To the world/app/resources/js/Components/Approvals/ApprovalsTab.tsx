import { useMemo, useState } from 'react';
import {
    Accordion,
    Badge,
    Button,
    Card,
    Group,
    Stack,
    Text,
    Textarea,
    Title,
    Table,
    ScrollArea,
} from '@mantine/core';
import { IconCheck, IconX, IconClipboardList } from '@tabler/icons-react';
import { notifications } from '@mantine/notifications';
import { router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import CommentThread from './CommentThread';
import type { StationRevisionRecord } from '@/types';

export interface PendingMeasurementRow {
    id: string;
    station_name: string;
    measurement_type: string;
    value: number | string;
    unit?: string | null;
    date: string;
    submitted_by_name?: string | null;
    submitted_at?: string | null;
}

export interface PendingStationRevisionRow extends StationRevisionRecord {
    station_name?: string | null;
    station_code?: string | null;
    submitted_by_name?: string | null;
}

type Props = {
    /** Pending measurements (omit on Stations page). */
    measurements?: PendingMeasurementRow[];
    /** Pending station revisions (omit on GIS pages). */
    stationRevisions?: PendingStationRevisionRow[];
    /** Approve endpoint name builders. */
    measurementApproveUrl?: (id: string) => string;
    measurementRejectUrl?: (id: string) => string;
    currentUserId?: string | null;
};

export default function ApprovalsTab({
    measurements = [],
    stationRevisions = [],
    measurementApproveUrl = (id) => `/measurements/${id}/approve`,
    measurementRejectUrl = (id) => `/measurements/${id}/reject`,
    currentUserId = null,
}: Props) {
    const { t } = useTranslation('approvals');
    const hasMeasurements = measurements.length > 0;
    const hasRevisions = stationRevisions.length > 0;

    if (!hasMeasurements && !hasRevisions) {
        return (
            <Card withBorder>
                <Group gap="xs" c="dimmed">
                    <IconClipboardList size={18} />
                    <Text>{t('queue.noPending')}</Text>
                </Group>
            </Card>
        );
    }

    return (
        <Stack gap="lg">
            {hasMeasurements && (
                <Stack gap="sm">
                    <Title order={4}>{t('queue.pendingMeasurements')}</Title>
                    <PendingMeasurementsTable
                        rows={measurements}
                        approveUrl={measurementApproveUrl}
                        rejectUrl={measurementRejectUrl}
                        currentUserId={currentUserId}
                    />
                </Stack>
            )}
            {hasRevisions && (
                <Stack gap="sm">
                    <Title order={4}>{t('queue.pendingStationEdits')}</Title>
                    <PendingRevisionsList rows={stationRevisions} currentUserId={currentUserId} />
                </Stack>
            )}
        </Stack>
    );
}

function PendingMeasurementsTable({
    rows,
    approveUrl,
    rejectUrl,
    currentUserId,
}: {
    rows: PendingMeasurementRow[];
    approveUrl: (id: string) => string;
    rejectUrl: (id: string) => string;
    currentUserId: string | null;
}) {
    const { t } = useTranslation('approvals');
    const [busy, setBusy] = useState<string | null>(null);
    const [expanded, setExpanded] = useState<string | null>(null);

    const submit = (id: string, kind: 'approve' | 'reject', notes?: string) => {
        setBusy(`${id}:${kind}`);
        const url = kind === 'approve' ? approveUrl(id) : rejectUrl(id);
        router.post(
            url,
            kind === 'reject' ? { review_notes: notes ?? '' } : {},
            {
                preserveScroll: true,
                onError: () =>
                    notifications.show({
                        title: 'Action failed',
                        message: `Could not ${kind} measurement`,
                        color: 'red',
                        autoClose: false,
                        withCloseButton: true,
                    }),
                onFinish: () => setBusy(null),
            },
        );
    };

    return (
        <Card withBorder p={0}>
            <ScrollArea>
                <Table verticalSpacing="sm" highlightOnHover>
                    <Table.Thead>
                        <Table.Tr>
                            <Table.Th>Station</Table.Th>
                            <Table.Th>Type</Table.Th>
                            <Table.Th>Value</Table.Th>
                            <Table.Th>Date</Table.Th>
                            <Table.Th>{t('queue.submittedBy')}</Table.Th>
                            <Table.Th>Actions</Table.Th>
                        </Table.Tr>
                    </Table.Thead>
                    <Table.Tbody>
                        {rows.map((r) => (
                            <RowWithExpand
                                key={r.id}
                                row={r}
                                expanded={expanded === r.id}
                                onToggle={() => setExpanded((cur) => (cur === r.id ? null : r.id))}
                                busy={busy}
                                onApprove={() => submit(r.id, 'approve')}
                                onReject={(notes) => submit(r.id, 'reject', notes)}
                                currentUserId={currentUserId}
                            />
                        ))}
                    </Table.Tbody>
                </Table>
            </ScrollArea>
        </Card>
    );
}

function RowWithExpand({
    row,
    expanded,
    onToggle,
    busy,
    onApprove,
    onReject,
    currentUserId,
}: {
    row: PendingMeasurementRow;
    expanded: boolean;
    onToggle: () => void;
    busy: string | null;
    onApprove: () => void;
    onReject: (notes: string) => void;
    currentUserId: string | null;
}) {
    const { t } = useTranslation('approvals');
    const [notes, setNotes] = useState('');

    return (
        <>
            <Table.Tr style={{ cursor: 'pointer' }} onClick={onToggle}>
                <Table.Td>{row.station_name}</Table.Td>
                <Table.Td>
                    <Badge variant="light">{row.measurement_type}</Badge>
                </Table.Td>
                <Table.Td>
                    {row.value} {row.unit ?? ''}
                </Table.Td>
                <Table.Td>{row.date}</Table.Td>
                <Table.Td>{row.submitted_by_name ?? '—'}</Table.Td>
                <Table.Td onClick={(e) => e.stopPropagation()}>
                    <Group gap="xs">
                        <Button
                            size="xs"
                            color="green"
                            leftSection={<IconCheck size={14} />}
                            loading={busy === `${row.id}:approve`}
                            onClick={onApprove}
                        >
                            {t('actions.approve')}
                        </Button>
                        <Button
                            size="xs"
                            color="red"
                            variant="light"
                            leftSection={<IconX size={14} />}
                            onClick={onToggle}
                        >
                            {t('actions.reject')}
                        </Button>
                    </Group>
                </Table.Td>
            </Table.Tr>
            {expanded && (
                <Table.Tr>
                    <Table.Td colSpan={6}>
                        <Stack gap="sm" p="sm">
                            <Textarea
                                label={t('queue.reviewNotes')}
                                value={notes}
                                onChange={(e) => setNotes(e.currentTarget.value)}
                                minRows={2}
                                autosize
                            />
                            <Group justify="flex-end">
                                <Button
                                    color="red"
                                    leftSection={<IconX size={14} />}
                                    loading={busy === `${row.id}:reject`}
                                    onClick={() => onReject(notes)}
                                >
                                    {t('actions.reject')}
                                </Button>
                            </Group>
                            <Title order={6}>{t('comments.title')}</Title>
                            <CommentThread
                                commentableType="measurement"
                                commentableId={row.id}
                                currentUserId={currentUserId}
                                compact
                            />
                        </Stack>
                    </Table.Td>
                </Table.Tr>
            )}
        </>
    );
}

function PendingRevisionsList({
    rows,
    currentUserId,
}: {
    rows: PendingStationRevisionRow[];
    currentUserId: string | null;
}) {
    const { t } = useTranslation('approvals');
    const [busy, setBusy] = useState<string | null>(null);
    const [notes, setNotes] = useState<Record<string, string>>({});

    const submit = (id: string, kind: 'approve' | 'reject') => {
        setBusy(`${id}:${kind}`);
        router.post(
            `/station-revisions/${id}/${kind}`,
            kind === 'reject' ? { review_notes: notes[id] ?? '' } : {},
            {
                preserveScroll: true,
                onError: () =>
                    notifications.show({
                        title: 'Action failed',
                        message: `Could not ${kind} revision`,
                        color: 'red',
                        autoClose: false,
                        withCloseButton: true,
                    }),
                onFinish: () => setBusy(null),
            },
        );
    };

    return (
        <Accordion variant="separated" multiple>
            {rows.map((r) => {
                const isCreate = r.change_type === 'create';
                const fieldCount = Object.keys(r.proposed_changes ?? {}).length;
                return (
                <Accordion.Item key={r.id} value={r.id}>
                    <Accordion.Control>
                        <Group justify="space-between" wrap="nowrap">
                            <div>
                                <Group gap="xs">
                                    <Text fw={600}>{r.station_name ?? r.station_id ?? '—'}</Text>
                                    {isCreate && (
                                        <Badge color="blue" variant="filled" size="sm">
                                            {t('queue.newStation', { defaultValue: 'New station' })}
                                        </Badge>
                                    )}
                                </Group>
                                <Text size="xs" c="dimmed">
                                    {r.station_code ? `${r.station_code} · ` : ''}
                                    {t('queue.submittedBy')}: {r.submitted_by_name ?? '—'} ·{' '}
                                    {new Date(r.created_at).toLocaleString()}
                                </Text>
                            </div>
                            <Badge color="yellow" variant="light">
                                {fieldCount} {t('queue.field').toLowerCase()}
                                {fieldCount === 1 ? '' : 's'}
                            </Badge>
                        </Group>
                    </Accordion.Control>
                    <Accordion.Panel>
                        <Stack gap="sm">
                            <Table withTableBorder withColumnBorders>
                                <Table.Thead>
                                    <Table.Tr>
                                        <Table.Th>{t('queue.field')}</Table.Th>
                                        <Table.Th>{isCreate ? t('queue.proposedValue') : t('queue.currentValue')}</Table.Th>
                                        {!isCreate && <Table.Th>{t('queue.proposedValue')}</Table.Th>}
                                    </Table.Tr>
                                </Table.Thead>
                                <Table.Tbody>
                                    {Object.entries(r.proposed_changes ?? {}).map(([field, change]) => {
                                        // For `create` revisions, proposed_changes is a flat payload.
                                        // For `update`/`delete` revisions, each value is { from, to }.
                                        const isDiff =
                                            change !== null &&
                                            typeof change === 'object' &&
                                            'to' in (change as Record<string, unknown>);
                                        return (
                                        <Table.Tr key={field}>
                                            <Table.Td>
                                                <Badge variant="default">{field}</Badge>
                                            </Table.Td>
                                            <Table.Td>
                                                <code style={{ color: isCreate ? 'var(--mantine-color-green-7)' : undefined }}>
                                                    {formatVal(isDiff ? (change as { from: unknown }).from : change)}
                                                </code>
                                            </Table.Td>
                                            {!isCreate && (
                                                <Table.Td>
                                                    <code style={{ color: 'var(--mantine-color-green-7)' }}>
                                                        {formatVal(isDiff ? (change as { to: unknown }).to : change)}
                                                    </code>
                                                </Table.Td>
                                            )}
                                        </Table.Tr>
                                        );
                                    })}
                                </Table.Tbody>
                            </Table>
                            <Textarea
                                label={t('queue.reviewNotes')}
                                value={notes[r.id] ?? ''}
                                onChange={(e) => setNotes((n) => ({ ...n, [r.id]: e.currentTarget.value }))}
                                minRows={2}
                                autosize
                            />
                            <Group justify="flex-end">
                                <Button
                                    color="red"
                                    variant="light"
                                    leftSection={<IconX size={14} />}
                                    loading={busy === `${r.id}:reject`}
                                    onClick={() => submit(r.id, 'reject')}
                                >
                                    {t('actions.reject')}
                                </Button>
                                <Button
                                    color="green"
                                    leftSection={<IconCheck size={14} />}
                                    loading={busy === `${r.id}:approve`}
                                    onClick={() => submit(r.id, 'approve')}
                                >
                                    {t('actions.approve')}
                                </Button>
                            </Group>
                            <Title order={6}>{t('comments.title')}</Title>
                            <CommentThread
                                commentableType="station_revision"
                                commentableId={r.id}
                                currentUserId={currentUserId}
                                compact
                            />
                        </Stack>
                    </Accordion.Panel>
                </Accordion.Item>
                );
            })}
        </Accordion>
    );
}

function formatVal(v: unknown): string {
    if (v === null || v === undefined) return '—';
    if (typeof v === 'boolean') return v ? 'true' : 'false';
    if (typeof v === 'object') return JSON.stringify(v);
    return String(v);
}

// Suppress unused-imports warning kept for future use
void useMemo;
