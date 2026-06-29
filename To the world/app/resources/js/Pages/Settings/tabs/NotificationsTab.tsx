import { useState } from 'react';
import { Button, Card, Stack, Switch, Text, Title } from '@mantine/core';
import { router } from '@inertiajs/react';
import { notifications } from '@mantine/notifications';
import { IconCheck, IconAlertTriangle } from '@tabler/icons-react';
import { useTranslation } from 'react-i18next';
import type { UserPreferences } from '@/types';

type NotifKey =
    | 'measurement_reviewed'
    | 'threshold_exceeded'
    | 'incident_reported'
    | 'incident_status_changed'
    | 'pin_used';

interface NotificationsTabProps {
    preferences: UserPreferences;
    isAdmin: boolean;
}

const KEYS: Array<{ key: NotifKey; labelKey: string; adminOnly?: boolean }> = [
    { key: 'measurement_reviewed',    labelKey: 'notifications.measurementReviewed' },
    { key: 'threshold_exceeded',      labelKey: 'notifications.thresholdExceeded' },
    { key: 'incident_reported',       labelKey: 'notifications.incidentReported' },
    { key: 'incident_status_changed', labelKey: 'notifications.incidentStatusChanged' },
    { key: 'pin_used',                labelKey: 'notifications.pinUsed', adminOnly: true },
];

export default function NotificationsTab({ preferences, isAdmin }: NotificationsTabProps) {
    const { t } = useTranslation('settings');
    const [submitting, setSubmitting] = useState(false);
    const [notifs, setNotifs] = useState<Record<NotifKey, boolean>>({
        measurement_reviewed:    preferences.notifications?.measurement_reviewed    ?? true,
        threshold_exceeded:      preferences.notifications?.threshold_exceeded      ?? true,
        incident_reported:       preferences.notifications?.incident_reported       ?? true,
        incident_status_changed: preferences.notifications?.incident_status_changed ?? true,
        pin_used:                preferences.notifications?.pin_used                ?? true,
    });

    const toggle = (key: NotifKey) =>
        setNotifs((prev) => ({ ...prev, [key]: !prev[key] }));

    const handleSave = () => {
        setSubmitting(true);
        router.patch('/settings/preferences', { notifications: notifs }, {
            preserveScroll: true,
            onSuccess: () => {
                notifications.show({
                    message: t('notifications.saved'),
                    color: 'green',
                    icon: <IconCheck size={16} />,
                    autoClose: 5000,
                });
            },
            onError: () => {
                notifications.show({
                    message: t('notifications.error'),
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
                    <Title order={4}>{t('notifications.title')}</Title>
                    <Text c="dimmed" size="sm">{t('notifications.subtitle')}</Text>
                </div>

                <Stack gap="md">
                    {KEYS.filter(({ adminOnly }) => !adminOnly || isAdmin).map(({ key, labelKey }) => (
                        <Switch
                            key={key}
                            label={t(labelKey)}
                            checked={notifs[key]}
                            onChange={() => toggle(key)}
                        />
                    ))}
                </Stack>

                <Button onClick={handleSave} loading={submitting} w="fit-content">
                    {t('notifications.save')}
                </Button>
            </Stack>
        </Card>
    );
}
