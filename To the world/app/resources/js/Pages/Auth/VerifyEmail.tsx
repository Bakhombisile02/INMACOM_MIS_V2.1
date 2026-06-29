import { useState } from 'react';
import { Head } from '@inertiajs/react';
import { Anchor, Alert, Button, Group, Paper, Stack, Text } from '@mantine/core';
import { IconMailCheck } from '@tabler/icons-react';
import { sendEmailVerification } from 'firebase/auth';
import { useTranslation } from 'react-i18next';
import { auth } from '@/lib/firebase';
import AuthLayout from '@/Layouts/AuthLayout';

export default function VerifyEmail() {
    const { t } = useTranslation('auth');
    const [sent, setSent] = useState(false);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const resend = async () => {
        const user = auth.currentUser;
        if (!user) {
            setError(t('errors.generic'));
            return;
        }
        setLoading(true);
        try {
            await sendEmailVerification(user);
            setSent(true);
        } catch {
            setError(t('errors.generic'));
        } finally {
            setLoading(false);
        }
    };

    return (
        <AuthLayout>
            <Head title={t('verify.title')} />

            <Paper radius="md" p="xl" withBorder maw={480} w="100%">
                <Text size="lg" fw={500}>
                    {t('verify.title')}
                </Text>

                <Stack gap="md" mt="lg">
                    <IconMailCheck size={48} stroke={1.5} />

                    <Text c="dimmed" size="sm">
                        {t('verify.description')}
                    </Text>

                    {sent && <Alert color="green">{t('verify.sent')}</Alert>}
                    {error && <Alert color="red">{error}</Alert>}
                </Stack>

                <Group justify="space-between" mt="xl">
                    <Anchor component="a" href={route('login')} size="xs">
                        {t('verify.backToLogin')}
                    </Anchor>
                    <Button loading={loading} onClick={resend}>
                        {t('verify.resend')}
                    </Button>
                </Group>
            </Paper>
        </AuthLayout>
    );
}
