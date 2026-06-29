import { Head } from '@inertiajs/react';
import { Card, Container, Stack, Text, Title } from '@mantine/core';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

interface ModulePlaceholderProps {
    titleKey: string;
    descriptionKey?: string;
}

export default function ModulePlaceholder({ titleKey, descriptionKey }: ModulePlaceholderProps) {
    const { t } = useTranslation('navigation');

    return (
        <>
            <Head title={t(titleKey)} />

            <Container size="xl" py="xl">
                <Card withBorder radius="md" p="xl">
                    <Stack gap="xs">
                        <Title order={2}>{t(titleKey)}</Title>
                        <Text c="dimmed">
                            {descriptionKey ? t(descriptionKey) : t('placeholder.default')}
                        </Text>
                    </Stack>
                </Card>
            </Container>
        </>
    );
}

ModulePlaceholder.layout = (page: React.ReactNode) => <AuthenticatedLayout>{page}</AuthenticatedLayout>;
