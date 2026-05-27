import { Head, usePage } from '@inertiajs/react';
import { Container, Stack, Tabs, Text, Title } from '@mantine/core';
import { IconBell, IconDatabase, IconPalette, IconUser } from '@tabler/icons-react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { PageProps, UserPreferences } from '@/types';
import ProfileTab from './tabs/ProfileTab';
import AppearanceTab from './tabs/AppearanceTab';
import NotificationsTab from './tabs/NotificationsTab';
import DataPreferencesTab from './tabs/DataPreferencesTab';

interface SettingsProps {
    preferences: UserPreferences;
    [key: string]: unknown;
}

export default function SettingsIndex() {
    const { t } = useTranslation('settings');
    const { auth, preferences } = usePage<PageProps<SettingsProps>>().props;
    const user = auth.user!;
    const isAdmin = user.role === 'admin';

    return (
        <>
            <Head title={t('title')} />

            <Container size="lg" py="xl">
                <Stack gap="xl">
                    <div>
                        <Title order={2}>{t('title')}</Title>
                        <Text c="dimmed" size="sm" mt={4}>{t('subtitle')}</Text>
                    </div>

                    <Tabs defaultValue="profile">
                        <Tabs.List mb="xl">
                            <Tabs.Tab value="profile" leftSection={<IconUser size={15} />}>
                                {t('tabs.profile')}
                            </Tabs.Tab>
                            <Tabs.Tab value="appearance" leftSection={<IconPalette size={15} />}>
                                {t('tabs.appearance')}
                            </Tabs.Tab>
                            <Tabs.Tab value="notifications" leftSection={<IconBell size={15} />}>
                                {t('tabs.notifications')}
                            </Tabs.Tab>
                            <Tabs.Tab value="data" leftSection={<IconDatabase size={15} />}>
                                {t('tabs.dataPreferences')}
                            </Tabs.Tab>
                        </Tabs.List>

                        <Tabs.Panel value="profile">
                            <ProfileTab user={user} />
                        </Tabs.Panel>

                        <Tabs.Panel value="appearance">
                            <AppearanceTab preferences={preferences} />
                        </Tabs.Panel>

                        <Tabs.Panel value="notifications">
                            <NotificationsTab preferences={preferences} isAdmin={isAdmin} />
                        </Tabs.Panel>

                        <Tabs.Panel value="data">
                            <DataPreferencesTab preferences={preferences} />
                        </Tabs.Panel>
                    </Tabs>
                </Stack>
            </Container>
        </>
    );
}

SettingsIndex.layout = (page: React.ReactNode) =>
    <AuthenticatedLayout>{page}</AuthenticatedLayout>;
