import { Link } from '@inertiajs/react';
import { IconChevronDown } from '@tabler/icons-react';
import {
    Burger,
    Center,
    Collapse,
    Container,
    Divider,
    Drawer,
    Group,
    Menu,
    ScrollArea,
    UnstyledButton,
} from '@mantine/core';
import { useDisclosure } from '@mantine/hooks';
import Logo from '@/Components/UI/Logo';
import classes from './HeaderMenu.module.css';

const links = [
    { link: '/dashboard', label: 'Dashboard' },
    {
        link: '#reports',
        label: 'Reports',
        links: [
            { link: '#overview', label: 'Overview' },
            { link: '#analytics', label: 'Analytics' },
            { link: '#export', label: 'Export' },
        ],
    },
    {
        link: '#operations',
        label: 'Operations',
        links: [
            { link: '#assets', label: 'Assets' },
            { link: '#personnel', label: 'Personnel' },
            { link: '#tasks', label: 'Tasks' },
        ],
    },
    { link: '/profile', label: 'Profile' },
];

type NavLink = { link: string; label: string; links?: { link: string; label: string }[] };

function isRealRoute(href: string) {
    return !href.startsWith('#');
}

export function HeaderMenu() {
    const [opened, { toggle, close }] = useDisclosure(false);

    const items = links.map((link) => {
        const menuItems = link.links?.map((item) => (
            <Menu.Item
                key={item.link}
                component="a"
                href={item.link}
                onClick={isRealRoute(item.link) ? undefined : (e: React.MouseEvent) => e.preventDefault()}
            >
                {item.label}
            </Menu.Item>
        ));

        if (menuItems) {
            return (
                <Menu key={link.label} trigger="hover" transitionProps={{ exitDuration: 0 }} withinPortal>
                    <Menu.Target>
                        <a
                            href={link.link}
                            className={classes.link}
                            onClick={(e) => e.preventDefault()}
                        >
                            <Center>
                                <span className={classes.linkLabel}>{link.label}</span>
                                <IconChevronDown size={14} stroke={1.5} />
                            </Center>
                        </a>
                    </Menu.Target>
                    <Menu.Dropdown>{menuItems}</Menu.Dropdown>
                </Menu>
            );
        }

        return isRealRoute(link.link) ? (
            <Link key={link.label} href={link.link} className={classes.link}>
                {link.label}
            </Link>
        ) : (
            <a
                key={link.label}
                href={link.link}
                className={classes.link}
                onClick={(e) => e.preventDefault()}
            >
                {link.label}
            </a>
        );
    });

    return (
        <header className={classes.header}>
            <Container size="xl">
                <div className={classes.inner}>
                    <Link href="/dashboard" style={{ textDecoration: 'none' }}>
                        <Logo />
                    </Link>
                    <Group gap={5} visibleFrom="sm">
                        {items}
                    </Group>
                    <Burger
                        opened={opened}
                        onClick={toggle}
                        size="sm"
                        hiddenFrom="sm"
                        aria-label="Toggle navigation"
                    />
                </div>
            </Container>

            <Drawer
                opened={opened}
                onClose={close}
                size="100%"
                padding="md"
                title="Navigation"
                hiddenFrom="sm"
                zIndex={1000000}
            >
                <ScrollArea h="calc(100vh - 80px)" mx="-md">
                    <Divider my="sm" />
                    {links.map((link) => {
                        if (link.links) {
                            return <DrawerLinksGroup key={link.label} link={link} />;
                        }

                        return isRealRoute(link.link) ? (
                            <Link
                                key={link.label}
                                href={link.link}
                                className={classes.link}
                                onClick={close}
                            >
                                {link.label}
                            </Link>
                        ) : (
                            <a
                                key={link.label}
                                href={link.link}
                                className={classes.link}
                                onClick={(e) => e.preventDefault()}
                            >
                                {link.label}
                            </a>
                        );
                    })}
                </ScrollArea>
            </Drawer>
        </header>
    );
}

function DrawerLinksGroup({ link }: { link: NavLink }) {
    const [opened, { toggle }] = useDisclosure(false);

    return (
        <>
            <UnstyledButton className={classes.link} onClick={toggle}>
                <Center inline>
                    <span className={classes.linkLabel}>{link.label}</span>
                    <IconChevronDown size={14} stroke={1.5} />
                </Center>
            </UnstyledButton>
            <Collapse in={opened}>
                {link.links?.map((subLink) => (
                    isRealRoute(subLink.link) ? (
                        <Link
                            key={subLink.link}
                            href={subLink.link}
                            className={classes.subLink}
                        >
                            {subLink.label}
                        </Link>
                    ) : (
                        <a
                            key={subLink.link}
                            href={subLink.link}
                            className={classes.subLink}
                            onClick={(e) => e.preventDefault()}
                        >
                            {subLink.label}
                        </a>
                    )
                ))}
            </Collapse>
        </>
    );
}
