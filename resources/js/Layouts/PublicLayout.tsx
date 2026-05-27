import type { ReactNode } from 'react';
import { Anchor, Container, Group, Text } from '@mantine/core';
import { useTranslation } from 'react-i18next';
import PublicHeader from '@/Components/UI/PublicHeader';

type PublicLayoutProps = {
    children: ReactNode;
};

export default function PublicLayout({ children }: PublicLayoutProps) {
    const { t } = useTranslation('common');
    const year = new Date().getFullYear();

    return (
        <>
            <PublicHeader />
            <main>{children}</main>
            <footer
                style={{
                    borderTop: '1px solid var(--mantine-color-gray-3)',
                    marginTop: 40,
                    padding: '24px 0',
                }}
            >
                <Container size="xl">
                    <Group justify="space-between" wrap="wrap">
                        <Text size="sm" c="dimmed">
                            {t('footer.copyright', { year })}
                        </Text>
                        <Group gap="md">
                            <Anchor href="#privacy" size="sm" c="dimmed">
                                {t('footer.privacy')}
                            </Anchor>
                            <Anchor href="#terms" size="sm" c="dimmed">
                                {t('footer.terms')}
                            </Anchor>
                        </Group>
                    </Group>
                </Container>
            </footer>
        </>
    );
}
