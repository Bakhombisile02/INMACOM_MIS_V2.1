import { Link, router, usePage } from '@inertiajs/react';
import {
    IconActivity,
    IconAdjustmentsHorizontal,
    IconAlertTriangle,
    IconChevronLeft,
    IconChevronRight,
    IconCloudRain,
    IconDatabase,
    IconDroplet,
    IconFolder,
    IconLayoutDashboard,
    IconLogout,
    IconMapPin,
    IconPercentage,
    IconSettings,
    IconShieldCheck,
    IconUserCircle,
    IconUsers,
} from '@tabler/icons-react';
import type { Icon } from '@tabler/icons-react';
import {
    ActionIcon,
    Avatar,
    Badge,
    Text,
    Tooltip,
    UnstyledButton,
} from '@mantine/core';
import { useTranslation } from 'react-i18next';
import type { NavItem, PageProps } from '@/types';
import classes from './DashboardSidebar.module.css';

interface DashboardSidebarProps {
    collapsed: boolean;
    onToggleCollapse: () => void;
    onNavigate?: () => void;
}

const ICONS: Record<string, Icon> = {
    dashboard: IconLayoutDashboard,
    stations: IconMapPin,
    thresholds: IconAdjustmentsHorizontal,
    users: IconUsers,
    flowLevels: IconActivity,
    damLevels: IconPercentage,
    waterQuality: IconDroplet,
    rainfall: IconCloudRain,
    groundwater: IconDatabase,
    disasterManagement: IconAlertTriangle,
    documentStorage: IconFolder,
    auditLog: IconShieldCheck,
    settings: IconSettings,
    profile: IconUserCircle,
    logout: IconLogout,
};

function toPath(href: string): string {
    try {
        return new URL(href, 'http://localhost').pathname;
    } catch {
        return href;
    }
}

function isActive(item: NavItem, currentPath: string): boolean {
    const targetPath = toPath(item.href);

    if (targetPath === '/') {
        return currentPath === '/';
    }

    return currentPath === targetPath || currentPath.startsWith(`${targetPath}/`);
}

function initialsFromName(name: string) {
    const cleaned = name.trim();
    if (!cleaned) return 'U';

    return cleaned
        .split(' ')
        .slice(0, 2)
        .map((chunk) => chunk.charAt(0).toUpperCase())
        .join('');
}

export default function DashboardSidebar({ collapsed, onToggleCollapse, onNavigate }: DashboardSidebarProps) {
    const { t } = useTranslation('navigation');
    const page = usePage<PageProps>();
    const { auth, navigation } = page.props;
    const currentPath = page.url.split('?')[0];

    const renderItem = (item: NavItem) => {
        const IconComp = ICONS[item.icon] ?? IconFolder;
        const active = isActive(item, currentPath);

        const content = (
            <>
                <IconComp className={classes.linkIcon} stroke={1.5} />
                {!collapsed && <span className={classes.linkLabel}>{t(item.label)}</span>}
                {!collapsed && typeof item.badge === 'number' && item.badge > 0 && (
                    <Badge variant="light" color="gray" size="xs" ml="auto">
                        {item.badge}
                    </Badge>
                )}
            </>
        );

        if (item.method === 'post') {
            const button = (
                <button
                    type="button"
                    key={item.id}
                    className={classes.link}
                    data-active={active || undefined}
                    onClick={() => {
                        onNavigate?.();
                        router.post(item.href);
                    }}
                    aria-label={t(item.label)}
                >
                    {content}
                </button>
            );

            if (collapsed) {
                return (
                    <Tooltip key={item.id} label={t(item.label)} position="right" withinPortal>
                        {button}
                    </Tooltip>
                );
            }

            return button;
        }

        const link = (
            <Link
                key={item.id}
                href={item.href}
                className={classes.link}
                data-active={active || undefined}
                onClick={onNavigate}
                aria-label={t(item.label)}
            >
                {content}
            </Link>
        );

        if (collapsed) {
            return (
                <Tooltip key={item.id} label={t(item.label)} position="right" withinPortal>
                    {link}
                </Tooltip>
            );
        }

        return link;
    };

    const profileCard = (
        <Link
            href="/profile"
            className={classes.profileCard}
            data-active={currentPath === '/profile' || undefined}
            onClick={onNavigate}
        >
            <Avatar
                size={collapsed ? 28 : 30}
                src={auth.user?.photo_url ?? undefined}
                className={classes.profileAvatar}
            >
                {initialsFromName(auth.user?.display_name ?? t('unknownUser'))}
            </Avatar>
            {!collapsed && (
                <div className={classes.userCopy}>
                    <Text size="sm" fw={700} className={classes.userName}>
                        {auth.user?.display_name ?? t('unknownUser')}
                    </Text>
                    <Text size="xs" c="dimmed">
                        {t(`roles.${auth.user?.role ?? 'clerk'}`)}
                    </Text>
                </div>
            )}
        </Link>
    );

    const bottomItems = navigation.bottom.filter((item) => item.id !== 'profile');

    return (
        <nav
            className={classes.navbar}
            data-mode={collapsed ? 'collapsed' : 'expanded'}
            aria-label={t('shell.sidebarLabel')}
        >
            <div className={classes.navbarMain}>
                <div className={classes.section}>{navigation.main.map(renderItem)}</div>
            </div>

            <div className={classes.footer}>
                {collapsed ? (
                    <Tooltip label={auth.user?.display_name ?? t('unknownUser')} position="right" withinPortal>
                        {profileCard}
                    </Tooltip>
                ) : (
                    profileCard
                )}

                <div className={classes.section}>{bottomItems.map(renderItem)}</div>

                {collapsed ? (
                    <Tooltip label={t('shell.expand')} position="right" withinPortal>
                        <ActionIcon
                            variant="subtle"
                            size="lg"
                            onClick={onToggleCollapse}
                            aria-label={t('shell.expand')}
                            className={classes.toggleBtn}
                        >
                            <IconChevronRight size={18} />
                        </ActionIcon>
                    </Tooltip>
                ) : (
                    <UnstyledButton
                        onClick={onToggleCollapse}
                        className={classes.link}
                        aria-label={t('shell.collapse')}
                    >
                        <IconChevronLeft className={classes.linkIcon} stroke={1.5} />
                        <span className={classes.linkLabel}>{t('shell.collapse')}</span>
                    </UnstyledButton>
                )}
            </div>
        </nav>
    );
}
