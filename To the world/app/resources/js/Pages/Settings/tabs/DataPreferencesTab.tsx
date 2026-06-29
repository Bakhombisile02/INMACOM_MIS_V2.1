import { useState } from 'react';
import { Button, Card, Select, Stack, Text, Title } from '@mantine/core';
import { router } from '@inertiajs/react';
import { notifications } from '@mantine/notifications';
import { IconCheck, IconAlertTriangle } from '@tabler/icons-react';
import { useTranslation } from 'react-i18next';
import type { UserPreferences } from '@/types';

const TIMEZONES = [
    { value: 'Africa/Maputo',       label: 'Africa/Maputo (UTC+2)' },
    { value: 'Africa/Johannesburg', label: 'Africa/Johannesburg (UTC+2)' },
    { value: 'Africa/Mbabane',      label: 'Africa/Mbabane (UTC+2)' },
    { value: 'UTC',                 label: 'UTC' },
];

const COUNTRIES = [
    { value: 'Mozambique',   label: 'Mozambique' },
    { value: 'South Africa', label: 'South Africa' },
    { value: 'Eswatini',     label: 'Eswatini' },
];

const ROWS_OPTIONS = ['10', '25', '50', '100'].map((v) => ({ value: v, label: v }));

interface DataPreferencesTabProps {
    preferences: UserPreferences;
}

export default function DataPreferencesTab({ preferences }: DataPreferencesTabProps) {
    const { t } = useTranslation('settings');
    const [submitting, setSubmitting] = useState(false);
    const [timezone, setTimezone]         = useState<string | null>(preferences.timezone ?? null);
    const [rowsPerPage, setRowsPerPage]   = useState<string | null>(
        preferences.rows_per_page ? String(preferences.rows_per_page) : '25',
    );
    const [defaultCountry, setDefaultCountry] = useState<string | null>(
        preferences.default_country ?? null,
    );

    const handleSave = () => {
        setSubmitting(true);
        router.patch('/settings/preferences', {
            timezone,
            rows_per_page: rowsPerPage ? parseInt(rowsPerPage, 10) : null,
            default_country: defaultCountry,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                notifications.show({
                    message: t('dataPreferences.saved'),
                    color: 'green',
                    icon: <IconCheck size={16} />,
                    autoClose: 5000,
                });
            },
            onError: () => {
                notifications.show({
                    message: t('dataPreferences.error'),
                    color: 'red',
                    icon: <IconAlertTriangle size={16} />,
                    autoClose: false,
                    withCloseButton: true,
                });
            },
            onFinish: () => setSubmitting(false),
        });
    };

    return (
        <Card withBorder radius="md" p="xl">
            <Stack gap="lg">
                <div>
                    <Title order={4}>{t('dataPreferences.title')}</Title>
                    <Text c="dimmed" size="sm">{t('dataPreferences.subtitle')}</Text>
                </div>

                <Select
                    label={t('dataPreferences.timezone')}
                    placeholder={t('dataPreferences.timezonePlaceholder')}
                    data={TIMEZONES}
                    value={timezone}
                    onChange={setTimezone}
                    clearable
                />
                <Select
                    label={t('dataPreferences.rowsPerPage')}
                    data={ROWS_OPTIONS}
                    value={rowsPerPage}
                    onChange={setRowsPerPage}
                />
                <Select
                    label={t('dataPreferences.defaultCountry')}
                    placeholder={t('dataPreferences.defaultCountryPlaceholder')}
                    data={COUNTRIES}
                    value={defaultCountry}
                    onChange={setDefaultCountry}
                    clearable
                />

                <Button onClick={handleSave} loading={submitting} w="fit-content">
                    {t('dataPreferences.save')}
                </Button>
            </Stack>
        </Card>
    );
}
