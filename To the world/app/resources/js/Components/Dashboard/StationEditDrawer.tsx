import { useEffect } from 'react';
import { Link, router } from '@inertiajs/react';
import {
    Badge,
    Button,
    Divider,
    Drawer,
    Group,
    NumberInput,
    ScrollArea,
    Select,
    Stack,
    Switch,
    Text,
    Textarea,
    TextInput,
    Title,
} from '@mantine/core';
import { useForm } from '@mantine/form';
import { notifications } from '@mantine/notifications';
import { IconAlertTriangle, IconCheck, IconExternalLink } from '@tabler/icons-react';
import { useTranslation } from 'react-i18next';
import type { StationMapMarker } from './MultiStationMap';

const COUNTRIES = ['Mozambique', 'South Africa', 'Eswatini'];
const CATEGORIES = ['river_gauge', 'dam', 'borehole', 'rainfall_station', 'lake', 'wetland', 'other'];
const WATER_SOURCES = ['surface', 'ground', 'atmospheric'];
const WATER_BODY_TYPES = ['river', 'dam', 'borehole', 'lake', 'wetland'];

interface StationEditDrawerProps {
    station: StationMapMarker | null;
    onClose: () => void;
    canManage: boolean;
}

export default function StationEditDrawer({ station, onClose, canManage }: StationEditDrawerProps) {
    const { t } = useTranslation('stations');

    const form = useForm({
        initialValues: {
            code: '',
            name: '',
            country: null as string | null,
            category: '',
            water_source: '',
            water_body_type: '',
            river_basin: '',
            owner_org: '',
            telemetry_system: '',
            gauge_code: '',
            summary: '',
            latitude: null as number | null,
            longitude: null as number | null,
            is_active: true,
            is_real_time: false,
        },
    });

    useEffect(() => {
        if (station) {
            form.setValues({
                code: station.code ?? '',
                name: station.name ?? '',
                country: station.country ?? null,
                category: station.category ?? '',
                water_source: station.water_source ?? '',
                water_body_type: station.water_body_type ?? '',
                river_basin: station.river_basin ?? '',
                owner_org: station.owner_org ?? '',
                telemetry_system: station.telemetry_system ?? '',
                gauge_code: station.gauge_code ?? '',
                summary: station.summary ?? '',
                latitude: station.latitude ?? null,
                longitude: station.longitude ?? null,
                is_active: station.is_active ?? true,
                is_real_time: station.is_real_time ?? false,
            });
            form.clearErrors();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [station?.id]);

    const handleSubmit = (values: typeof form.values) => {
        if (!station) return;
        router.patch(route('stations.update', station.id), values, {
            onSuccess: () => {
                notifications.show({
                    color: 'green',
                    icon: <IconCheck size={16} />,
                    message: t('form.saved'),
                    autoClose: 5000,
                });
                onClose();
            },
            onError: (errors) => {
                form.setErrors(errors);
                notifications.show({
                    color: 'red',
                    icon: <IconAlertTriangle size={16} />,
                    message: t('form.error'),
                    autoClose: false,
                    withCloseButton: true,
                });
            },
        });
    };

    const isUnconfirmed =
        station?.summary?.includes('Unconfirmed') ||
        station?.summary?.includes('Unverified') ||
        station?.summary?.includes('Inferred');

    return (
        <Drawer
            opened={station !== null}
            onClose={onClose}
            position="right"
            size={460}
            withinPortal={false}
            zIndex={5000}
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
                    {/* Header badges + full-detail link */}
                    <Group gap="xs" wrap="wrap" align="center">
                        <Badge variant="outline" color="gray" size="sm" style={{ fontFamily: 'monospace' }}>
                            {station.code}
                        </Badge>
                        <Badge
                            color={station.status === 'active' ? 'green' : 'red'}
                            variant="light"
                            size="sm"
                        >
                            {station.status === 'active' ? t('status.active') : t('status.inactive')}
                        </Badge>
                        {isUnconfirmed && (
                            <Badge color="yellow" variant="light" size="sm">
                                Unconfirmed
                            </Badge>
                        )}
                        {station.is_real_time && (
                            <Badge color="teal" variant="light" size="sm">
                                {t('detail.realtime')}
                            </Badge>
                        )}
                        {station.show_url && (
                            <Button
                                component={Link}
                                href={station.show_url}
                                variant="subtle"
                                size="xs"
                                rightSection={<IconExternalLink size={12} />}
                                style={{ marginLeft: 'auto' }}
                            >
                                {t('actions.viewDetails')}
                            </Button>
                        )}
                    </Group>

                    <Divider />

                    {canManage ? (
                        <form onSubmit={form.onSubmit(handleSubmit)}>
                            <Stack gap="sm">
                                <TextInput
                                    label={t('form.code')}
                                    required
                                    {...form.getInputProps('code')}
                                />
                                <TextInput
                                    label={t('form.name')}
                                    required
                                    {...form.getInputProps('name')}
                                />
                                <Select
                                    label={t('form.country')}
                                    data={COUNTRIES}
                                    clearable
                                    {...form.getInputProps('country')}
                                />
                                <Select
                                    label={t('form.category')}
                                    data={CATEGORIES}
                                    required
                                    {...form.getInputProps('category')}
                                />
                                <Select
                                    label={t('form.waterSource')}
                                    data={WATER_SOURCES}
                                    required
                                    {...form.getInputProps('water_source')}
                                />
                                <Select
                                    label={t('form.waterBodyType')}
                                    data={WATER_BODY_TYPES}
                                    required
                                    {...form.getInputProps('water_body_type')}
                                />
                                <TextInput
                                    label={t('form.riverBasin')}
                                    {...form.getInputProps('river_basin')}
                                />
                                <TextInput
                                    label={t('form.ownerOrg')}
                                    {...form.getInputProps('owner_org')}
                                />
                                <TextInput
                                    label={t('form.telemetrySystem')}
                                    {...form.getInputProps('telemetry_system')}
                                />
                                <TextInput
                                    label={t('form.gaugeCode')}
                                    {...form.getInputProps('gauge_code')}
                                />
                                <Group grow gap="xs">
                                    <NumberInput
                                        label={t('form.latitude')}
                                        decimalScale={6}
                                        min={-90}
                                        max={90}
                                        {...form.getInputProps('latitude')}
                                    />
                                    <NumberInput
                                        label={t('form.longitude')}
                                        decimalScale={6}
                                        min={-180}
                                        max={180}
                                        {...form.getInputProps('longitude')}
                                    />
                                </Group>
                                <Textarea
                                    label={t('form.summary')}
                                    rows={3}
                                    autosize
                                    minRows={2}
                                    maxRows={5}
                                    {...form.getInputProps('summary')}
                                />
                                <Divider />
                                <Group gap="xl">
                                    <Switch
                                        label={t('form.isActive')}
                                        {...form.getInputProps('is_active', { type: 'checkbox' })}
                                    />
                                    <Switch
                                        label={t('form.isRealTime')}
                                        {...form.getInputProps('is_real_time', { type: 'checkbox' })}
                                    />
                                </Group>
                                <Group justify="flex-end" mt="xs">
                                    <Button variant="default" onClick={onClose} type="button">
                                        {t('form.cancel')}
                                    </Button>
                                    <Button type="submit">
                                        {t('form.save')}
                                    </Button>
                                </Group>
                            </Stack>
                        </form>
                    ) : (
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
                                    {station.summary.includes('Inferred') && (
                                        <Badge color="yellow" variant="light" size="sm">
                                            {t('detail.inferredMetadata')}
                                        </Badge>
                                    )}
                                </>
                            )}
                        </Stack>
                    )}
                </Stack>
            )}
        </Drawer>
    );
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
