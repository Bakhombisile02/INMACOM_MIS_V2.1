import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import {
    ActionIcon,
    Avatar,
    Badge,
    Box,
    Button,
    Code,
    Collapse,
    Container,
    Flex,
    Group,
    Menu,
    Modal,
    Pagination,
    Select,
    Stack,
    Table,
    Text,
    TextInput,
    Title,
    Tooltip,
} from '@mantine/core';
import { useDisclosure, useDebouncedCallback } from '@mantine/hooks';
import { notifications } from '@mantine/notifications';
import {
    IconAlertTriangle,
    IconCheck,
    IconChevronDown,
    IconChevronUp,
    IconCopy,
    IconDots,
    IconKey,
    IconMail,
    IconPencil,
    IconRefresh,
    IconSearch,
    IconTrash,
    IconUserPlus,
    IconX,
} from '@tabler/icons-react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InviteModal from './partials/InviteModal';
import EditRoleModal from './partials/EditRoleModal';

interface UserRow {
    id: string;
    display_name: string | null;
    email: string;
    role: string;
    photo_url: string | null;
    organization: string | null;
    country: string | null;
    created_at: string | null;
}

interface InvitationRow {
    id: string;
    code: string;
    role: string;
    created_at: string | null;
    expires_at: string | null;
    sent_to_email: string | null;
    invite_url: string | null;
    status: 'available' | 'used' | 'revoked' | 'expired' | 'linkOpened';
}

interface PaginatedUsers {
    data: UserRow[];
    current_page: number;
    last_page: number;
    total: number;
}

interface UsersIndexProps {
    users: PaginatedUsers;
    invitations: InvitationRow[];
    filters: { search: string; role: string };
    isAdmin: boolean;
}

const ROLE_COLORS: Record<string, string> = {
    admin: 'red',
    manager: 'orange',
    clerk: 'blue',
};

const STATUS_COLORS: Record<string, string> = {
    available: 'green',
    used: 'gray',
    revoked: 'red',
    expired: 'yellow',
    linkOpened: 'orange',
};

export default function UsersIndex({ users, invitations, filters, isAdmin }: UsersIndexProps) {
    const { t } = useTranslation('users');
    const { t: tNav } = useTranslation('navigation');

    const [tab, setTab] = useState<'users' | 'invitations'>('users');
    const [inviteOpened, inviteHandlers] = useDisclosure(false);
    const [editUser, setEditUser] = useState<UserRow | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<UserRow | null>(null);
    const [archiveOpened, { toggle: toggleArchive }] = useDisclosure(false);
    const [resendTarget, setResendTarget] = useState<InvitationRow | null>(null);
    const [resendEmail, setResendEmail] = useState('');
    const [resendLoading, setResendLoading] = useState(false);

    const activeInvitations = invitations.filter(
        (inv) => inv.status === 'available' || inv.status === 'linkOpened',
    );
    const archivedInvitations = invitations.filter(
        (inv) => inv.status !== 'available' && inv.status !== 'linkOpened',
    );

    const expiryCountdown = (expiresAt: string): string => {
        const diff = new Date(expiresAt).getTime() - Date.now();
        if (diff <= 0) return t('invitations.expiredLabel');
        const days = Math.floor(diff / 86_400_000);
        const hours = Math.floor((diff % 86_400_000) / 3_600_000);
        const mins = Math.floor((diff % 3_600_000) / 60_000);
        if (days > 0) return `${days}d ${hours}h`;
        if (hours > 0) return `${hours}h ${mins}m`;
        return `${mins}m`;
    };

    const expiryColor = (expiresAt: string): string | undefined => {
        const diff = new Date(expiresAt).getTime() - Date.now();
        if (diff <= 0) return 'red';
        if (diff < 86_400_000) return 'orange';      // < 1 day
        if (diff < 3 * 86_400_000) return 'yellow';  // < 3 days
        return undefined;
    };

    const debouncedSearch = useDebouncedCallback((value: string) => {
        router.get(
            route('users.index'),
            { search: value, role: filters.role },
            { preserveState: true, replace: true },
        );
    }, 350);

    const handleRoleFilter = (value: string | null) => {
        router.get(
            route('users.index'),
            { search: filters.search, role: value ?? '' },
            { preserveState: true, replace: true },
        );
    };

    const handlePageChange = (page: number) => {
        router.get(
            route('users.index'),
            { search: filters.search, role: filters.role, page },
            { preserveState: true },
        );
    };

    const handlePasswordReset = (user: UserRow) => {
        router.post(
            route('users.password-reset', user.id),
            {},
            {
                onSuccess: () =>
                    notifications.show({
                        color: 'green',
                        message: t('passwordReset.success', { email: user.email }),
                        icon: <IconCheck size={18} />,
                        autoClose: 5000,
                        withBorder: true,
                    }),
                onError: () =>
                    notifications.show({
                        color: 'red',
                        message: t('passwordReset.failed'),
                        icon: <IconAlertTriangle size={18} />,
                        autoClose: false,
                        withCloseButton: true,
                        withBorder: true,
                    }),
            },
        );
    };

    const handleDelete = (user: UserRow) => {
        setDeleteTarget(user);
    };

    const confirmDelete = () => {
        if (!deleteTarget) return;
        router.delete(route('users.destroy', deleteTarget.id), {
            onSuccess: () => setDeleteTarget(null),
            onError: () => {
                setDeleteTarget(null);
                notifications.show({
                    color: 'red',
                    message: t('deleteConfirm.title'),
                    icon: <IconAlertTriangle size={18} />,
                    autoClose: false,
                    withCloseButton: true,
                    withBorder: true,
                });
            },
        });
    };

    const handleRevoke = (inv: InvitationRow) => {
        router.delete(route('invites.destroy', inv.id), {
            onError: () =>
                notifications.show({
                    color: 'red',
                    message: t('inviteModal.errors.failed'),
                    icon: <IconAlertTriangle size={18} />,
                    autoClose: false,
                    withCloseButton: true,
                    withBorder: true,
                }),
        });
    };

    const handleCopyLink = async (inv: InvitationRow) => {
        if (!inv.invite_url) return;
        await navigator.clipboard.writeText(inv.invite_url);
        notifications.show({
            color: 'green',
            message: t('invitations.linkCopied'),
            icon: <IconCheck size={18} />,
            autoClose: 2000,
            withBorder: true,
        });
    };

    const handleGetNewLink = async (inv: InvitationRow) => {
        try {
            const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
            const res = await fetch(route('invites.resend', inv.id), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({}),
            });
            if (!res.ok) throw new Error();
            const data = await res.json();
            if (data.invite_url) {
                await navigator.clipboard.writeText(data.invite_url);
            }
            notifications.show({
                color: 'green',
                message: t('invitations.newLinkCopied'),
                icon: <IconCheck size={18} />,
                autoClose: 3000,
                withBorder: true,
            });
            router.reload({ only: ['invitations'] });
        } catch {
            notifications.show({
                color: 'red',
                message: t('resendModal.failed'),
                icon: <IconAlertTriangle size={18} />,
                autoClose: false,
                withCloseButton: true,
                withBorder: true,
            });
        }
    };

    const openResend = (inv: InvitationRow) => {
        setResendEmail(inv.sent_to_email ?? '');
        setResendTarget(inv);
    };

    const handleResend = async () => {
        if (!resendTarget) return;
        setResendLoading(true);
        try {
            const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
            const res = await fetch(route('invites.resend', resendTarget.id), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ sent_to_email: resendEmail || undefined }),
            });
            if (!res.ok) throw new Error();
            setResendTarget(null);
            notifications.show({
                color: 'green',
                message: resendEmail
                    ? t('resendModal.success', { email: resendEmail })
                    : t('resendModal.successNoEmail'),
                icon: <IconCheck size={18} />,
                autoClose: 5000,
                withBorder: true,
            });
            router.reload({ only: ['invitations'] });
        } catch {
            notifications.show({
                color: 'red',
                message: t('resendModal.failed'),
                icon: <IconAlertTriangle size={18} />,
                autoClose: false,
                withCloseButton: true,
                withBorder: true,
            });
        } finally {
            setResendLoading(false);
        }
    };

    const roleFilterOptions = [
        { value: '', label: t('allRoles') },
        { value: 'admin', label: t('roles.admin') },
        { value: 'manager', label: t('roles.manager') },
        { value: 'clerk', label: t('roles.clerk') },
    ];

    return (
        <>
            <Head title={tNav('users')} />

            <Container size="xl" py="xl">
                <Stack gap="lg">
                    {/* Header */}
                    <Group justify="space-between" align="flex-start">
                        <div>
                            <Title order={1}>{t('title')}</Title>
                            <Text c="dimmed">{t('subtitle')}</Text>
                        </div>
                        {isAdmin && (
                            <Button
                                leftSection={<IconUserPlus size={16} />}
                                onClick={inviteHandlers.open}
                            >
                                {t('inviteButton')}
                            </Button>
                        )}
                    </Group>

                    {/* Tab bar */}
                    <Flex gap="xs">
                        <Button
                            variant={tab === 'users' ? 'filled' : 'subtle'}
                            onClick={() => setTab('users')}
                            size="sm"
                        >
                            {t('tabs.users')}
                        </Button>
                        {isAdmin && (
                            <Button
                                variant={tab === 'invitations' ? 'filled' : 'subtle'}
                                onClick={() => setTab('invitations')}
                                size="sm"
                            >
                                {t('tabs.invitations')}
                                {invitations.filter((i) => i.status === 'available').length > 0 && (
                                    <Badge ml={6} size="xs" circle variant="filled" color="blue">
                                        {invitations.filter((i) => i.status === 'available').length}
                                    </Badge>
                                )}
                            </Button>
                        )}
                    </Flex>

                    {/* ---- USERS TAB ---- */}
                    {tab === 'users' && (
                        <Stack gap="md">
                            {/* Toolbar */}
                            <Group>
                                <TextInput
                                    placeholder={t('search')}
                                    leftSection={<IconSearch size={16} />}
                                    defaultValue={filters.search}
                                    onChange={(e) => debouncedSearch(e.currentTarget.value)}
                                    radius="md"
                                    style={{ flex: 1 }}
                                />
                                <Select
                                    placeholder={t('filterRole')}
                                    data={roleFilterOptions}
                                    value={filters.role || ''}
                                    onChange={handleRoleFilter}
                                    clearable={false}
                                    radius="md"
                                    style={{ width: 180 }}
                                />
                            </Group>

                            {/* Table */}
                            <Table.ScrollContainer minWidth={600}>
                                <Table striped highlightOnHover withTableBorder withColumnBorders={false}>
                                    <Table.Thead>
                                        <Table.Tr>
                                            <Table.Th>{t('columns.user')}</Table.Th>
                                            <Table.Th>{t('columns.role')}</Table.Th>
                                            <Table.Th>{t('columns.organisation')}</Table.Th>
                                            <Table.Th>{t('columns.joined')}</Table.Th>
                                            {isAdmin && <Table.Th style={{ width: 60 }} />}
                                        </Table.Tr>
                                    </Table.Thead>
                                    <Table.Tbody>
                                        {users.data.length === 0 ? (
                                            <Table.Tr>
                                                <Table.Td colSpan={isAdmin ? 5 : 4}>
                                                    <Text c="dimmed" ta="center" py="xl">{t('empty')}</Text>
                                                </Table.Td>
                                            </Table.Tr>
                                        ) : (
                                            users.data.map((user) => (
                                                <Table.Tr key={user.id}>
                                                    <Table.Td>
                                                        <Group gap="sm">
                                                            <Avatar
                                                                src={user.photo_url}
                                                                name={user.display_name ?? user.email}
                                                                size="sm"
                                                                color="blue"
                                                            />
                                                            <div>
                                                                <Text size="sm" fw={500} lineClamp={1}>
                                                                    {user.display_name ?? '—'}
                                                                </Text>
                                                                <Text size="xs" c="dimmed" lineClamp={1}>
                                                                    {user.email}
                                                                </Text>
                                                            </div>
                                                        </Group>
                                                    </Table.Td>
                                                    <Table.Td>
                                                        <Badge
                                                            color={ROLE_COLORS[user.role] ?? 'gray'}
                                                            variant="light"
                                                            size="sm"
                                                        >
                                                            {t(`roles.${user.role}` as const)}
                                                        </Badge>
                                                    </Table.Td>
                                                    <Table.Td>
                                                        <Text size="sm">{user.organization ?? '—'}</Text>
                                                        {user.country && (
                                                            <Text size="xs" c="dimmed">{user.country}</Text>
                                                        )}
                                                    </Table.Td>
                                                    <Table.Td>
                                                        <Text size="sm">{user.created_at ?? '—'}</Text>
                                                    </Table.Td>
                                                    {isAdmin && (
                                                        <Table.Td>
                                                            <Menu withinPortal position="bottom-end" shadow="md">
                                                                <Menu.Target>
                                                                    <ActionIcon variant="subtle" color="gray">
                                                                        <IconDots size={16} />
                                                                    </ActionIcon>
                                                                </Menu.Target>
                                                                <Menu.Dropdown>
                                                                    <Menu.Item
                                                                        leftSection={<IconPencil size={14} />}
                                                                        onClick={() => setEditUser(user)}
                                                                    >
                                                                        {t('actions.editRole')}
                                                                    </Menu.Item>
                                                                    <Menu.Item
                                                                        leftSection={<IconKey size={14} />}
                                                                        onClick={() => handlePasswordReset(user)}
                                                                    >
                                                                        {t('actions.resetPassword')}
                                                                    </Menu.Item>
                                                                    <Menu.Divider />
                                                                    <Menu.Item
                                                                        leftSection={<IconTrash size={14} />}
                                                                        color="red"
                                                                        onClick={() => handleDelete(user)}
                                                                    >
                                                                        {t('actions.delete')}
                                                                    </Menu.Item>
                                                                </Menu.Dropdown>
                                                            </Menu>
                                                        </Table.Td>
                                                    )}
                                                </Table.Tr>
                                            ))
                                        )}
                                    </Table.Tbody>
                                </Table>
                            </Table.ScrollContainer>

                            {/* Pagination */}
                            {users.last_page > 1 && (
                                <Group justify="center">
                                    <Pagination
                                        total={users.last_page}
                                        value={users.current_page}
                                        onChange={handlePageChange}
                                        size="sm"
                                    />
                                </Group>
                            )}
                        </Stack>
                    )}

                    {/* ---- INVITATIONS TAB ---- */}
                    {tab === 'invitations' && isAdmin && (
                        <Stack gap="md">
                            {/* Active invitations */}
                            <Table.ScrollContainer minWidth={700}>
                                <Table striped highlightOnHover withTableBorder withColumnBorders={false}>
                                    <Table.Thead>
                                        <Table.Tr>
                                            <Table.Th>{t('invitations.columns.code')}</Table.Th>
                                            <Table.Th>{t('invitations.columns.role')}</Table.Th>
                                            <Table.Th>{t('invitations.columns.created')}</Table.Th>
                                            <Table.Th>{t('invitations.columns.expires')}</Table.Th>
                                            <Table.Th>{t('invitations.columns.sentTo')}</Table.Th>
                                            <Table.Th>{t('invitations.columns.status')}</Table.Th>
                                            <Table.Th style={{ width: 40 }} />
                                        </Table.Tr>
                                    </Table.Thead>
                                    <Table.Tbody>
                                        {activeInvitations.length === 0 ? (
                                            <Table.Tr>
                                                <Table.Td colSpan={7}>
                                                    <Text c="dimmed" ta="center" py="xl">{t('invitations.empty')}</Text>
                                                </Table.Td>
                                            </Table.Tr>
                                        ) : (
                                            activeInvitations.map((inv) => (
                                                <Table.Tr key={inv.id}>
                                                    <Table.Td>
                                                        <Code>{inv.code}</Code>
                                                    </Table.Td>
                                                    <Table.Td>
                                                        <Badge color={ROLE_COLORS[inv.role] ?? 'gray'} variant="light" size="sm">
                                                            {t(`roles.${inv.role}` as const)}
                                                        </Badge>
                                                    </Table.Td>
                                                    <Table.Td>
                                                        <Text size="sm">{inv.created_at ?? '—'}</Text>
                                                    </Table.Td>
                                                    <Table.Td>
                                                        {inv.expires_at ? (
                                                            <Tooltip label={new Date(inv.expires_at).toLocaleString()}>
                                                                <Text size="sm" c={expiryColor(inv.expires_at)} style={{ cursor: 'default' }}>
                                                                    {expiryCountdown(inv.expires_at)}
                                                                </Text>
                                                            </Tooltip>
                                                        ) : (
                                                            <Text size="sm" c="dimmed">—</Text>
                                                        )}
                                                    </Table.Td>
                                                    <Table.Td>
                                                        <Text size="sm" c={inv.sent_to_email ? undefined : 'dimmed'}>
                                                            {inv.sent_to_email ?? '—'}
                                                        </Text>
                                                    </Table.Td>
                                                    <Table.Td>
                                                        <Badge color={STATUS_COLORS[inv.status] ?? 'gray'} variant="light" size="sm">
                                                            {t(`invitations.status.${inv.status}` as const)}
                                                        </Badge>
                                                    </Table.Td>
                                                    <Table.Td>
                                                        <Menu withinPortal position="bottom-end" shadow="md">
                                                            <Menu.Target>
                                                                <ActionIcon variant="subtle" color="gray">
                                                                    <IconDots size={16} />
                                                                </ActionIcon>
                                                            </Menu.Target>
                                                            <Menu.Dropdown>
                                                                {inv.status === 'available' && inv.invite_url && (
                                                                    <Menu.Item
                                                                        leftSection={<IconCopy size={14} />}
                                                                        onClick={() => handleCopyLink(inv)}
                                                                    >
                                                                        {t('actions.copyLink')}
                                                                    </Menu.Item>
                                                                )}
                                                                {inv.status === 'linkOpened' && (
                                                                    <Menu.Item
                                                                        leftSection={<IconRefresh size={14} />}
                                                                        onClick={() => handleGetNewLink(inv)}
                                                                    >
                                                                        {t('actions.newLink')}
                                                                    </Menu.Item>
                                                                )}
                                                                <Menu.Item
                                                                    leftSection={<IconMail size={14} />}
                                                                    onClick={() => openResend(inv)}
                                                                >
                                                                    {t('actions.resend')}
                                                                </Menu.Item>
                                                                <Menu.Divider />
                                                                <Menu.Item
                                                                    leftSection={<IconX size={14} />}
                                                                    color="red"
                                                                    onClick={() => handleRevoke(inv)}
                                                                >
                                                                    {t('actions.revoke')}
                                                                </Menu.Item>
                                                            </Menu.Dropdown>
                                                        </Menu>
                                                    </Table.Td>
                                                </Table.Tr>
                                            ))
                                        )}
                                    </Table.Tbody>
                                </Table>
                            </Table.ScrollContainer>

                            {/* Archived invitations */}
                            {archivedInvitations.length > 0 && (
                                <Box>
                                    <Button
                                        variant="subtle"
                                        size="xs"
                                        color="gray"
                                        onClick={toggleArchive}
                                        rightSection={archiveOpened ? <IconChevronUp size={14} /> : <IconChevronDown size={14} />}
                                    >
                                        {archiveOpened
                                            ? t('invitations.archive.hide')
                                            : t('invitations.archive.show', { count: archivedInvitations.length })}
                                    </Button>
                                    <Collapse in={archiveOpened}>
                                        <Box mt="sm">
                                            <Table.ScrollContainer minWidth={600}>
                                                <Table withTableBorder withColumnBorders={false} opacity={0.75}>
                                                    <Table.Thead>
                                                        <Table.Tr>
                                                            <Table.Th>{t('invitations.columns.code')}</Table.Th>
                                                            <Table.Th>{t('invitations.columns.role')}</Table.Th>
                                                            <Table.Th>{t('invitations.columns.created')}</Table.Th>
                                                            <Table.Th>{t('invitations.columns.sentTo')}</Table.Th>
                                                            <Table.Th>{t('invitations.columns.status')}</Table.Th>
                                                        </Table.Tr>
                                                    </Table.Thead>
                                                    <Table.Tbody>
                                                        {archivedInvitations.map((inv) => (
                                                            <Table.Tr key={inv.id}>
                                                                <Table.Td><Code>{inv.code}</Code></Table.Td>
                                                                <Table.Td>
                                                                    <Badge color={ROLE_COLORS[inv.role] ?? 'gray'} variant="light" size="sm">
                                                                        {t(`roles.${inv.role}` as const)}
                                                                    </Badge>
                                                                </Table.Td>
                                                                <Table.Td><Text size="sm">{inv.created_at ?? '—'}</Text></Table.Td>
                                                                <Table.Td>
                                                                    <Text size="sm" c={inv.sent_to_email ? undefined : 'dimmed'}>
                                                                        {inv.sent_to_email ?? '—'}
                                                                    </Text>
                                                                </Table.Td>
                                                                <Table.Td>
                                                                    <Badge color={STATUS_COLORS[inv.status] ?? 'gray'} variant="light" size="sm">
                                                                        {t(`invitations.status.${inv.status}` as const)}
                                                                    </Badge>
                                                                </Table.Td>
                                                            </Table.Tr>
                                                        ))}
                                                    </Table.Tbody>
                                                </Table>
                                            </Table.ScrollContainer>
                                        </Box>
                                    </Collapse>
                                </Box>
                            )}
                        </Stack>
                    )}
                </Stack>
            </Container>

            {/* Modals */}
            <InviteModal opened={inviteOpened} onClose={inviteHandlers.close} />
            <EditRoleModal user={editUser} onClose={() => setEditUser(null)} />

            {/* Resend invite modal */}
            <Modal
                opened={!!resendTarget}
                onClose={() => !resendLoading && setResendTarget(null)}
                title={t('resendModal.title')}
                size="sm"
            >
                {resendTarget && (
                    <Stack gap="md">
                        {resendTarget.status === 'linkOpened' && (
                            <Text size="sm" c="orange">
                                {t('resendModal.regenerateNotice')}
                            </Text>
                        )}
                        <Text size="sm" c="dimmed">
                            {t('resendModal.description')}
                        </Text>
                        <TextInput
                            label={t('resendModal.emailLabel')}
                            placeholder={t('resendModal.emailPlaceholder')}
                            value={resendEmail}
                            onChange={(e) => setResendEmail(e.currentTarget.value)}
                            type="email"
                            required
                        />
                        <Group justify="flex-end">
                            <Button
                                variant="subtle"
                                onClick={() => setResendTarget(null)}
                                disabled={resendLoading}
                            >
                                {t('resendModal.cancel')}
                            </Button>
                            <Button
                                onClick={handleResend}
                                loading={resendLoading}
                                disabled={!resendEmail.trim()}
                                leftSection={<IconMail size={16} />}
                            >
                                {t('resendModal.submit')}
                            </Button>
                        </Group>
                    </Stack>
                )}
            </Modal>

            {/* Delete confirmation modal */}
            <Modal
                opened={!!deleteTarget}
                onClose={() => setDeleteTarget(null)}
                title={t('deleteConfirm.title')}
                size="sm"
            >
                {deleteTarget && (
                    <Stack gap="md">
                        <Text size="sm">
                            {t('deleteConfirm.message', { name: deleteTarget.display_name ?? deleteTarget.email })}
                        </Text>
                        <Group justify="flex-end">
                            <Button variant="subtle" onClick={() => setDeleteTarget(null)}>
                                {t('deleteConfirm.cancel')}
                            </Button>
                            <Button color="red" onClick={confirmDelete}>
                                {t('deleteConfirm.confirm')}
                            </Button>
                        </Group>
                    </Stack>
                )}
            </Modal>
        </>
    );
}

UsersIndex.layout = (page: React.ReactNode) => <AuthenticatedLayout>{page}</AuthenticatedLayout>;
