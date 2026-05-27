import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { useForm } from '@mantine/form';
import {
    Anchor,
    Alert,
    Box,
    Button,
    Center,
    Group,
    Paper,
    PasswordInput,
    Progress,
    Stack,
    Text,
    TextInput,
} from '@mantine/core';
import { IconCheck, IconX } from '@tabler/icons-react';
import { createUserWithEmailAndPassword, sendEmailVerification } from 'firebase/auth';
import { useTranslation } from 'react-i18next';
import { auth } from '@/lib/firebase';
import AuthLayout from '@/Layouts/AuthLayout';

function PasswordRequirement({ meets, label }: { meets: boolean; label: string }) {
    return (
        <Text component="div" c={meets ? 'teal' : 'red'} mt={5} size="sm">
            <Center inline>
                {meets ? <IconCheck size={14} stroke={1.5} /> : <IconX size={14} stroke={1.5} />}
                <Box ml={7}>{label}</Box>
            </Center>
        </Text>
    );
}

const passwordRequirements = [
    { re: /[0-9]/, key: 'reqNumber' as const },
    { re: /[a-z]/, key: 'reqLowercase' as const },
    { re: /[A-Z]/, key: 'reqUppercase' as const },
    { re: /[$&+,:;=?@#|'<>.^*()%!-]/, key: 'reqSpecial' as const },
];

function getPasswordStrength(password: string) {
    let multiplier = password.length > 7 ? 0 : 1;
    passwordRequirements.forEach((requirement) => {
        if (!requirement.re.test(password)) {
            multiplier += 1;
        }
    });
    return Math.max(100 - (100 / (passwordRequirements.length + 1)) * multiplier, 0);
}

export default function RegisterEmail() {
    const { t } = useTranslation('auth');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [passwordValue, setPasswordValue] = useState('');

    const form = useForm({
        mode: 'uncontrolled',
        initialValues: { email: '', password: '' },
        validate: {
            email: (val) => (/^\S+@\S+$/.test(val) ? null : t('errors.invalidEmail')),
            password: (val) => {
                const errors: string[] = [];
                if (val.length < 8) errors.push(t('errors.req8chars'));
                if (!/[A-Z]/.test(val)) errors.push(t('errors.reqUppercase'));
                if (!/[a-z]/.test(val)) errors.push(t('errors.reqLowercase'));
                if (!/[0-9]/.test(val)) errors.push(t('errors.reqNumber'));
                if (!/[^A-Za-z0-9]/.test(val)) errors.push(t('errors.reqSpecial'));
                return errors.length > 0
                    ? t('errors.passwordMustContain', { requirements: errors.join(', ') })
                    : null;
            },
        },
    });

    form.watch('password', ({ value }) => {
        setPasswordValue(typeof value === 'string' ? value : '');
    });

    const strength = getPasswordStrength(passwordValue);
    const strengthColor = strength > 80 ? 'teal' : strength > 50 ? 'yellow' : 'red';
    const checks = passwordRequirements.map((requirement) => (
        <PasswordRequirement
            key={requirement.key}
            label={t(`errors.${requirement.key}`)}
            meets={requirement.re.test(passwordValue)}
        />
    ));
    const bars = Array(4)
        .fill(0)
        .map((_, index) => (
            <Progress
                styles={{ section: { transitionDuration: '0ms' } }}
                value={
                    passwordValue.length > 0 && index === 0
                        ? 100
                        : strength >= ((index + 1) / 4) * 100
                          ? 100
                          : 0
                }
                color={strengthColor}
                key={index}
                size={4}
                aria-label={`Password strength segment ${index + 1}`}
            />
        ));

    const handleSubmit = form.onSubmit(async (values) => {
        setLoading(true);
        setError(null);

        try {
            const credential = await createUserWithEmailAndPassword(
                auth,
                values.email.trim().toLowerCase(),
                values.password,
            );
            await sendEmailVerification(credential.user);
            router.visit(route('login.email'), {
                data: { status: t('login.registerSuccess') },
            });
        } catch (e: any) {
            const code: string = e?.code ?? '';
            if (code === 'auth/email-already-in-use') {
                form.setFieldError('email', t('errors.emailInUse'));
            } else if (code === 'auth/weak-password') {
                form.setFieldError('password', t('errors.passwordMustContain', { requirements: t('errors.req8chars') }));
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
            <Head title={t('login.register')} />

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
                            placeholder={t('login.passwordRegisterPlaceholder')}
                            radius="md"
                            autoComplete="new-password"
                            key={form.key('password')}
                            {...form.getInputProps('password')}
                        />

                        <Box>
                            <Group gap={5} grow mt="xs" mb="xs">
                                {bars}
                            </Group>
                            <PasswordRequirement
                                label={t('errors.req8chars')}
                                meets={passwordValue.length > 7}
                            />
                            {checks}
                        </Box>
                    </Stack>

                    <Group justify="space-between" mt="xl">
                        <Anchor component="a" href={route('login')} size="xs">
                            {t('login.toLogin')}
                        </Anchor>
                        <Button type="submit" loading={loading}>
                            {t('login.register')}
                        </Button>
                    </Group>
                </form>
            </Paper>
        </AuthLayout>
    );
}
