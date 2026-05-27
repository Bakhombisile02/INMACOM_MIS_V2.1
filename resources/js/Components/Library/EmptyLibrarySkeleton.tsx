import { Container, Grid, Skeleton, Stack, Title } from '@mantine/core';
import { IconFolderOpen } from '@tabler/icons-react';
import classes from './EmptyLibrarySkeleton.module.css';

interface EmptyLibrarySkeletonProps {
    title: string;
    message?: string;
}

const SKELETON_SPANS: { base: number; xs: number }[] = [
    { base: 12, xs: 4 },
    { base: 12, xs: 8 },
    { base: 12, xs: 8 },
    { base: 12, xs: 4 },
    { base: 12, xs: 3 },
    { base: 12, xs: 3 },
    { base: 12, xs: 6 },
];

export default function EmptyLibrarySkeleton({ title }: EmptyLibrarySkeletonProps) {
    return (
        <div className={classes.wrap}>
            <Container my="md" px={0}>
                <Grid>
                    {SKELETON_SPANS.map((span, idx) => (
                        <Grid.Col key={idx} span={span}>
                            <Skeleton height={140} radius="md" animate={false} />
                        </Grid.Col>
                    ))}
                </Grid>
            </Container>

            <div className={classes.overlay}>
                <Stack gap={4} align="center" className={classes.copy}>
                    <div className={classes.iconWrap}>
                        <IconFolderOpen size={32} stroke={1.4} />
                    </div>
                    <Title order={4} className={classes.title}>
                        {title}
                    </Title>
                </Stack>
            </div>
        </div>
    );
}
