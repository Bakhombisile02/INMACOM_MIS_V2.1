import { IconUpload } from '@tabler/icons-react';
import { Button, Stack, Text, Title } from '@mantine/core';
import classes from './EmptyState.module.css';

interface EmptyStateProps {
    title: string;
    message: string;
    actionLabel?: string;
    onAction?: () => void;
    icon?: React.ReactNode;
}

export default function EmptyState({ title, message, actionLabel, onAction, icon }: EmptyStateProps) {
    return (
        <div className={classes.wrap}>
            <div className={classes.iconWrap}>{icon ?? <IconUpload size={42} stroke={1.4} />}</div>
            <Stack gap={6} align="center">
                <Title order={3} className={classes.title}>
                    {title}
                </Title>
                <Text c="dimmed" ta="center" maw={420}>
                    {message}
                </Text>
                {actionLabel && onAction && (
                    <Button mt="md" onClick={onAction}>
                        {actionLabel}
                    </Button>
                )}
            </Stack>
        </div>
    );
}
