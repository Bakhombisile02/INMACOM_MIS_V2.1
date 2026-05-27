import { useEffect } from 'react';
import { router } from '@inertiajs/react';
import {
    Button,
    Divider,
    Grid,
    Group,
    Modal,
    NumberInput,
    Select,
    Stack,
    Switch,
    Textarea,
    TextInput,
    Title,
} from '@mantine/core';
import { useForm } from '@mantine/form';
import { notifications } from '@mantine/notifications';
import { IconAlertTriangle, IconCheck } from '@tabler/icons-react';
import { useTranslation } from 'react-i18next';
import CoordPicker from '@/Components/Dashboard/CoordPicker';

export interface StationFormData {
    id: string;
    code: string;
    name: string;
    latitude?: number | null;
    longitude?: number | null;
    category: string;
    water_source: string;
    water_body_type: string;
    is_active: boolean;
    is_real_time: boolean;
    summary?: string | null;
    telemetry_system?: string | null;
    gauge_code?: string | null;
    owner_org?: string | null;
    country?: string | null;
    river_basin?: string | null;
}

interface StationFormModalProps {
    opened: boolean;
    onClose: () => void;
    station?: StationFormData;
}

const COUNTRIES = ['Mozambique', 'South Africa', 'Eswatini'];
const CATEGORIES = ['river_gauge', 'dam', 'borehole', 'rainfall_station', 'lake', 'wetland', 'other'];
const WATER_SOURCES = ['surface', 'ground', 'atmospheric'];
const WATER_BODY_TYPES = ['river', 'dam', 'borehole', 'lake', 'wetland'];

export default function StationFormModal({ opened, onClose, station }: StationFormModalProps) {
    const { t } = useTranslation('stations');
    const isEdit = !!station;

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

    // Reset form each time the modal opens (handles both create and edit)
    useEffect(() => {
        if (opened) {
            form.setValues({
                code: station?.code ?? '',
                name: station?.name ?? '',
                country: station?.country ?? null,
                category: station?.category ?? '',
                water_source: station?.water_source ?? '',
                water_body_type: station?.water_body_type ?? '',
                river_basin: station?.river_basin ?? '',
                owner_org: station?.owner_org ?? '',
                telemetry_system: station?.telemetry_system ?? '',
                gauge_code: station?.gauge_code ?? '',
                summary: station?.summary ?? '',
                latitude: station?.latitude ?? null,
                longitude: station?.longitude ?? null,
                is_active: station?.is_active ?? true,
                is_real_time: station?.is_real_time ?? false,
            });
            form.clearErrors();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [opened, station]);

    const handleSubmit = (values: typeof form.values) => {
        const onSuccess = () => {
            notifications.show({
                color: 'green',
                icon: <IconCheck size={16} />,
                message: t(isEdit ? 'form.saved' : 'form.created'),
                autoClose: 5000,
            });
            onClose();
        };

        const onError = (errors: Record<string, string>) => {
            form.setErrors(errors);
            notifications.show({
                color: 'red',
                icon: <IconAlertTriangle size={16} />,
                message: t('form.error'),
                autoClose: false,
                withCloseButton: true,
            });
        };

        if (isEdit) {
            router.patch(route('stations.update', station!.id), values, { onSuccess, onError });
        } else {
            router.post(route('stations.store'), values, { onSuccess, onError });
        }
    };

    return (
        <Modal
            opened={opened}
            onClose={onClose}
            title={<Title order={4}>{t(isEdit ? 'form.titleEdit' : 'form.titleCreate')}</Title>}
            size="lg"
        >
            <form onSubmit={form.onSubmit(handleSubmit)}>
                <Stack gap="sm">
                    <Grid gutter="sm">
                        <Grid.Col span={6}>
                            <TextInput
                                label={t('form.code')}
                                radius="md"
                                required
                                {...form.getInputProps('code')}
                            />
                        </Grid.Col>
                        <Grid.Col span={6}>
                            <Select
                                label={t('form.country')}
                                data={COUNTRIES}
                                clearable
                                {...form.getInputProps('country')}
                            />
                        </Grid.Col>
                    </Grid>

                    <TextInput
                        label={t('form.name')}
                        radius="md"
                        required
                        {...form.getInputProps('name')}
                    />

                    <Grid gutter="sm">
                        <Grid.Col span={6}>
                            <Select
                                label={t('form.category')}
                                data={CATEGORIES}
                                required
                                {...form.getInputProps('category')}
                            />
                        </Grid.Col>
                        <Grid.Col span={6}>
                            <Select
                                label={t('form.waterSource')}
                                data={WATER_SOURCES}
                                required
                                {...form.getInputProps('water_source')}
                            />
                        </Grid.Col>
                    </Grid>

                    <Grid gutter="sm">
                        <Grid.Col span={6}>
                            <Select
                                label={t('form.waterBodyType')}
                                data={WATER_BODY_TYPES}
                                required
                                {...form.getInputProps('water_body_type')}
                            />
                        </Grid.Col>
                        <Grid.Col span={6}>
                            <TextInput
                                label={t('form.riverBasin')}
                                radius="md"
                                {...form.getInputProps('river_basin')}
                            />
                        </Grid.Col>
                    </Grid>

                    <Grid gutter="sm">
                        <Grid.Col span={6}>
                            <TextInput
                                label={t('form.ownerOrg')}
                                radius="md"
                                {...form.getInputProps('owner_org')}
                            />
                        </Grid.Col>
                        <Grid.Col span={6}>
                            <TextInput
                                label={t('form.telemetrySystem')}
                                radius="md"
                                {...form.getInputProps('telemetry_system')}
                            />
                        </Grid.Col>
                    </Grid>

                    <TextInput
                        label={t('form.gaugeCode')}
                        radius="md"
                        {...form.getInputProps('gauge_code')}
                    />

                    <Textarea
                        label={t('form.summary')}
                        radius="md"
                        rows={3}
                        {...form.getInputProps('summary')}
                    />

                    <Divider label={t('form.locationSection')} labelPosition="center" />

                    <Grid gutter="sm">
                        <Grid.Col span={6}>
                            <NumberInput
                                label={t('form.latitude')}
                                radius="md"
                                decimalScale={6}
                                min={-90}
                                max={90}
                                step={0.000001}
                                value={form.values.latitude ?? ''}
                                error={form.errors.latitude}
                                onChange={(val) =>
                                    form.setFieldValue('latitude', val === '' ? null : Number(val))
                                }
                            />
                        </Grid.Col>
                        <Grid.Col span={6}>
                            <NumberInput
                                label={t('form.longitude')}
                                radius="md"
                                decimalScale={6}
                                min={-180}
                                max={180}
                                step={0.000001}
                                value={form.values.longitude ?? ''}
                                error={form.errors.longitude}
                                onChange={(val) =>
                                    form.setFieldValue('longitude', val === '' ? null : Number(val))
                                }
                            />
                        </Grid.Col>
                    </Grid>

                    <CoordPicker
                        lat={form.values.latitude}
                        lng={form.values.longitude}
                        onChange={(lat, lng) => {
                            form.setFieldValue('latitude', lat);
                            form.setFieldValue('longitude', lng);
                        }}
                        hint={t('form.locationSection')}
                    />

                    <Group gap="xl">
                        <Switch
                            label={t('form.isActive')}
                            checked={form.values.is_active}
                            onChange={(e) => form.setFieldValue('is_active', e.currentTarget.checked)}
                        />
                        <Switch
                            label={t('form.isRealTime')}
                            checked={form.values.is_real_time}
                            onChange={(e) => form.setFieldValue('is_real_time', e.currentTarget.checked)}
                        />
                    </Group>

                    <Group justify="flex-end" mt="xs">
                        <Button variant="subtle" onClick={onClose}>
                            {t('form.cancel')}
                        </Button>
                        <Button type="submit">{t(isEdit ? 'form.save' : 'form.create')}</Button>
                    </Group>
                </Stack>
            </form>
        </Modal>
    );
}
