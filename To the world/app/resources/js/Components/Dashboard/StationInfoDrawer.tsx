import { Link } from '@inertiajs/react';
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
import { IconExternalLink } from '@tabler/icons-react';
import { useTranslation } from 'react-i18next';
import type { StationMapMarker } from './MultiStationMap';

interface StationInfoDrawerProps {
    station: StationMapMarker | null;
    onClose: () => void;
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

export default function StationInfoDrawer({ station, onClose }: StationInfoDrawerProps) {
    const { t } = useTranslation('stations');

    const isUnconfirmed =
        station?.summary?.includes('Unconfirmed') ||
        station?.summary?.includes('Unverified') ||
        station?.summary?.includes('Inferred');

    return (
        <Drawer
            opened={station !== null}
            onClose={onClose}
            position="right"
            size={400}
            zIndex={2000}
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
                            {t(`status.${station.status}`)}
                        </Badge>
                        {isUnconfirmed && (
                            <Badge color="yellow" variant="light" size="sm">
                                {t('detail.inferredMetadata')}
                            </Badge>
                        )}
                        {station.is_real_time && (
                            <Badge color="teal" variant="light" size="sm">
                                {t('detail.realtime')}
                            </Badge>
                        )}
                    </Group>

                    <Divider />

                    {/* Station details */}
                    <Stack gap={6}>
                        <DetailRow label={t('detail.country')} value={station.country} />
                        <DetailRow label={t('detail.category')} value={station.category} />
                        <DetailRow label={t('detail.waterSource')} value={station.water_source} />
                        <DetailRow label={t('detail.waterBodyType')} value={station.water_body_type} />
                        <DetailRow label={t('detail.riverBasin')} value={station.river_basin} />
                        <DetailRow label={t('detail.ownerOrg')} value={station.owner_org} />
                        <DetailRow label={t('detail.telemetrySystem')} value={station.telemetry_system} />
                        <DetailRow label={t('detail.gaugeCode')} value={station.gauge_code} />
                        {station.latitude != null && (
                            <DetailRow label={t('detail.latitude')} value={station.latitude.toFixed(6)} />
                        )}
                        {station.longitude != null && (
                            <DetailRow label={t('detail.longitude')} value={station.longitude.toFixed(6)} />
                        )}
                        {station.summary && (
                            <>
                                <Divider mt="xs" />
                                <Text size="xs" c="dimmed" mt={4}>{t('detail.summary')}</Text>
                                <Text size="sm">
                                    {station.summary.replace(/\s*\[Inferred Metadata[^\]]*\]/, '').trim()}
                                </Text>
                                {isUnconfirmed && (
                                    <Badge color="yellow" variant="light" size="sm">
                                        {t('detail.inferredMetadata')}
                                    </Badge>
                                )}
                            </>
                        )}
                    </Stack>

                    <Divider />

                    {/* Primary action */}
                    {station.show_url && (
                        <Button
                            component={Link}
                            href={station.show_url}
                            leftSection={<IconExternalLink size={16} />}
                            fullWidth
                        >
                            {t('actions.viewDetails')}
                        </Button>
                    )}
                </Stack>
            )}
        </Drawer>
    );
}
