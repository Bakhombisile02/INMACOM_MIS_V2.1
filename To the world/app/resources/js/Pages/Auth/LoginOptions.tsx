import { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useForm } from '@mantine/form';
import { upperFirst, useDisclosure, useToggle } from '@mantine/hooks';
import { Anchor, Alert, Badge, Box, Button, Center, Checkbox, Divider, Group, Modal, Paper, PasswordInput, PinInput, Progress, Stack, Text, TextInput, Tooltip } from '@mantine/core';
import { notifications } from '@mantine/notifications';
import { IconAlertTriangle, IconBrandGoogle, IconBrandWindows, IconCheck, IconShieldCheck, IconX } from '@tabler/icons-react';
import { createUserWithEmailAndPassword, sendEmailVerification, signInWithEmailAndPassword, signInWithPopup } from 'firebase/auth';
import { useTranslation } from 'react-i18next';
import { auth, googleProvider } from '@/lib/firebase';
import { authenticateWithLaravel } from '@/lib/firebaseAuth';
import AuthLayout from '@/Layouts/AuthLayout';
import type { PageProps } from '@/types';

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

export default function LoginOptions() {
    const { t } = useTranslation('auth');
    const { flash } = usePage<PageProps>().props;
    const [type, toggle] = useToggle(['login', 'register'] as const);
    const [googleLoading, setGoogleLoading] = useState(false);
    const [emailLoading, setEmailLoading] = useState(false);

    const notifyError = (message: string) =>
        notifications.show({
            color: 'red',
            title: t('errors.title', { defaultValue: 'Authentication error' }),
            message,
            icon: <IconAlertTriangle size={18} />,
            autoClose: false,
            withCloseButton: true,
            withBorder: true,
        });

    const notifySuccess = (message: string) =>
        notifications.show({
            color: 'green',
            title: t('common.success', { defaultValue: 'Success' }),
            message,
            icon: <IconCheck size={18} />,
            autoClose: 5000,
            withBorder: true,
        });

    useEffect(() => {
        if (flash?.status) notifySuccess(flash.status);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [flash?.status]);

    const [pinModalOpened, { open: openPinModal, close: closePinModal }] = useDisclosure(false);
    const [pinCode, setPinCode] = useState('');
    const [pinVerifying, setPinVerifying] = useState(false);
    const [pinError, setPinError] = useState<string | null>(null);
    const [pinRole, setPinRole] = useState<string | null>(null);
    const [pinRoleLabel, setPinRoleLabel] = useState<string | null>(null);

    // Auto-open the PIN modal when ?pin= is present in the URL (e.g. from an invite link redirect)
    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const prefilledPin = params.get('pin');
        if (prefilledPin && prefilledPin.length === 6) {
            const code = prefilledPin.toUpperCase();
            setPinCode(code);
            openPinModal();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const form = useForm({
        initialValues: { name: '', email: '', password: '', terms: true },
        validate: {
            email: (val) => (/^\S+@\S+$/.test(val) ? null : t('errors.invalidEmail')),
            password: (val) => {
                if (type === 'login') return val.length < 1 ? t('errors.passwordRequired') : null;
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

    const resetPinState = () => {
        setPinCode('');
        setPinError(null);
        setPinRole(null);
        setPinRoleLabel(null);
    };

    const handleToggle = () => {
        // From register → login: reset pin state so any later switch back requires re-entry.
        if (type === 'register') {
            resetPinState();
        }
        toggle();
        form.reset();
    };

    const handleRegisterAnchor = () => {
        // Always require a fresh PIN before showing the register form.
        setPinError(null);
        setPinCode('');
        openPinModal();
    };

    const handleVerifyPin = async () => {
        if (pinCode.length < 6) return;
        setPinVerifying(true);
        setPinError(null);
        try {
            const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
            const res = await fetch(route('register.pin.verify'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ code: pinCode.toUpperCase() }),
            });
            if (!res.ok) {
                const body = await res.json().catch(() => ({}));
                const msg = body?.errors?.code?.[0] ?? body?.message ?? t('pin.invalid');
                setPinError(String(msg));
                return;
            }
            const body = await res.json();
            setPinRole(body.role);
            setPinRoleLabel(
                t(`pin.roleLabels.${body.role}`, { defaultValue: body.role_label ?? body.role }),
            );
            closePinModal();
            // Switch into register mode if not already.
            if (type === 'login') {
                toggle();
                form.reset();
            }
        } catch {
            setPinError(t('errors.generic'));
        } finally {
            setPinVerifying(false);
        }
    };

    const handleGoogle = async () => {
        setGoogleLoading(true);
        try {
            const result = await signInWithPopup(auth, googleProvider);
            await authenticateWithLaravel(
                result.user,
                (msg) => {
                    notifyError(msg);
                    setGoogleLoading(false);
                },
                { registrationPin: pinCode || undefined },
            );
        } catch (e: any) {
            if (e?.code !== 'auth/popup-closed-by-user' && e?.code !== 'auth/cancelled-popup-request') {
                notifyError(t('errors.generic'));
            }
            setGoogleLoading(false);
        }
    };

    const handleSubmit = form.onSubmit(async (values) => {
        setEmailLoading(true);
        try {
            if (type === 'login') {
                const credential = await signInWithEmailAndPassword(
                    auth,
                    values.email.trim().toLowerCase(),
                    values.password,
                );
                await authenticateWithLaravel(credential.user, (msg) => {
                    notifyError(msg);
                    setEmailLoading(false);
                });
            } else {
                const credential = await createUserWithEmailAndPassword(
                    auth,
                    values.email.trim().toLowerCase(),
                    values.password,
                );
                await sendEmailVerification(credential.user);
                // Reserve the verified PIN against this Firebase identity so it
                // can be consumed when the user verifies their email and logs in.
                if (pinCode) {
                    try {
                        const csrf =
                            document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
                        await fetch(route('register.pin.reserve'), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({
                                code: pinCode.toUpperCase(),
                                email: values.email.trim().toLowerCase(),
                                firebase_uid: credential.user.uid,
                            }),
                        });
                    } catch {
                        // Non-fatal: registration succeeded; if reservation failed the user
                        // can still log in but will get the default role.
                    }
                }
                notifySuccess(t('login.registerSuccess'));
                resetPinState();
                form.reset();
                toggle();
                setEmailLoading(false);
            }
        } catch (e: any) {
            const code: string = e?.code ?? '';
            if (code === 'auth/invalid-credential' || code === 'auth/user-not-found' || code === 'auth/wrong-password') {
                notifyError(t('errors.wrongPassword'));
            } else if (code === 'auth/email-already-in-use') {
                form.setFieldError('email', t('errors.emailInUse'));
            } else if (code === 'auth/weak-password') {
                form.setFieldError('password', t('errors.passwordMustContain', { requirements: t('errors.req8chars') }));
            } else if (code === 'auth/too-many-requests') {
                notifyError(t('errors.tooManyRequests'));
            } else {
                notifyError(t('errors.generic'));
            }
            setEmailLoading(false);
        }
    });

    return (
        <AuthLayout>
            <Head title={upperFirst(t(`login.${type === 'login' ? 'title' : 'register'}`))} />

            <Paper radius="md" p="xl" withBorder maw={480} w="100%">
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

                <form onSubmit={handleSubmit}>
                    <Stack>
                        {type === 'register' && pinRoleLabel && (
                            <Alert
                                variant="light"
                                color="blue"
                                icon={<IconShieldCheck size={18} />}
                                radius="md"
                                py="xs"
                            >
                                <Group gap="xs" wrap="nowrap">
                                    <Text size="sm">{t('pin.registeringAs')}</Text>
                                    <Badge color="blue" variant="filled">
                                        {pinRoleLabel}
                                    </Badge>
                                </Group>
                            </Alert>
                        )}
                        {type === 'register' && (
                            <TextInput
                                label={t('login.nameLabel')}
                                placeholder={t('login.namePlaceholder')}
                                radius="md"
                                value={form.values.name}
                                onChange={(e) => form.setFieldValue('name', e.currentTarget.value)}
                            />
                        )}
                        <TextInput
                            required
                            label={t('login.emailLabel')}
                            placeholder={t('login.emailPlaceholder')}
                            radius="md"
                            autoComplete="email"
                            value={form.values.email}
                            onChange={(e) => form.setFieldValue('email', e.currentTarget.value)}
                            error={form.errors.email}
                        />
                        <PasswordInput
                            required
                            label={t('login.passwordLabel')}
                            placeholder={
                                type === 'register'
                                    ? t('login.passwordRegisterPlaceholder')
                                    : t('login.passwordPlaceholder')
                            }
                            radius="md"
                            autoComplete={type === 'register' ? 'new-password' : 'current-password'}
                            value={form.values.password}
                            onChange={(e) => form.setFieldValue('password', e.currentTarget.value)}
                            error={form.errors.password}
                        />
                        {type === 'register' && (() => {
                            const pwd = form.values.password;
                            const strength = getPasswordStrength(pwd);
                            const strengthColor = strength > 80 ? 'teal' : strength > 50 ? 'yellow' : 'red';
                            return (
                                <Box>
                                    <Group gap={5} grow mt={-4} mb="xs">
                                        {Array(4)
                                            .fill(0)
                                            .map((_, index) => (
                                                <Progress
                                                    styles={{ section: { transitionDuration: '0ms' } }}
                                                    value={
                                                        pwd.length > 0 && index === 0
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
                                            ))}
                                    </Group>
                                    <PasswordRequirement
                                        label={t('errors.req8chars')}
                                        meets={pwd.length > 7}
                                    />
                                    {passwordRequirements.map((requirement) => (
                                        <PasswordRequirement
                                            key={requirement.key}
                                            label={t(`errors.${requirement.key}`)}
                                            meets={requirement.re.test(pwd)}
                                        />
                                    ))}
                                </Box>
                            );
                        })()}
                        {type === 'register' && (
                            <Checkbox
                                label={t('login.acceptTerms')}
                                checked={form.values.terms}
                                onChange={(e) => form.setFieldValue('terms', e.currentTarget.checked)}
                            />
                        )}
                    </Stack>

                    <Group justify="space-between" mt="xl">
                        <Anchor
                            component="button"
                            type="button"
                            size="xs"
                            onClick={type === 'login' ? handleRegisterAnchor : handleToggle}
                        >
                            {type === 'register' ? t('login.toLogin') : t('login.toRegister')}
                        </Anchor>
                        <Button type="submit" loading={emailLoading}>
                            {type === 'login' ? t('login.title') : t('login.register')}
                        </Button>
                    </Group>
                </form>
            </Paper>

            <Modal
                opened={pinModalOpened}
                onClose={closePinModal}
                title={t('pin.title')}
                centered
                radius="md"
            >
                <Stack gap="md">
                    <Text size="sm" c="dimmed">
                        {t('pin.description')}
                    </Text>
                    <Center>
                        <PinInput
                            length={6}
                            type="alphanumeric"
                            oneTimeCode
                            size="lg"
                            autoFocus
                            value={pinCode}
                            onChange={(v) => {
                                setPinCode(v.toUpperCase());
                                if (pinError) setPinError(null);
                            }}
                            onComplete={() => {
                                void handleVerifyPin();
                            }}
                        />
                    </Center>
                    {pinError && (
                        <Text c="red" size="sm" ta="center">
                            {pinError}
                        </Text>
                    )}
                    <Group justify="flex-end" mt="xs">
                        <Button variant="default" onClick={closePinModal} disabled={pinVerifying}>
                            {t('pin.cancel')}
                        </Button>
                        <Button
                            onClick={handleVerifyPin}
                            loading={pinVerifying}
                            disabled={pinCode.length < 6}
                        >
                            {t('pin.verify')}
                        </Button>
                    </Group>
                </Stack>
            </Modal>
        </AuthLayout>
    );
}
