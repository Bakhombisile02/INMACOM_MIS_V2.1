import { Head, router, usePage } from '@inertiajs/react';
import { Button, Container, Stack, Text, Title } from '@mantine/core';
import { useTranslation } from 'react-i18next';
import MainLayout from '@/Layouts/MainLayout';
import type { PageProps } from '@/types';

export default function DashboardPlaceholder() {
    const { t } = useTranslation('app');
    const { t: tCommon } = useTranslation('common');
    const { auth } = usePage<PageProps>().props;

    return (
        <>
            <Head title={tCommon('header.dashboard')} />

            <Container size="sm" py={80}>
                <Stack align="center" gap="sm">
                    <Title order={1}>{t('dashboard.placeholderTitle')}</Title>
                    <Text c="dimmed" ta="center">
                        {t('dashboard.placeholderBody')}
                    </Text>
                    {auth.user && (
                        <Text size="sm">{t('dashboard.signedInAs', { email: auth.user.email })}</Text>
                    )}
                    <Button
                        color="dark"
                        onClick={() => router.post(route('logout'))}
                    >
                        {tCommon('actions.logout')}
                    </Button>
                </Stack>
            </Container>
        </>
    );
}

DashboardPlaceholder.layout = (page: React.ReactNode) => <MainLayout>{page}</MainLayout>;
