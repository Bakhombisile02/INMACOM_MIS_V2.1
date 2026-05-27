import { Head, Link } from '@inertiajs/react';
import { IconAlertTriangle, IconDroplet, IconFileText, IconMapPin } from '@tabler/icons-react';
import { Card, Container, Group, SimpleGrid, Stack, Text, Title } from '@mantine/core';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

interface DashboardIndexProps {
    summary: {
        stations: number;
        measurements: number;
        incidents: number;
        documents: number;
    };
}

export default function DashboardIndex({ summary }: DashboardIndexProps) {
    const { t } = useTranslation('app');
    const { t: tNav } = useTranslation('navigation');

    return (
        <>
            <Head title={tNav('dashboard')} />

            <Container size="xl" py="xl">
                <Stack gap="xs" mb="lg">
                    <Title order={1}>{t('dashboard.title')}</Title>
                    <Text c="dimmed">{t('dashboard.subtitle')}</Text>
                </Stack>

                <SimpleGrid cols={{ base: 1, sm: 2, lg: 4 }} spacing="md">
                    <Card withBorder radius="md" p="md">
                        <Group justify="space-between">
                            <Text fw={600}>{t('dashboard.cards.stations')}</Text>
                            <IconMapPin size={18} />
                        </Group>
                        <Title order={2} mt="sm">{summary.stations}</Title>
                        <Text c="dimmed" size="sm" mt={8}>
                            <Link href={route('stations.index')}>{t('dashboard.cards.openStations')}</Link>
                        </Text>
                    </Card>

                    <Card withBorder radius="md" p="md">
                        <Group justify="space-between">
                            <Text fw={600}>{t('dashboard.cards.measurements')}</Text>
                            <IconDroplet size={18} />
                        </Group>
                        <Title order={2} mt="sm">{summary.measurements}</Title>
                        <Text c="dimmed" size="sm" mt={8}>{t('dashboard.cards.liveData')}</Text>
                    </Card>

                    <Card withBorder radius="md" p="md">
                        <Group justify="space-between">
                            <Text fw={600}>{t('dashboard.cards.incidents')}</Text>
                            <IconAlertTriangle size={18} />
                        </Group>
                        <Title order={2} mt="sm">{summary.incidents}</Title>
                        <Text c="dimmed" size="sm" mt={8}>{t('dashboard.cards.monitorAlerts')}</Text>
                    </Card>

                    <Card withBorder radius="md" p="md">
                        <Group justify="space-between">
                            <Text fw={600}>{t('dashboard.cards.documents')}</Text>
                            <IconFileText size={18} />
                        </Group>
                        <Title order={2} mt="sm">{summary.documents}</Title>
                        <Text c="dimmed" size="sm" mt={8}>
                            <Link href={route('library')}>{t('dashboard.cards.openLibrary')}</Link>
                        </Text>
                    </Card>
                </SimpleGrid>
            </Container>
        </>
    );
}

DashboardIndex.layout = (page: React.ReactNode) => <AuthenticatedLayout>{page}</AuthenticatedLayout>;
