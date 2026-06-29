import { Link, usePage } from '@inertiajs/react';
import { Container, Group } from '@mantine/core';
import LanguageSwitcher from '@/Components/UI/LanguageSwitcher';
import Logo from '@/Components/UI/Logo';
import NotificationsBell from '@/Components/Notifications/NotificationsBell';
import classes from './DashboardHeader.module.css';

export default function DashboardHeader() {
    const page = usePage();
    const isAuthed = !!(page.props as { auth?: { user?: unknown } }).auth?.user;
    return (
        <header className={classes.header}>
            <Container size="xl">
                <div className={classes.inner}>
                    <Link href={route('dashboard')} className={classes.logoLink}>
                        <Logo />
                    </Link>

                    <Group gap="sm" wrap="nowrap">
                        {isAuthed && <NotificationsBell />}
                        <LanguageSwitcher />
                    </Group>
                </div>
            </Container>
        </header>
    );
}
