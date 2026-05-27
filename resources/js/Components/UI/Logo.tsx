import { Group, Text } from '@mantine/core';

export default function Logo() {
    return (
        <Group gap="xs" wrap="nowrap">
            <img
                src="/images/inmacom-logo.png"
                alt="INMACOM MIS"
                style={{ width: 32, height: 32, objectFit: 'contain' }}
            />

            <Text fw={700} c="#1f3f62" style={{ letterSpacing: '0.06em' }}>
                INMACOM MIS
            </Text>
        </Group>
    );
}
