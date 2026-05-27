import { useEffect, useState, useCallback, useRef, type ReactNode } from 'react';
import {
    Stack,
    Group,
    Avatar,
    Text,
    Textarea,
    Button,
    Badge,
    ActionIcon,
    Tooltip,
    Loader,
    Anchor,
    Box,
    Paper,
    UnstyledButton,
} from '@mantine/core';
import { IconCheck, IconRotate, IconTrash, IconCornerDownRight } from '@tabler/icons-react';
import { notifications } from '@mantine/notifications';
import { useTranslation } from 'react-i18next';
import { usePermissions } from '@/lib/permissions';
import type { CommentRecord, CommentMentionRef } from '@/types';

type SearchUser = { id: string; display_name: string; email: string; photo_url?: string | null };

type Props = {
    commentableType: 'measurement' | 'station' | 'station_revision' | 'disaster_incident';
    commentableId: string;
    /** Optional: filter to a single field discussion. */
    fieldName?: string | null;
    currentUserId?: string | null;
    /** Compact variant for inline embedding (e.g., inside a marker popover). */
    compact?: boolean;
};

type ApiCommentList = { comments: CommentRecord[] };
type ApiComment = { comment: CommentRecord };

function getCsrf(): string {
    const meta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
    return meta?.content ?? '';
}

async function jsonFetch<T>(url: string, init?: RequestInit): Promise<T> {
    const res = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrf(),
            'X-Requested-With': 'XMLHttpRequest',
            ...(init?.headers ?? {}),
        },
        ...init,
    });
    if (!res.ok) {
        const body = await res.text();
        throw new Error(body || `Request failed: ${res.status}`);
    }
    return res.json() as Promise<T>;
}

export default function CommentThread({
    commentableType,
    commentableId,
    fieldName = null,
    currentUserId = null,
    compact = false,
}: Props) {
    const { t } = useTranslation('approvals');
    const permissions = usePermissions();
    const [items, setItems] = useState<CommentRecord[]>([]);
    const [loading, setLoading] = useState(true);
    const [body, setBody] = useState('');
    const [replyTo, setReplyTo] = useState<string | null>(null);
    const [posting, setPosting] = useState(false);
    const textareaRef = useRef<HTMLTextAreaElement>(null);
    const [mentionQuery, setMentionQuery] = useState<string | null>(null);
    const [mentionUsers, setMentionUsers] = useState<SearchUser[]>([]);
    const [mentionLoading, setMentionLoading] = useState(false);
    const mentionTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

    const handleBodyChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
        const val = e.currentTarget.value;
        setBody(val);
        const pos = e.currentTarget.selectionStart ?? val.length;
        const textBefore = val.slice(0, pos);
        const match = /@([^\s@]*)$/.exec(textBefore);
        if (match) {
            const query = match[1];
            setMentionQuery(query);
            if (mentionTimer.current) clearTimeout(mentionTimer.current);
            mentionTimer.current = setTimeout(() => {
                setMentionLoading(true);
                jsonFetch<{ users: SearchUser[] }>(`/users/search?q=${encodeURIComponent(query)}`)
                    .then((data) => setMentionUsers(data.users))
                    .catch(() => setMentionUsers([]))
                    .finally(() => setMentionLoading(false));
            }, 200);
        } else {
            setMentionQuery(null);
            setMentionUsers([]);
            if (mentionTimer.current) clearTimeout(mentionTimer.current);
        }
    };

    const pickMention = (user: SearchUser) => {
        const ta = textareaRef.current;
        if (!ta) return;
        const pos = ta.selectionStart ?? body.length;
        const textBefore = body.slice(0, pos);
        const match = /@([^\s@]*)$/.exec(textBefore);
        if (!match) return;
        const start = pos - match[0].length;
        const token = `@[${user.id}]`;
        const newVal = body.slice(0, start) + token + ' ' + body.slice(pos);
        setBody(newVal);
        setMentionQuery(null);
        setMentionUsers([]);
        requestAnimationFrame(() => {
            ta.focus();
            const newPos = start + token.length + 1;
            ta.setSelectionRange(newPos, newPos);
        });
    };

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const qs = new URLSearchParams({
                commentable_type: commentableType,
                commentable_id: commentableId,
            });
            if (fieldName) qs.set('field_name', fieldName);
            const data = await jsonFetch<ApiCommentList>(`/comments?${qs.toString()}`);
            setItems(data.comments);
        } catch (e) {
            notifications.show({
                title: 'Failed to load comments',
                message: (e as Error).message,
                color: 'red',
                autoClose: false,
                withCloseButton: true,
            });
        } finally {
            setLoading(false);
        }
    }, [commentableType, commentableId, fieldName]);

    useEffect(() => {
        void load();
    }, [load]);

    const submit = async () => {
        const trimmed = body.trim();
        if (!trimmed) return;
        setPosting(true);
        try {
            const { comment } = await jsonFetch<ApiComment>('/comments', {
                method: 'POST',
                body: JSON.stringify({
                    commentable_type: commentableType,
                    commentable_id: commentableId,
                    parent_id: replyTo,
                    field_name: fieldName ?? null,
                    body: trimmed,
                }),
            });
            setItems((prev) => [...prev, comment]);
            setBody('');
            setReplyTo(null);
        } catch (e) {
            notifications.show({
                title: 'Failed to post comment',
                message: (e as Error).message,
                color: 'red',
                autoClose: false,
                withCloseButton: true,
            });
        } finally {
            setPosting(false);
        }
    };

    const resolve = async (id: string, currentlyResolved: boolean) => {
        try {
            const url = currentlyResolved ? `/comments/${id}/unresolve` : `/comments/${id}/resolve`;
            const { comment } = await jsonFetch<ApiComment>(url, { method: 'PATCH' });
            setItems((prev) => prev.map((c) => (c.id === id ? comment : c)));
        } catch (e) {
            notifications.show({
                title: 'Action failed',
                message: (e as Error).message,
                color: 'red',
                autoClose: false,
                withCloseButton: true,
            });
        }
    };

    const remove = async (id: string) => {
        try {
            await jsonFetch(`/comments/${id}`, { method: 'DELETE' });
            setItems((prev) => prev.filter((c) => c.id !== id && c.parent_id !== id));
        } catch (e) {
            notifications.show({
                title: 'Delete failed',
                message: (e as Error).message,
                color: 'red',
                autoClose: false,
                withCloseButton: true,
            });
        }
    };

    if (loading) {
        return (
            <Group justify="center" py="md">
                <Loader size="sm" />
            </Group>
        );
    }

    const roots = items.filter((c) => !c.parent_id);
    const repliesByParent = new Map<string, CommentRecord[]>();
    items
        .filter((c) => c.parent_id)
        .forEach((c) => {
            const list = repliesByParent.get(c.parent_id!) ?? [];
            list.push(c);
            repliesByParent.set(c.parent_id!, list);
        });

    return (
        <Stack gap={compact ? 'xs' : 'sm'}>
            {roots.length === 0 ? (
                <Text c="dimmed" size="sm">
                    {t('comments.empty')}
                </Text>
            ) : (
                roots.map((c) => (
                    <CommentItem
                        key={c.id}
                        comment={c}
                        replies={repliesByParent.get(c.id) ?? []}
                        currentUserId={currentUserId}
                        canResolve={permissions.canApprove}
                        canDeleteAny={permissions.isAdmin}
                        onResolve={resolve}
                        onDelete={remove}
                        onReply={(id) => setReplyTo(id)}
                        isReplying={replyTo === c.id}
                    />
                ))
            )}

            <Stack gap={4}>
                {replyTo && (
                    <Group gap={6}>
                        <IconCornerDownRight size={14} />
                        <Text size="xs" c="dimmed">
                            {t('comments.reply')}
                        </Text>
                        <Anchor size="xs" component="button" onClick={() => setReplyTo(null)}>
                            {t('actions.cancel', { ns: 'common' })}
                        </Anchor>
                    </Group>
                )}
                <div style={{ position: 'relative' }}>
                    <Textarea
                        ref={textareaRef}
                        placeholder={t('comments.placeholder')}
                        value={body}
                        onChange={handleBodyChange}
                        minRows={2}
                        autosize
                        maxRows={6}
                    />
                    {mentionQuery !== null && (
                        <Paper
                            withBorder
                            shadow="md"
                            style={{
                                position: 'absolute',
                                top: '100%',
                                left: 0,
                                right: 0,
                                zIndex: 300,
                                maxHeight: 220,
                                overflowY: 'auto',
                            }}
                        >
                            {mentionLoading ? (
                                <Group p="xs" justify="center">
                                    <Loader size="xs" />
                                </Group>
                            ) : mentionUsers.length === 0 ? (
                                <Text size="sm" c="dimmed" p="xs">
                                    No users found
                                </Text>
                            ) : (
                                mentionUsers.map((u) => (
                                    <UnstyledButton
                                        key={u.id}
                                        onClick={() => pickMention(u)}
                                        style={{ display: 'block', width: '100%' }}
                                    >
                                        <Group
                                            gap="xs"
                                            px="sm"
                                            py={6}
                                            style={{
                                                cursor: 'pointer',
                                                '&:hover': { backgroundColor: 'var(--mantine-color-gray-0)' },
                                            }}
                                        >
                                            <Avatar
                                                src={u.photo_url ?? undefined}
                                                size="sm"
                                                radius="xl"
                                            >
                                                {u.display_name.slice(0, 1).toUpperCase()}
                                            </Avatar>
                                            <div>
                                                <Text size="sm" fw={500} lh={1.2}>
                                                    {u.display_name}
                                                </Text>
                                                <Text size="xs" c="dimmed">
                                                    {u.email}
                                                </Text>
                                            </div>
                                        </Group>
                                    </UnstyledButton>
                                ))
                            )}
                        </Paper>
                    )}
                </div>
                <Group justify="flex-end">
                    <Button onClick={submit} loading={posting} disabled={!body.trim()}>
                        {t('comments.post')}
                    </Button>
                </Group>
            </Stack>
        </Stack>
    );
}

type CommentItemProps = {
    comment: CommentRecord;
    replies: CommentRecord[];
    currentUserId: string | null;
    canResolve: boolean;
    canDeleteAny: boolean;
    onResolve: (id: string, currentlyResolved: boolean) => void;
    onDelete: (id: string) => void;
    onReply: (id: string) => void;
    isReplying: boolean;
};

/**
 * Render a comment body, replacing `@[uuid]` tokens with highlighted @DisplayName spans.
 * Falls back to raw text for unresolved tokens.
 */
function renderMentions(body: string, mentions: CommentMentionRef[]): ReactNode {
    const nameMap = new Map<string, string>(
        mentions.filter((m) => m.user).map((m) => [m.user!.id, m.user!.display_name]),
    );
    const regex = /@\[([0-9a-f-]{36})\]/gi;
    const parts: ReactNode[] = [];
    let lastIndex = 0;
    let match: RegExpExecArray | null;
    while ((match = regex.exec(body)) !== null) {
        if (match.index > lastIndex) {
            parts.push(body.slice(lastIndex, match.index));
        }
        const name = nameMap.get(match[1]) ?? 'unknown';
        parts.push(
            <Text key={match.index} span c="blue.7" fw={600} size="sm">
                @{name}
            </Text>,
        );
        lastIndex = regex.lastIndex;
    }
    if (lastIndex < body.length) parts.push(body.slice(lastIndex));
    return parts.length > 0 ? parts : body;
}

function CommentItem({
    comment,
    replies,
    currentUserId,
    canResolve,
    canDeleteAny,
    onResolve,
    onDelete,
    onReply,
    isReplying,
}: CommentItemProps) {
    const { t } = useTranslation('approvals');
    const resolved = !!comment.resolved_at;
    const isAuthor = !!currentUserId && comment.author?.id === currentUserId;
    const canDelete = isAuthor || canDeleteAny;

    return (
        <Box
            style={{
                borderLeft: resolved ? '2px solid var(--mantine-color-green-5)' : '2px solid var(--mantine-color-gray-3)',
                paddingLeft: 8,
                opacity: resolved ? 0.7 : 1,
            }}
        >
            <Group gap="xs" wrap="nowrap" align="flex-start">
                <Avatar src={comment.author?.photo_url ?? undefined} size="sm" radius="xl">
                    {comment.author?.display_name?.slice(0, 1).toUpperCase() ?? '?'}
                </Avatar>
                <Stack gap={2} style={{ flex: 1 }}>
                    <Group gap="xs">
                        <Text size="sm" fw={600}>
                            {comment.author?.display_name ?? '—'}
                        </Text>
                        {comment.field_name && (
                            <Badge size="xs" variant="light">
                                {comment.field_name}
                            </Badge>
                        )}
                        {resolved && (
                            <Badge size="xs" color="green" variant="filled">
                                {t('comments.resolvedBadge')}
                            </Badge>
                        )}
                        <Text size="xs" c="dimmed">
                            {new Date(comment.created_at).toLocaleString()}
                        </Text>
                    </Group>
                    <Text size="sm" style={{ whiteSpace: 'pre-wrap' }}>
                        {renderMentions(comment.body, comment.mentions)}
                    </Text>
                    <Group gap="xs">
                        <Anchor size="xs" component="button" onClick={() => onReply(comment.id)}>
                            {t('comments.reply')}
                        </Anchor>
                        {canResolve && (
                            <Tooltip label={resolved ? t('actions.unresolve') : t('actions.resolve')}>
                                <ActionIcon
                                    size="xs"
                                    variant="subtle"
                                    color={resolved ? 'gray' : 'green'}
                                    onClick={() => onResolve(comment.id, resolved)}
                                >
                                    {resolved ? <IconRotate size={14} /> : <IconCheck size={14} />}
                                </ActionIcon>
                            </Tooltip>
                        )}
                        {canDelete && (
                            <Tooltip label={t('actions.delete')}>
                                <ActionIcon
                                    size="xs"
                                    variant="subtle"
                                    color="red"
                                    onClick={() => onDelete(comment.id)}
                                >
                                    <IconTrash size={14} />
                                </ActionIcon>
                            </Tooltip>
                        )}
                    </Group>
                    {replies.length > 0 && (
                        <Stack gap={4} mt={4} ml="md">
                            {replies.map((r) => (
                                <CommentItem
                                    key={r.id}
                                    comment={r}
                                    replies={[]}
                                    currentUserId={currentUserId}
                                    canResolve={canResolve}
                                    canDeleteAny={canDeleteAny}
                                    onResolve={onResolve}
                                    onDelete={onDelete}
                                    onReply={onReply}
                                    isReplying={false}
                                />
                            ))}
                        </Stack>
                    )}
                </Stack>
            </Group>
            {isReplying && (
                <Text size="xs" c="dimmed" mt={4}>
                    {t('comments.reply')} ↓
                </Text>
            )}
        </Box>
    );
}
