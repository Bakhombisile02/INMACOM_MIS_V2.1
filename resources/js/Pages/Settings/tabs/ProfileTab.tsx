import { useState } from 'react';
import { useForm } from '@mantine/form';
import { Button, Card, Select, Stack, Text, TextInput, Title } from '@mantine/core';
import { router } from '@inertiajs/react';
import { notifications } from '@mantine/notifications';
import { IconCheck, IconAlertTriangle } from '@tabler/icons-react';
import { useTranslation } from 'react-i18next';
import type { User } from '@/types';

const COUNTRIES = [
    { value: 'Mozambique', label: 'Mozambique' },
    { value: 'South Africa', label: 'South Africa' },
    { value: 'Eswatini', label: 'Eswatini' },
];

interface ProfileTabProps {
    user: User;
}

export default function ProfileTab({ user }: ProfileTabProps) {
    const { t } = useTranslation('settings');
    const [submitting, setSubmitting] = useState(false);

    const form = useForm({
        initialValues: {
            display_name: user.display_name ?? '',
            country:      user.country ?? '',
            organization: user.organization ?? '',
            telephone:    user.telephone ?? '',
        },
        validate: {
            display_name: (v) => v.trim().length === 0 ? t('profile.displayNameRequired') : null,
        },
    });

    const handleSubmit = form.onSubmit((values) => {
        setSubmitting(true);
        router.patch('/settings/profile', values, {
            preserveScroll: true,
            onSuccess: () => {
                notifications.show({
                    message: t('profile.saved'),
                    color: 'green',
                    icon: <IconCheck size={16} />,
                    autoClose: 5000,
                });
            },
            onError: (errors) => {
                notifications.show({
                    message: Object.values(errors)[0] ?? t('profile.error'),
                    color: 'red',
                    icon: <IconAlertTriangle size={16} />,
                    autoClose: false,
                    withCloseButton: true,
                });
            },
            onFinish: () => setSubmitting(false),
        });
    });

    return (
        <Card withBorder radius="md" p="xl">
            <Stack gap="lg">
                <div>
                    <Title order={4}>{t('profile.title')}</Title>
                    <Text c="dimmed" size="sm">{t('profile.subtitle')}</Text>
                </div>

                <form onSubmit={handleSubmit}>
                    <Stack gap="md">
                        <TextInput
                            radius="md"
                            label={t('profile.displayName')}
                            {...form.getInputProps('display_name')}
                        />
                        <TextInput
                            radius="md"
                            label={t('profile.email')}
                            value={user.email}
                            disabled
                            description={t('profile.emailReadOnly')}
                        />
                        <Select
                            label={t('profile.country')}
                            placeholder={t('profile.countryPlaceholder')}
                            data={COUNTRIES}
                            clearable
                            value={form.values.country || null}
                            onChange={(v) => form.setFieldValue('country', v ?? '')}
                        />
                        <TextInput
                            radius="md"
                            label={t('profile.organization')}
                            {...form.getInputProps('organization')}
                        />
                        <TextInput
                            radius="md"
                            label={t('profile.telephone')}
                            {...form.getInputProps('telephone')}
                        />
                        <Button type="submit" loading={submitting} w="fit-content">
                            {t('profile.saveProfile')}
                        </Button>
                    </Stack>
                </form>
            </Stack>
        </Card>
    );
}
