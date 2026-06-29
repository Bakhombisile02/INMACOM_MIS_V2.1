import { Group, Text } from '@mantine/core';
import classes from './Logo.module.css';

export default function Logo() {
    return (
        <Group gap="xs" wrap="nowrap" className={classes.root}>
            <img
                src="/images/inmacom-logo.png"
                alt="INMACOM MIS"
                className={classes.logoMark}
            />

            <Text fw={700} c="#1f3f62" className={classes.wordmark}>
                INMACOM MIS
            </Text>
        </Group>
    );
}
