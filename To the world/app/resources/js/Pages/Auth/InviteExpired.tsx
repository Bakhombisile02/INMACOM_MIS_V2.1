import { Head, Link } from '@inertiajs/react';
import { Anchor, Center, Stack, Text, Title } from '@mantine/core';
import { IconLinkOff } from '@tabler/icons-react';
import { useTranslation } from 'react-i18next';
import AuthLayout from '@/Layouts/AuthLayout';

export default function InviteExpired() {
    const { t } = useTranslation('auth');

    return (
        <AuthLayout>
            <Head title="Invitation Expired" />

            <Center style={{ minHeight: '60vh' }}>
                <Stack align="center" gap="md" maw={400} ta="center" px="md">
                    <IconLinkOff size={56} stroke={1.2} color="var(--mantine-color-gray-5)" />

                    <Title order={2}>Invitation Expired</Title>

                    <Text c="dimmed" size="sm">
                        This invitation link has already been used or has expired.
                        If you need access, ask an administrator to send you a new invite.
                    </Text>

                    <Anchor component={Link} href={route('login')} size="sm">
                        Back to login
                    </Anchor>
                </Stack>
            </Center>
        </AuthLayout>
    );
}
