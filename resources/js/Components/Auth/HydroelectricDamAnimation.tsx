import { Box, Text } from '@mantine/core';
import { useMediaQuery } from '@mantine/hooks';

const AUTH_ANIMATION_SRC =
    'https://lottie.host/5cfc8673-4606-4ff2-86df-d7765f2b631f/NpyBf0kVjm.lottie';

export default function HydroelectricDamAnimation() {
    const prefersReducedMotion = useMediaQuery('(prefers-reduced-motion: reduce)');
    const isMobile = useMediaQuery('(max-width: 48em)');
    const animationSize = isMobile ? 360 : 600;

    return (
        <Box
            aria-hidden="true"
            mx="auto"
            style={{
                width: '100%',
                maxWidth: animationSize,
                minHeight: animationSize,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
            }}
        >
            {prefersReducedMotion ? (
                <Box
                    w="100%"
                    h={animationSize}
                    style={{
                        borderRadius: 16,
                        background:
                            'linear-gradient(135deg, rgba(42,157,244,0.12), rgba(30,95,190,0.22))',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                    }}
                >
                    <Text c="blue.8" fw={600}>
                        Hydroelectric dam animation
                    </Text>
                </Box>
            ) : (
                <dotlottie-wc
                    src={AUTH_ANIMATION_SRC}
                    autoplay
                    loop
                    style={{ width: animationSize, height: animationSize, maxWidth: '100%' }}
                ></dotlottie-wc>
            )}
        </Box>
    );
}
