import { useEffect, useState, type ReactNode } from 'react';
import { Burger, Drawer, Text } from '@mantine/core';
import { useDisclosure, useMediaQuery } from '@mantine/hooks';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import DashboardHeader from '@/Components/Dashboard/DashboardHeader';
import DashboardSidebar from '@/Components/Dashboard/DashboardSidebar';
import { LOCALE_COOKIE, SUPPORTED_LOCALES, type SupportedLocale } from '@/lib/i18n';
import type { PageProps } from '@/types';
import classes from './AuthenticatedLayout.module.css';

const DEFAULT_STORAGE_KEY = 'INMACOM_DASHBOARD_NAV_COLLAPSED';

interface AuthenticatedLayoutProps {
    children: ReactNode;
    /** Override the localStorage key used to persist sidebar state. */
    sidebarStorageKey?: string;
    /** Initial collapsed state when no saved preference exists for this key. */
    defaultCollapsed?: boolean;
}

export default function AuthenticatedLayout({
    children,
    sidebarStorageKey = DEFAULT_STORAGE_KEY,
    defaultCollapsed = false,
}: AuthenticatedLayoutProps) {
    const { t, i18n } = useTranslation('navigation');
    const page = usePage<PageProps>();
    const [mobileOpened, { toggle: toggleMobile, close: closeMobile }] = useDisclosure(false);
    const [collapsed, setCollapsed] = useState<boolean>(() => {
        if (typeof window === 'undefined') return defaultCollapsed;
        const saved = window.localStorage.getItem(sidebarStorageKey);
        return saved !== null ? saved === '1' : defaultCollapsed;
    });
    const isMobile = useMediaQuery('(max-width: 48em)');

    useEffect(() => {
        if (typeof window !== 'undefined') {
            window.localStorage.setItem(sidebarStorageKey, collapsed ? '1' : '0');
        }
    }, [collapsed, sidebarStorageKey]);

    // Apply the authenticated user's preferred language on first load if no
    // locale cookie has been set yet. Lets newly-provisioned users (e.g. the
    // Mozambique team) see the UI in their preferred language out of the box.
    useEffect(() => {
        if (typeof document === 'undefined') return;
        const cookieAlreadySet = document.cookie.split('; ').some((c) => c.startsWith(`${LOCALE_COOKIE}=`));
        if (cookieAlreadySet) return;
        const preferred = page.props.auth?.user?.preferences?.language as SupportedLocale | undefined;
        if (preferred && (SUPPORTED_LOCALES as readonly string[]).includes(preferred)) {
            void i18n.changeLanguage(preferred);
            const oneYear = 60 * 60 * 24 * 365;
            document.cookie = `${LOCALE_COOKIE}=${preferred}; path=/; max-age=${oneYear}; SameSite=Lax`;
        }
    }, [i18n, page.props.auth?.user?.preferences?.language]);

    const handleNavigate = () => {
        if (isMobile) closeMobile();
    };

    return (
        <>
            <DashboardHeader />

            <div className={classes.shell}>
                <aside className={classes.sidebar} data-collapsed={collapsed ? 'true' : 'false'}>
                    <DashboardSidebar
                        collapsed={collapsed}
                        onToggleCollapse={() => setCollapsed((value) => !value)}
                        onNavigate={handleNavigate}
                    />
                </aside>

                <div className={classes.main}>
                    <div className={classes.mobileTrigger}>
                        <Burger
                            opened={mobileOpened}
                            onClick={toggleMobile}
                            size="sm"
                            aria-label={t('shell.toggleNavigation')}
                        />
                        <Text fw={600} size="sm">
                            {t('shell.menu')}
                        </Text>
                    </div>

                    {children}
                </div>
            </div>

            <Drawer
                opened={mobileOpened}
                onClose={closeMobile}
                size={280}
                padding={0}
                withCloseButton={false}
                hiddenFrom="sm"
                zIndex={1000000}
                title={null}
            >
                <DashboardSidebar
                    collapsed={false}
                    onToggleCollapse={closeMobile}
                    onNavigate={handleNavigate}
                />
            </Drawer>
        </>
    );
}
