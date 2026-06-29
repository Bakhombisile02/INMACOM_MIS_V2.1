import { useEffect, useState, useCallback } from 'react';
import { ActionIcon, Indicator, Menu, ScrollArea, Stack, Text, Group, Anchor, Badge } from '@mantine/core';
import { IconBell, IconBellRinging } from '@tabler/icons-react';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

type MentionRow = {
    id: string;
    comment_id: string;
    read_at: string | null;
    created_at: string;
    comment: {
        id: string;
        body: string;
        field_name: string | null;
        commentable_type: string;
        commentable_id: string;
        created_at: string;
        author: { id: string; display_name: string } | null;
    };
};

function getCsrf(): string {
    const meta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
    return meta?.content ?? '';
}

export default function NotificationsBell() {
    const { t } = useTranslation('approvals');
    const page = usePage();
    const sharedCount = (page.props as { unreadMentions?: number }).unreadMentions ?? 0;
    const [opened, setOpened] = useState(false);
    const [count, setCount] = useState<number>(sharedCount);
    const [items, setItems] = useState<MentionRow[]>([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        setCount(sharedCount);
    }, [sharedCount]);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const res = await fetch('/comments/mentions/me', {
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return;
            const data = (await res.json()) as { mentions: MentionRow[] };
            setItems(data.mentions);
        } finally {
            setLoading(false);
        }
    }, []);

    const markRead = useCallback(async () => {
        try {
            await fetch('/comments/mentions/read', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({}),
            });
            setCount(0);
            setItems((prev) => prev.map((m) => ({ ...m, read_at: m.read_at ?? new Date().toISOString() })));
        } catch {
            /* ignore */
        }
    }, []);

    const onOpen = () => {
        setOpened(true);
        void load();
    };

    const onClose = () => {
        setOpened(false);
        if (count > 0) {
            void markRead();
        }
    };

    return (
        <Menu opened={opened} onChange={(o) => (o ? onOpen() : onClose())} position="bottom-end" width={380} shadow="md">
            <Menu.Target>
                <Indicator
                    color="red"
                    size={16}
                    offset={4}
                    disabled={count === 0}
                    label={count > 99 ? '99+' : count || undefined}
                >
                    <ActionIcon variant="subtle" aria-label={t('notifications.title')}>
                        {count > 0 ? <IconBellRinging size={20} /> : <IconBell size={20} />}
                    </ActionIcon>
                </Indicator>
            </Menu.Target>
            <Menu.Dropdown>
                <Menu.Label>
                    <Group justify="space-between">
                        <Text fw={600}>{t('notifications.title')}</Text>
                        {count > 0 && (
                            <Badge size="xs" color="red" variant="filled">
                                {count}
                            </Badge>
                        )}
                    </Group>
                </Menu.Label>
                <ScrollArea.Autosize mah={420}>
                    {loading ? (
                        <Text size="sm" c="dimmed" p="md">
                            {t('notifications.loading', { defaultValue: 'Loading…' })}
                        </Text>
                    ) : items.length === 0 ? (
                        <Text size="sm" c="dimmed" p="md">
                            {t('notifications.empty')}
                        </Text>
                    ) : (
                        <Stack gap={0}>
                            {items.map((m) => (
                                <MentionItem key={m.id} row={m} />
                            ))}
                        </Stack>
                    )}
                </ScrollArea.Autosize>
            </Menu.Dropdown>
        </Menu>
    );
}

function MentionItem({ row }: { row: MentionRow }) {
    const isUnread = !row.read_at;
    const c = row.comment;
    return (
        <Anchor
            href={hrefFor(c.commentable_type, c.commentable_id)}
            underline="never"
            style={{
                display: 'block',
                padding: '8px 12px',
                borderBottom: '1px solid var(--mantine-color-gray-2)',
                background: isUnread ? 'var(--mantine-color-blue-0)' : undefined,
                color: 'inherit',
            }}
        >
            <Group justify="space-between" gap={4}>
                <Text size="sm" fw={600}>
                    {c.author?.display_name ?? '—'}
                </Text>
                <Text size="xs" c="dimmed">
                    {new Date(c.created_at).toLocaleString()}
                </Text>
            </Group>
            {c.field_name && (
                <Badge size="xs" variant="light" mb={4}>
                    {c.field_name}
                </Badge>
            )}
            <Text size="sm" lineClamp={2}>
                {c.body}
            </Text>
        </Anchor>
    );
}

function hrefFor(type: string, id: string): string {
    switch (type) {
        case 'station':
        case 'station_revision':
            return `/stations#approvals-${id}`;
        case 'measurement':
            return `/gis/flow-levels#measurement-${id}`;
        case 'disaster_incident':
            return `/disaster#incident-${id}`;
        default:
            return '#';
    }
}
