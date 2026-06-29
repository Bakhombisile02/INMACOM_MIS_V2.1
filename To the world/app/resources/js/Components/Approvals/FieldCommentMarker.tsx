import { Popover, ActionIcon, Indicator, Tooltip, Box } from '@mantine/core';
import { IconMessageCircle } from '@tabler/icons-react';
import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import CommentThread from './CommentThread';

type Props = {
    commentableType: 'measurement' | 'station' | 'station_revision' | 'disaster_incident';
    commentableId: string;
    fieldName: string;
    currentUserId?: string | null;
    /** Optional unread-count hint (e.g., from a parent-fetched summary). */
    unresolvedCount?: number;
};

export default function FieldCommentMarker({
    commentableType,
    commentableId,
    fieldName,
    currentUserId = null,
    unresolvedCount = 0,
}: Props) {
    const { t } = useTranslation('approvals');
    const [opened, setOpened] = useState(false);
    const [count, setCount] = useState(unresolvedCount);

    useEffect(() => {
        setCount(unresolvedCount);
    }, [unresolvedCount]);

    return (
        <Popover opened={opened} onChange={setOpened} position="bottom-end" shadow="md" width={420} withArrow>
            <Popover.Target>
                <Tooltip label={t('actions.viewThread')} position="top" withArrow>
                    <Indicator
                        size={14}
                        offset={3}
                        color="red"
                        disabled={count === 0}
                        label={count > 99 ? '99+' : count || undefined}
                    >
                        <ActionIcon
                            variant="subtle"
                            size="sm"
                            color={count > 0 ? 'red' : 'gray'}
                            onClick={() => setOpened((o) => !o)}
                            aria-label={t('actions.viewThread')}
                        >
                            <IconMessageCircle size={16} />
                        </ActionIcon>
                    </Indicator>
                </Tooltip>
            </Popover.Target>
            <Popover.Dropdown>
                <Box style={{ maxHeight: 480, overflowY: 'auto' }}>
                    {opened && (
                        <CommentThread
                            commentableType={commentableType}
                            commentableId={commentableId}
                            fieldName={fieldName}
                            currentUserId={currentUserId}
                            compact
                        />
                    )}
                </Box>
            </Popover.Dropdown>
        </Popover>
    );
}
