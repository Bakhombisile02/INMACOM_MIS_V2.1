import { useState } from 'react';
import {
    ActionIcon,
    Alert,
    Box,
    Button,
    Code,
    Divider,
    Group,
    Modal,
    Paper,
    Select,
    Stack,
    Text,
    TextInput,
    Textarea,
    Tooltip,
} from '@mantine/core';
import { useForm } from '@mantine/form';
import { notifications } from '@mantine/notifications';
import { IconAlertTriangle, IconCheck, IconCopy, IconSend } from '@tabler/icons-react';
import { useTranslation } from 'react-i18next';

interface InviteResult {
    pin: string;
    invite_url: string;
    role_label: string;
}

interface InviteModalProps {
    opened: boolean;
    onClose: () => void;
}

export default function InviteModal({ opened, onClose }: InviteModalProps) {
    const { t } = useTranslation('users');
    const [loading, setLoading] = useState(false);
    const [result, setResult] = useState<InviteResult | null>(null);
    const [copied, setCopied] = useState(false);

    const form = useForm({
        initialValues: {
            role: '',
            note: '',
            expires_in: 'never',
            sent_to_email: '',
        },
        validate: {
            role: (v) => (!v ? t('inviteModal.errors.role') : null),
        },
    });

    const handleClose = () => {
        form.reset();
        setResult(null);
        setCopied(false);
        onClose();
    };

    const handleSubmit = form.onSubmit(async (values) => {
        setLoading(true);
        try {
            const resp = await fetch(route('invites.store'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                    Accept: 'application/json',
                },
                body: JSON.stringify(values),
            });
            const data = await resp.json();
            if (!resp.ok) throw new Error(data.message ?? 'error');
            setResult(data as InviteResult);
        } catch {
            notifications.show({
                color: 'red',
                message: t('inviteModal.errors.failed'),
                icon: <IconAlertTriangle size={18} />,
                autoClose: false,
                withCloseButton: true,
                withBorder: true,
            });
        } finally {
            setLoading(false);
        }
    });

    const handleCopy = async () => {
        if (!result) return;
        await navigator.clipboard.writeText(result.invite_url);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const roleOptions = [
        { value: 'admin', label: t('roles.admin') },
        { value: 'manager', label: t('roles.manager') },
        { value: 'clerk', label: t('roles.clerk') },
    ];

    const expiryOptions = [
        { value: '24h', label: t('inviteModal.expiryOptions.24h') },
        { value: '7d', label: t('inviteModal.expiryOptions.7d') },
        { value: '30d', label: t('inviteModal.expiryOptions.30d') },
        { value: 'never', label: t('inviteModal.expiryOptions.never') },
    ];

    return (
        <Modal
            opened={opened}
            onClose={handleClose}
            title={t('inviteModal.title')}
            size="md"
            closeOnClickOutside={!result}
        >
            {result ? (
                <Stack gap="md">
                    <Text fw={600} size="lg">{t('inviteModal.resultTitle')}</Text>
                    <Text size="sm" c="dimmed">{t('inviteModal.resultSubtitle')}</Text>

                    <Box>
                        <Text size="xs" fw={600} c="dimmed" mb={4}>{t('inviteModal.pin')}</Text>
                        <Paper withBorder radius="md" p="md">
                            <Text
                                size="xl"
                                fw={700}
                                ff="monospace"
                                ta="center"
                                style={{ letterSpacing: '0.4em', fontSize: '1.75rem' }}
                            >
                                {result.pin}
                            </Text>
                        </Paper>
                    </Box>

                    <Box>
                        <Group justify="space-between" mb={4}>
                            <Text size="xs" fw={600} c="dimmed">{t('inviteModal.inviteLink')}</Text>
                            <Tooltip label={copied ? t('inviteModal.copied') : t('inviteModal.copyLink')}>
                                <ActionIcon
                                    variant={copied ? 'filled' : 'light'}
                                    color={copied ? 'green' : 'blue'}
                                    onClick={handleCopy}
                                    size="sm"
                                >
                                    {copied ? <IconCheck size={14} /> : <IconCopy size={14} />}
                                </ActionIcon>
                            </Tooltip>
                        </Group>
                        <Code block style={{ wordBreak: 'break-all', fontSize: 12 }}>
                            {result.invite_url}
                        </Code>
                    </Box>

                    <Alert
                        color="yellow"
                        icon={<IconAlertTriangle size={16} />}
                        variant="light"
                        radius="md"
                    >
                        <Text size="xs">{t('inviteModal.warning')}</Text>
                    </Alert>

                    <Group justify="flex-end" mt="xs">
                        <Button variant="subtle" onClick={() => { setResult(null); form.reset(); }}>
                            {t('inviteModal.generateAnother')}
                        </Button>
                        <Button onClick={handleClose}>{t('inviteModal.done')}</Button>
                    </Group>
                </Stack>
            ) : (
                <form onSubmit={handleSubmit}>
                    <Stack gap="md">
                        <Text size="sm" c="dimmed">{t('inviteModal.subtitle')}</Text>

                        <Select
                            label={t('inviteModal.role')}
                            placeholder={t('inviteModal.rolePlaceholder')}
                            data={roleOptions}
                            required
                            {...form.getInputProps('role')}
                        />

                        <Select
                            label={t('inviteModal.expiry')}
                            data={expiryOptions}
                            {...form.getInputProps('expires_in')}
                        />

                        <TextInput
                            label={t('inviteModal.sentToEmail')}
                            placeholder="someone@example.com"
                            description={t('inviteModal.sentToEmailDescription')}
                            leftSection={<IconSend size={16} />}
                            {...form.getInputProps('sent_to_email')}
                        />

                        <Textarea
                            label={t('inviteModal.note')}
                            placeholder={t('inviteModal.notePlaceholder')}
                            rows={2}
                            {...form.getInputProps('note')}
                        />

                        <Divider />

                        <Group justify="flex-end">
                            <Button variant="subtle" onClick={handleClose} disabled={loading}>
                                {t('editRoleModal.cancel')}
                            </Button>
                            <Button type="submit" loading={loading}>
                                {t('inviteModal.submit')}
                            </Button>
                        </Group>
                    </Stack>
                </form>
            )}
        </Modal>
    );
}
