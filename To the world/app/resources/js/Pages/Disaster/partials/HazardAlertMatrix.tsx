import React, { useState } from 'react';
import {
    Box,
    Group,
    Popover,
    ScrollArea,
    Stack,
    Table,
    Text,
} from '@mantine/core';
import { useTranslation } from 'react-i18next';

export interface HazardType {
    code: string;
    name: string;
}

export interface ManagementArea {
    id: string;
    code: string;
    name: string;
    basin: string;
    country: string | null;
}

export interface StatusLevel {
    hazard_code: string;
    level_code: string;
    name: string;
    severity: number;
    color: string | null;
    description: string | null;
    actions_required: string | null;
}

export interface CurrentStatus {
    hazard_code: string;
    area_id: string;
    level_code: string;
    score: number | null;
    calculated_at: string | null;
    calculation_notes: string | null;
    level_name: string;
    color: string | null;
    severity: number;
    area_name: string;
}

interface HazardAlertMatrixProps {
    hazardTypes: HazardType[];
    areas: ManagementArea[];
    currentStatuses: CurrentStatus[];
    statusLevels: StatusLevel[];
}

function severityFallbackColor(severity: number): string {
    if (severity <= 1) return '#22c55e';
    if (severity === 2) return '#eab308';
    if (severity === 3) return '#f97316';
    return '#ef4444';
}

function resolveColor(status: CurrentStatus): string {
    return status.color ?? severityFallbackColor(status.severity);
}

function formatDateTime(value: string | null): string {
    if (!value) return '—';
    try {
        return new Date(value).toLocaleString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return value;
    }
}

function Legend({ statusLevels }: { statusLevels: StatusLevel[] }) {
    const { t } = useTranslation('disaster');

    // Deduplicate by level_code (across hazard types), keep unique severity+name pairs
    const seen = new Set<string>();
    const uniqueLevels = statusLevels.filter((lvl) => {
        const key = `${lvl.level_code}|${lvl.severity}`;
        if (seen.has(key)) return false;
        seen.add(key);
        return true;
    }).sort((a, b) => a.severity - b.severity);

    if (uniqueLevels.length === 0) return null;

    return (
        <Group gap="md" mb="sm" wrap="wrap">
            {uniqueLevels.map((lvl) => (
                <Group key={`${lvl.hazard_code}|${lvl.level_code}`} gap={6}>
                    <Box
                        style={{
                            width: 14,
                            height: 14,
                            borderRadius: 3,
                            backgroundColor: lvl.color ?? severityFallbackColor(lvl.severity),
                            flexShrink: 0,
                        }}
                    />
                    <Text size="xs" c="dimmed">
                        {lvl.name}
                    </Text>
                </Group>
            ))}
        </Group>
    );
}

interface StatusCellProps {
    status: CurrentStatus | undefined;
}

function StatusCell({ status }: StatusCellProps) {
    const { t } = useTranslation('disaster');
    const [opened, setOpened] = useState(false);

    if (!status) {
        return (
            <Table.Td style={{ textAlign: 'center', minWidth: 90 }}>
                <Text size="xs" c="dimmed">—</Text>
            </Table.Td>
        );
    }

    const color = resolveColor(status);

    return (
        <Table.Td style={{ padding: 4, minWidth: 90 }}>
            <Popover
                opened={opened}
                onClose={() => setOpened(false)}
                position="bottom"
                withArrow
                shadow="md"
                width={240}
            >
                <Popover.Target>
                    <Box
                        onClick={() => setOpened((o) => !o)}
                        style={{
                            backgroundColor: color,
                            borderRadius: 4,
                            padding: '4px 8px',
                            cursor: 'pointer',
                            textAlign: 'center',
                        }}
                    >
                        <Text size="xs" fw={600} c="white" style={{ textShadow: '0 1px 2px rgba(0,0,0,0.4)' }}>
                            {status.level_name}
                        </Text>
                    </Box>
                </Popover.Target>
                <Popover.Dropdown>
                    <Stack gap="xs">
                        <Group gap={6}>
                            <Box
                                style={{
                                    width: 10,
                                    height: 10,
                                    borderRadius: 2,
                                    backgroundColor: color,
                                    flexShrink: 0,
                                }}
                            />
                            <Text size="sm" fw={600}>{status.level_name}</Text>
                        </Group>
                        {status.score != null && (
                            <Group gap="xs">
                                <Text size="xs" c="dimmed">{t('matrix.score')}:</Text>
                                <Text size="xs">{status.score.toFixed(2)}</Text>
                            </Group>
                        )}
                        <Group gap="xs">
                            <Text size="xs" c="dimmed">{t('matrix.calculatedAt')}:</Text>
                            <Text size="xs">{formatDateTime(status.calculated_at)}</Text>
                        </Group>
                        <Box>
                            <Text size="xs" c="dimmed" mb={2}>{t('matrix.notes')}:</Text>
                            <Text size="xs">
                                {status.calculation_notes ?? t('matrix.noNotes')}
                            </Text>
                        </Box>
                    </Stack>
                </Popover.Dropdown>
            </Popover>
        </Table.Td>
    );
}

export default function HazardAlertMatrix({
    hazardTypes,
    areas,
    currentStatuses,
    statusLevels,
}: HazardAlertMatrixProps) {
    const { t } = useTranslation('disaster');

    if (hazardTypes.length === 0) {
        return (
            <Text size="sm" c="dimmed" ta="center" py="xl">
                {t('matrix.noHazardTypes')}
            </Text>
        );
    }

    if (areas.length === 0) {
        return (
            <Text size="sm" c="dimmed" ta="center" py="xl">
                {t('matrix.noAreas')}
            </Text>
        );
    }

    // Build a lookup map: `${hazard_code}|${area_id}` → CurrentStatus
    const statusMap = new Map<string, CurrentStatus>();
    for (const s of currentStatuses) {
        statusMap.set(`${s.hazard_code}|${s.area_id}`, s);
    }

    return (
        <Box>
            <Legend statusLevels={statusLevels} />
            <ScrollArea>
                <Table withTableBorder withColumnBorders style={{ tableLayout: 'fixed' }}>
                    <Table.Thead>
                        <Table.Tr>
                            <Table.Th
                                style={{
                                    minWidth: 140,
                                    width: 140,
                                    position: 'sticky',
                                    left: 0,
                                    zIndex: 1,
                                    backgroundColor: 'var(--mantine-color-body)',
                                }}
                            >
                                <Text size="xs" fw={600} c="dimmed">Hazard / Area</Text>
                            </Table.Th>
                            {areas.map((area) => (
                                <Table.Th
                                    key={area.id}
                                    style={{ minWidth: 100, textAlign: 'center' }}
                                >
                                    <Stack gap={0}>
                                        <Text size="xs" fw={600} lineClamp={2}>
                                            {area.name}
                                        </Text>
                                        {area.country && (
                                            <Text size="xs" c="dimmed">{area.country}</Text>
                                        )}
                                    </Stack>
                                </Table.Th>
                            ))}
                        </Table.Tr>
                    </Table.Thead>
                    <Table.Tbody>
                        {hazardTypes.map((hazard) => (
                            <Table.Tr key={hazard.code}>
                                <Table.Td
                                    style={{
                                        position: 'sticky',
                                        left: 0,
                                        zIndex: 1,
                                        backgroundColor: 'var(--mantine-color-body)',
                                    }}
                                >
                                    <Text size="sm" fw={500}>{hazard.name}</Text>
                                    <Text size="xs" c="dimmed">{hazard.code}</Text>
                                </Table.Td>
                                {areas.map((area) => (
                                    <StatusCell
                                        key={area.id}
                                        status={statusMap.get(`${hazard.code}|${area.id}`)}
                                    />
                                ))}
                            </Table.Tr>
                        ))}
                    </Table.Tbody>
                </Table>
            </ScrollArea>
        </Box>
    );
}
