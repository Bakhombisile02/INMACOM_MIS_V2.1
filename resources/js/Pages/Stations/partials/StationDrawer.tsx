import { Link } from '@inertiajs/react';
import { Badge, Button, Divider, Drawer, Group, Stack, Text, Title } from '@mantine/core';
import { IconChevronRight } from '@tabler/icons-react';
import { useTranslation } from 'react-i18next';

export interface StationSummary {
    id: string;
    code: string;
    name: string;
    country?: string | null;
    category: string;
    water_source: string;
    water_body_type: string;
    status: 'active' | 'inactive';
    is_real_time: boolean;
    show_url: string;
}

interface StationDrawerProps {
    station: StationSummary | null;
    onClose: () => void;
}

export default function StationDrawer({ station, onClose }: StationDrawerProps) {
    const { t } = useTranslation('stations');

    return (
        <Drawer
            opened={station !== null}
            onClose={onClose}
            position="right"
            size={380}
            title={
                <Title order={4} lineClamp={1}>
                    {station?.name ?? ''}
                </Title>
            }
            padding="md"
        >
            {station && (
                <Stack gap="md">
                    <Group gap="xs" wrap="wrap">
                        <Badge variant="outline" color="gray" size="sm">
                            {station.code}
                        </Badge>
                        <Badge
                            color={station.status === 'active' ? 'green' : 'gray'}
                            variant="light"
                            size="sm"
                        >
                            {t(`status.${station.status}`)}
                        </Badge>
                        {station.is_real_time && (
                            <Badge color="teal" variant="light" size="sm">
                                {t('detail.realtime')}
                            </Badge>
                        )}
                    </Group>

                    <Divider />

                    <Stack gap="xs">
                        {station.country && (
                            <Group justify="space-between">
                                <Text size="sm" c="dimmed">
                                    {t('detail.country')}
                                </Text>
                                <Text size="sm">{station.country}</Text>
                            </Group>
                        )}
                        <Group justify="space-between">
                            <Text size="sm" c="dimmed">
                                {t('detail.category')}
                            </Text>
                            <Text size="sm">{station.category}</Text>
                        </Group>
                        <Group justify="space-between">
                            <Text size="sm" c="dimmed">
                                {t('detail.waterSource')}
                            </Text>
                            <Text size="sm">{station.water_source}</Text>
                        </Group>
                        <Group justify="space-between">
                            <Text size="sm" c="dimmed">
                                {t('detail.waterBodyType')}
                            </Text>
                            <Text size="sm">{station.water_body_type}</Text>
                        </Group>
                    </Stack>

                    <Button
                        component={Link}
                        href={station.show_url}
                        rightSection={<IconChevronRight size={16} />}
                        variant="light"
                        fullWidth
                        mt="auto"
                    >
                        {t('actions.viewDetails')}
                    </Button>
                </Stack>
            )}
        </Drawer>
    );
}
