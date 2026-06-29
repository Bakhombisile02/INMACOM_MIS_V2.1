import { Head, usePage } from '@inertiajs/react';
import { Avatar, Card, Container, Group, Stack, Text, Title } from '@mantine/core';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { PageProps } from '@/types';

function initialsFromName(name: string) {
    return name
        .split(' ')
        .slice(0, 2)
        .map((chunk) => chunk.charAt(0).toUpperCase())
        .join('');
}

export default function ProfileIndex() {
    const { t } = useTranslation('navigation');
    const { auth } = usePage<PageProps>().props;
    const user = auth.user;
    const displayName = user?.display_name ?? t('unknownUser');

    return (
        <>
            <Head title={t('profile')} />

            <Container size="xl" py="xl">
                <Card withBorder radius="md" p="xl">
                    <Stack gap="md">
                        <Group gap="md">
                            <Avatar size={56} src={user?.photo_url ?? undefined}>
                                {initialsFromName(displayName)}
                            </Avatar>
                            <div>
                                <Title order={2}>{displayName}</Title>
                                <Text c="dimmed">{t(`roles.${user?.role ?? 'clerk'}`)}</Text>
                            </div>
                        </Group>

                        <Group justify="space-between">
                            <Text c="dimmed">{t('profileFields.email')}</Text>
                            <Text>{user?.email ?? '-'}</Text>
                        </Group>
                        <Group justify="space-between">
                            <Text c="dimmed">{t('profileFields.country')}</Text>
                            <Text>{user?.country ?? '-'}</Text>
                        </Group>
                        <Group justify="space-between">
                            <Text c="dimmed">{t('profileFields.organization')}</Text>
                            <Text>{user?.organization ?? '-'}</Text>
                        </Group>
                        <Group justify="space-between">
                            <Text c="dimmed">{t('profileFields.telephone')}</Text>
                            <Text>{user?.telephone ?? '-'}</Text>
                        </Group>
                    </Stack>
                </Card>
            </Container>
        </>
    );
}

ProfileIndex.layout = (page: React.ReactNode) => <AuthenticatedLayout>{page}</AuthenticatedLayout>;
