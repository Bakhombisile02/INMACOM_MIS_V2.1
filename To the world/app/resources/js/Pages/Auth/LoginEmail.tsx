import { useState } from 'react';
import { Head } from '@inertiajs/react';
import { useForm } from '@mantine/form';
import { Anchor, Alert, Button, Group, Paper, PasswordInput, Stack, TextInput } from '@mantine/core';
import { signInWithEmailAndPassword } from 'firebase/auth';
import { useTranslation } from 'react-i18next';
import { auth } from '@/lib/firebase';
import { authenticateWithLaravel } from '@/lib/firebaseAuth';
import AuthLayout from '@/Layouts/AuthLayout';

export default function LoginEmail() {
    const { t } = useTranslation('auth');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const form = useForm({
        mode: 'uncontrolled',
        initialValues: { email: '', password: '' },
        validate: {
            email: (val) => (/^\S+@\S+$/.test(val) ? null : t('errors.invalidEmail')),
            password: (val) => (val.length < 1 ? t('errors.passwordRequired') : null),
        },
    });

    const handleSubmit = form.onSubmit(async (values) => {
        setLoading(true);
        setError(null);

        try {
            const credential = await signInWithEmailAndPassword(
                auth,
                values.email.trim().toLowerCase(),
                values.password,
            );
            await authenticateWithLaravel(credential.user, setError);
        } catch (e: any) {
            const code: string = e?.code ?? '';
            if (code === 'auth/invalid-credential' || code === 'auth/user-not-found' || code === 'auth/wrong-password') {
                setError(t('errors.wrongPassword'));
            } else if (code === 'auth/too-many-requests') {
                setError(t('errors.tooManyRequests'));
            } else {
                setError(t('errors.generic'));
            }
            setLoading(false);
        }
    });

    return (
        <AuthLayout>
            <Head title={t('login.title')} />

            <Paper radius="md" p="xl" withBorder maw={480} w="100%">
                {error && <Alert color="red" mb="md">{error}</Alert>}

                <form onSubmit={handleSubmit}>
                    <Stack>
                        <TextInput
                            required
                            label={t('login.emailLabel')}
                            placeholder={t('login.emailPlaceholder')}
                            radius="md"
                            autoComplete="email"
                            key={form.key('email')}
                            {...form.getInputProps('email')}
                        />
                        <PasswordInput
                            required
                            label={t('login.passwordLabel')}
                            placeholder={t('login.passwordPlaceholder')}
                            radius="md"
                            autoComplete="current-password"
                            key={form.key('password')}
                            {...form.getInputProps('password')}
                        />
                    </Stack>

                    <Group justify="space-between" mt="xl">
                        <Anchor component="a" href={route('register')} size="xs">
                            {t('login.toRegister')}
                        </Anchor>
                        <Button type="submit" loading={loading}>
                            {t('login.title')}
                        </Button>
                    </Group>
                </form>
            </Paper>
        </AuthLayout>
    );
}
