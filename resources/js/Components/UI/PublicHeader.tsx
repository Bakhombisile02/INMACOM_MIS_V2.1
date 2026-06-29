import { Link, usePage } from '@inertiajs/react';
import { Burger, Button, Container, Divider, Drawer, Group, ScrollArea } from '@mantine/core';
import { useDisclosure } from '@mantine/hooks';
import { useTranslation } from 'react-i18next';
import Logo from '@/Components/UI/Logo';
import LanguageSwitcher from '@/Components/UI/LanguageSwitcher';
import type { PageProps } from '@/types';
import classes from './HeaderMenu.module.css';

type NavLink = { href: string; labelKey: string; matchPrefixes?: string[]; auth?: boolean };

const NAV: NavLink[] = [
    { href: '/', labelKey: 'header.home' },
    { href: '/explore', labelKey: 'header.explore', matchPrefixes: ['/explore'] },
    { href: '/documents', labelKey: 'header.documents', matchPrefixes: ['/documents'] },
    { href: '/library', labelKey: 'header.library', matchPrefixes: ['/library'], auth: true },
];

function isActive(pathname: string, link: NavLink) {
    if (link.href === '/') return pathname === '/';
    return (link.matchPrefixes ?? [link.href]).some((p) => pathname === p || pathname.startsWith(`${p}/`));
}

export default function PublicHeader() {
    const { t } = useTranslation('common');
    const { auth, url } = usePage<PageProps>().props as PageProps & { url?: string };
    const [opened, { toggle, close }] = useDisclosure(false);

    const pathname = typeof window !== 'undefined'
        ? window.location.pathname
        : (typeof url === 'string' ? url.split('?')[0] : '/');

    const visibleNav = NAV.filter((link) => !link.auth || !!auth?.user);

    return (
        <header className={classes.header}>
            <Container size="xl">
                <div className={classes.inner}>
                    <Link href="/" className={classes.logoLink} aria-label="INMACOM home">
                        <Logo />
                    </Link>

                    <Group gap={2} visibleFrom="sm" className={classes.desktopLinks}>
                        {visibleNav.map((link) => (
                            <Link
                                key={link.href}
                                href={link.href}
                                className={`${classes.link} ${isActive(pathname, link) ? classes.linkActive : ''}`}
                                aria-current={isActive(pathname, link) ? 'page' : undefined}
                            >
                                {t(link.labelKey)}
                            </Link>
                        ))}
                    </Group>

                    <Group gap="xs" visibleFrom="sm" className={classes.desktopActions}>
                        <LanguageSwitcher />
                        {auth?.user ? (
                            <Button component={Link} href="/dashboard">
                                {t('header.dashboard')}
                            </Button>
                        ) : (
                            <Button component={Link} href="/login" variant="default">
                                {t('header.login')}
                            </Button>
                        )}
                    </Group>

                    <Group gap="xs" hiddenFrom="sm" className={classes.mobileControls}>
                        <LanguageSwitcher compact />
                        <Burger
                            opened={opened}
                            onClick={toggle}
                            size="md"
                            className={classes.burger}
                            aria-label={t('header.toggleNavigation')}
                        />
                    </Group>
                </div>
            </Container>

            <Drawer
                opened={opened}
                onClose={close}
                size="100%"
                padding="md"
                title={t('header.navigation')}
                hiddenFrom="sm"
                zIndex={1000000}
            >
                <ScrollArea h="calc(100vh - 80px)" mx="-md">
                    <div className={classes.mobileNav}>
                        <Divider my="sm" />
                        {visibleNav.map((link) => (
                            <Link
                                key={link.href}
                                href={link.href}
                                className={`${classes.link} ${isActive(pathname, link) ? classes.linkActive : ''}`}
                                aria-current={isActive(pathname, link) ? 'page' : undefined}
                                onClick={close}
                            >
                                {t(link.labelKey)}
                            </Link>
                        ))}
                        <Divider my="sm" />
                        {auth?.user ? (
                            <Link href="/dashboard" className={`${classes.link} ${classes.mobileAction}`} onClick={close}>
                                {t('header.dashboard')}
                            </Link>
                        ) : (
                            <Link href="/login" className={`${classes.link} ${classes.mobileAction}`} onClick={close}>
                                {t('header.login')}
                            </Link>
                        )}
                    </div>
                </ScrollArea>
            </Drawer>
        </header>
    );
}
