import { ActionIcon, Box, Group } from '@mantine/core';
import { useComputedColorScheme, useMantineColorScheme } from '@mantine/core';
import { IconHelp, IconMoon, IconSun, IconWorld } from '@tabler/icons-react';
import Logo from '@/Components/UI/Logo';

export default function AuthTopBar() {
    const { setColorScheme } = useMantineColorScheme();
    const computedColorScheme = useComputedColorScheme('light', {
        getInitialValueInEffect: true,
    });

    return (
        <Box
            px={{ base: 'md', md: 'xl' }}
            h={68}
            style={{
                borderBottom: '1px solid var(--mantine-color-default-border)',
                background: 'var(--mantine-color-body)',
                boxShadow: '0 1px 4px light-dark(rgba(0,0,0,0.03), rgba(0,0,0,0.2))',
            }}
        >
            <Group h="100%" justify="space-between">
                <Logo />

                <Group gap="xs">
                    <ActionIcon
                        variant="subtle"
                        color="gray"
                        aria-label="Language"
                    >
                        <IconWorld size={18} />
                    </ActionIcon>
                    <ActionIcon
                        variant="subtle"
                        color="gray"
                        aria-label="Help"
                    >
                        <IconHelp size={18} />
                    </ActionIcon>
                    <ActionIcon
                        variant="subtle"
                        color="gray"
                        aria-label="Toggle color scheme"
                        onClick={() =>
                            setColorScheme(computedColorScheme === 'dark' ? 'light' : 'dark')
                        }
                    >
                        {computedColorScheme === 'dark' ? (
                            <IconSun size={18} />
                        ) : (
                            <IconMoon size={18} />
                        )}
                    </ActionIcon>
                </Group>
            </Group>
        </Box>
    );
}
