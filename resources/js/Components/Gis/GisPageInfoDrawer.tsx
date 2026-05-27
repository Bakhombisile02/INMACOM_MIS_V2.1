import {
    Badge,
    Button,
    Divider,
    Drawer,
    Group,
    ScrollArea,
    Stack,
    Text,
    Title,
} from '@mantine/core';
import { IconChartLine } from '@tabler/icons-react';
import { useTranslation } from 'react-i18next';
import type { GisStationData } from '@/Components/Dashboard/GisMap';

interface GisPageInfoDrawerProps {
    station: GisStationData | null;
    onClose: () => void;
    onViewDetails: () => void;
    withinPortal?: boolean;
    zIndex?: number;
}

function DetailRow({ label, value }: { label: string; value?: string | null }) {
    if (!value) return null;
    return (
        <Group justify="space-between" gap="xs" wrap="nowrap" align="flex-start">
            <Text size="sm" c="dimmed" style={{ flexShrink: 0, minWidth: 110 }}>
                {label}
            </Text>
            <Text size="sm" ta="right" style={{ wordBreak: 'break-word' }}>
                {value}
            </Text>
        </Group>
    );
}

export default function GisPageInfoDrawer({
    station,
    onClose,
    onViewDetails,
    withinPortal = true,
    zIndex = 2000,
}: GisPageInfoDrawerProps) {
    const { t } = useTranslation('stations');

    return (
        <Drawer
            opened={station !== null}
            onClose={onClose}
            position="right"
            size={400}
            zIndex={zIndex}
            withinPortal={withinPortal}
            title={
                <Title order={5} lineClamp={1}>
                    {station?.name ?? ''}
                </Title>
            }
            scrollAreaComponent={ScrollArea.Autosize}
            padding="md"
        >
            {station && (
                <Stack gap="md">
                    {/* Status badges */}
                    <Group gap="xs" wrap="wrap" align="center">
                        <Badge variant="outline" color="gray" size="sm" style={{ fontFamily: 'monospace' }}>
                            {station.code}
                        </Badge>
                        <Badge
                            color={station.status === 'active' ? 'green' : 'red'}
                            variant="light"
                            size="sm"
                        >
                            {station.status === 'active'
                                ? t('status.active')
                                : t('status.inactive')}
                        </Badge>
                        {station.is_real_time && (
                            <Badge color="teal" variant="light" size="sm">
                                {t('detail.realtime')}
                            </Badge>
                        )}
                    </Group>

                    <Divider />

                    {/* Measurement data from popupData */}
                    <Stack gap={8}>
                        {station.popupData.map((row, i) => (
                            <Group key={i} justify="space-between" gap="xs" wrap="nowrap" align="flex-start">
                                <Text size="sm" c="dimmed" style={{ flexShrink: 0, minWidth: 110 }}>
                                    {row.label}
                                </Text>
                                <Text
                                    size="sm"
                                    fw={row.color ? 700 : undefined}
                                    ta="right"
                                    style={{
                                        wordBreak: 'break-word',
                                        color: row.color ?? undefined,
                                    }}
                                >
                                    {row.value}
                                </Text>
                            </Group>
                        ))}
                    </Stack>

                    <Divider />

                    {/* Station metadata */}
                    <Stack gap={6}>
                        <DetailRow label={t('detail.riverBasin')} value={station.river_basin} />
                        <DetailRow label={t('detail.ownerOrg')} value={station.owner_org} />
                        <DetailRow label={t('detail.telemetrySystem')} value={station.telemetry_system} />
                        {station.latitude != null && (
                            <DetailRow
                                label={t('detail.latitude')}
                                value={station.latitude.toFixed(6)}
                            />
                        )}
                        {station.longitude != null && (
                            <DetailRow
                                label={t('detail.longitude')}
                                value={station.longitude.toFixed(6)}
                            />
                        )}
                        {station.summary && (
                            <>
                                <Divider mt="xs" />
                                <Text size="xs" c="dimmed" mt={4}>{t('detail.summary')}</Text>
                                <Text size="sm">
                                    {station.summary
                                        .replace(/\s*\[Inferred Metadata[^\]]*\]/, '')
                                        .trim()}
                                </Text>
                            </>
                        )}
                    </Stack>

                    <Divider />

                    {/* Primary CTA */}
                    <Button
                        leftSection={<IconChartLine size={16} />}
                        fullWidth
                        onClick={onViewDetails}
                    >
                        View Full Details
                    </Button>
                </Stack>
            )}
        </Drawer>
    );
}
