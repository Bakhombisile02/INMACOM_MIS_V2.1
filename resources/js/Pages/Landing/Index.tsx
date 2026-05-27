import { Head, Link } from '@inertiajs/react';
import { Alert, Button, Container, Group, Stack, Text, Title } from '@mantine/core';
import { IconAlertTriangle } from '@tabler/icons-react';
import { Trans, useTranslation } from 'react-i18next';
import PublicLayout from '@/Layouts/PublicLayout';
import HydroelectricDamAnimation from '@/Components/Auth/HydroelectricDamAnimation';
import classes from './Index.module.css';

export default function LandingIndex() {
    const { t } = useTranslation('landing');
    const { t: tCommon } = useTranslation('common');

    return (
        <>
            <Head title="INMACOM MIS" />

            <Container size="xl">
                <div className={classes.inner}>
                    <div className={classes.content}>
                        <Stack gap="lg">
                            <Text size="xs" tt="uppercase" fw={700} c="dimmed">
                                {t('eyebrow')}
                            </Text>

                            <Title className={classes.title} order={1}>
                                <Trans
                                    t={t}
                                    i18nKey="title"
                                    components={{ 1: <span className={classes.highlight} /> }}
                                />
                            </Title>

                            <Text size="md" c="dimmed">
                                {t('lead')}
                            </Text>

                            <Alert
                                color="red"
                                variant="light"
                                radius="md"
                                icon={<IconAlertTriangle size={20} />}
                                title={t('disclaimer.title')}
                            >
                                <Stack gap="xs">
                                    <Text size="sm">{t('disclaimer.body')}</Text>
                                    <Text size="sm" fw={500}>
                                        {t('disclaimer.agreement')}
                                    </Text>
                                </Stack>
                            </Alert>

                            <Group>
                                <Button component={Link} href="/explore" size="md">
                                    {tCommon('actions.exploreLiveData')}
                                </Button>
                                <Button component={Link} href="/login" variant="default" size="md">
                                    {tCommon('actions.signIn')}
                                </Button>
                                <Button
                                    component="a"
                                    href="#about"
                                    variant="subtle"
                                    size="md"
                                >
                                    {tCommon('actions.learnMore')}
                                </Button>
                            </Group>
                        </Stack>
                    </div>

                    <div className={classes.visual}>
                        <HydroelectricDamAnimation />
                    </div>
                </div>
            </Container>
        </>
    );
}

LandingIndex.layout = (page: React.ReactNode) => <PublicLayout>{page}</PublicLayout>;
