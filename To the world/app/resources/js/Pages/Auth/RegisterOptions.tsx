import { useState } from 'react';
import { Head } from '@inertiajs/react';
import { Anchor, Alert, Button, Divider, Group, Paper, Tooltip } from '@mantine/core';
import { IconBrandGoogle, IconBrandWindows, IconMail } from '@tabler/icons-react';
import { signInWithPopup } from 'firebase/auth';
import { useTranslation } from 'react-i18next';
import { auth, googleProvider } from '@/lib/firebase';
import { authenticateWithLaravel } from '@/lib/firebaseAuth';
import AuthLayout from '@/Layouts/AuthLayout';

export default function RegisterOptions() {
    const { t } = useTranslation('auth');
    const [googleLoading, setGoogleLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleGoogle = async () => {
        setGoogleLoading(true);
        setError(null);
        try {
            const result = await signInWithPopup(auth, googleProvider);
            await authenticateWithLaravel(result.user, setError);
        } catch (e: any) {
            if (e?.code !== 'auth/popup-closed-by-user' && e?.code !== 'auth/cancelled-popup-request') {
                setError(t('errors.generic'));
            }
            setGoogleLoading(false);
        }
    };

    return (
        <AuthLayout>
            <Head title={t('login.register')} />

            <Paper radius="md" p="xl" withBorder maw={480} w="100%">
                {error && <Alert color="red" mb="md">{error}</Alert>}

                <Group grow mb="md">
                    <Button
                        variant="default"
                        leftSection={<IconBrandGoogle size={16} />}
                        loading={googleLoading}
                        onClick={handleGoogle}
                    >
                        {t('login.continueWithGoogle')}
                    </Button>
                    <Tooltip label={t('login.microsoftComingSoon')} withArrow>
                        <Button
                            variant="default"
                            leftSection={<IconBrandWindows size={16} />}
                            data-disabled
                            onClick={(event) => event.preventDefault()}
                        >
                            {t('login.continueWithMicrosoft')}
                        </Button>
                    </Tooltip>
                </Group>

                <Divider label={t('login.orContinueWithEmail')} labelPosition="center" my="lg" />

                <Button
                    component="a"
                    href={route('register.email')}
                    fullWidth
                    leftSection={<IconMail size={16} />}
                >
                    {t('login.emailLabel')}
                </Button>

                <Group justify="space-between" mt="xl">
                    <Anchor component="a" href={route('login')} size="xs">
                        {t('login.toLogin')}
                    </Anchor>
                </Group>
            </Paper>
        </AuthLayout>
    );
}
