import { Box, Container, Flex, Grid, Stack } from '@mantine/core';
import { useMediaQuery } from '@mantine/hooks';
import type { ReactNode } from 'react';
import PublicHeader from '@/Components/UI/PublicHeader';
import HydroelectricDamAnimation from '@/Components/Auth/HydroelectricDamAnimation';

type AuthShellProps = {
    children: ReactNode;
};

export default function AuthShell({ children }: AuthShellProps) {
    const isMobile = useMediaQuery('(max-width: 48em)');

    return (
        <Box mih="100vh" style={{ background: 'var(--mantine-color-body)' }}>
            <PublicHeader />

            <Container
                size={1680}
                style={{ minHeight: 'calc(100vh - 56px)', display: 'flex', alignItems: 'center', paddingTop: '1rem', paddingBottom: '1rem' }}
            >
                <Grid align="center" gutter={{ base: 'lg', md: 'xl' }} w="100%">
                    {!isMobile && (
                        <Grid.Col span={{ base: 12, md: 7 }}>
                            <Flex justify="center" align="center" h="100%">
                                <HydroelectricDamAnimation />
                            </Flex>
                        </Grid.Col>
                    )}

                    <Grid.Col span={{ base: 12, md: 5 }}>
                        <Flex justify="center" align="center">
                            <Stack w="100%" maw={900} gap="md">
                                {isMobile && (
                                    <Box w="100%" maw={420} mx="auto" style={{ display: 'flex', justifyContent: 'center' }}>
                                        <HydroelectricDamAnimation />
                                    </Box>
                                )}
                                {children}
                            </Stack>
                        </Flex>
                    </Grid.Col>
                </Grid>
            </Container>
        </Box>
    );
}
