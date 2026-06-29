import { router } from '@inertiajs/react';
import { Button, Divider, Group, Modal, Select, Stack, Text } from '@mantine/core';
import { useForm } from '@mantine/form';
import { notifications } from '@mantine/notifications';
import { IconAlertTriangle } from '@tabler/icons-react';
import { useTranslation } from 'react-i18next';

interface UserRow {
    id: string;
    display_name: string | null;
    email: string;
    role: string;
    organization: string | null;
    country: string | null;
    created_at: string | null;
}

interface EditRoleModalProps {
    user: UserRow | null;
    onClose: () => void;
}

export default function EditRoleModal({ user, onClose }: EditRoleModalProps) {
    const { t } = useTranslation('users');

    const form = useForm({
        initialValues: { role: user?.role ?? '' },
    });

    // Sync form when a different user is selected
    if (user && form.values.role !== user.role && !form.isDirty()) {
        form.setFieldValue('role', user.role);
    }

    const handleClose = () => {
        form.reset();
        onClose();
    };

    const handleSubmit = form.onSubmit((values) => {
        if (!user) return;
        router.patch(
            route('users.update', user.id),
            { role: values.role },
            {
                onSuccess: () => handleClose(),
                onError: () => {
                    notifications.show({
                        color: 'red',
                        message: t('editRoleModal.errors.failed'),
                        icon: <IconAlertTriangle size={18} />,
                        autoClose: false,
                        withCloseButton: true,
                        withBorder: true,
                    });
                },
            },
        );
    });

    const roleOptions = [
        { value: 'admin', label: t('roles.admin') },
        { value: 'manager', label: t('roles.manager') },
        { value: 'clerk', label: t('roles.clerk') },
    ];

    return (
        <Modal
            opened={!!user}
            onClose={handleClose}
            title={t('editRoleModal.title')}
            size="sm"
        >
            {user && (
                <form onSubmit={handleSubmit}>
                    <Stack gap="md">
                        <div>
                            <Text size="xs" fw={600} c="dimmed" mb={2}>{t('editRoleModal.userDetails')}</Text>
                            <Text fw={500}>{user.display_name ?? user.email}</Text>
                            <Text size="sm" c="dimmed">{user.email}</Text>
                            {user.organization && (
                                <Text size="sm" c="dimmed">{user.organization}</Text>
                            )}
                        </div>

                        <Select
                            label={t('editRoleModal.role')}
                            data={roleOptions}
                            required
                            {...form.getInputProps('role')}
                        />

                        <Divider />

                        <Group justify="flex-end">
                            <Button variant="subtle" onClick={handleClose}>
                                {t('editRoleModal.cancel')}
                            </Button>
                            <Button type="submit">{t('editRoleModal.submit')}</Button>
                        </Group>
                    </Stack>
                </form>
            )}
        </Modal>
    );
}
