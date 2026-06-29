import { Card, Group, SegmentedControl, Stack, Text, Title, useMantineColorScheme } from '@mantine/core';
import { router } from '@inertiajs/react';
import { notifications } from '@mantine/notifications';
import { IconCheck } from '@tabler/icons-react';
import { useTranslation } from 'react-i18next';
import LanguageSwitcher from '@/Components/UI/LanguageSwitcher';
import type { UserPreferences } from '@/types';

interface AppearanceTabProps {
    preferences: UserPreferences;
}

export default function AppearanceTab({ preferences: _ }: AppearanceTabProps) {
    const { t } = useTranslation('settings');
    const { colorScheme, setColorScheme } = useMantineColorScheme();

    const handleThemeChange = (value: string) => {
        setColorScheme(value as 'light' | 'dark' | 'auto');
        router.patch('/settings/preferences', { theme: value }, {
            preserveScroll: true,
            onSuccess: () => {
                notifications.show({
                    message: t('appearance.saved'),
                    color: 'green',
                    icon: <IconCheck size={16} />,
                    autoClose: 3000,
                });
            },
        });
    };

    return (
        <Stack gap="md">
            <Card withBorder radius="md" p="xl">
                <Stack gap="md">
                    <div>
                        <Title order={4}>{t('appearance.themeTitle')}</Title>
                        <Text c="dimmed" size="sm">{t('appearance.themeSubtitle')}</Text>
                    </div>
                    <SegmentedControl
                        value={colorScheme}
                        onChange={handleThemeChange}
                        data={[
                            { value: 'light', label: t('appearance.themeLight') },
                            { value: 'dark',  label: t('appearance.themeDark') },
                            { value: 'auto',  label: t('appearance.themeAuto') },
                        ]}
                    />
                </Stack>
            </Card>

            <Card withBorder radius="md" p="xl">
                <Stack gap="md">
                    <div>
                        <Title order={4}>{t('appearance.languageTitle')}</Title>
                        <Text c="dimmed" size="sm">{t('appearance.languageSubtitle')}</Text>
                    </div>
                    <Group>
                        <LanguageSwitcher />
                    </Group>
                </Stack>
            </Card>
        </Stack>
    );
}
