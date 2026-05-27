import { useEffect, useState, type ReactNode } from 'react';
import { Burger, Drawer, Text } from '@mantine/core';
import { useDisclosure, useMediaQuery } from '@mantine/hooks';
import { useTranslation } from 'react-i18next';
import PublicHeader from '@/Components/UI/PublicHeader';
import LibrarySidebar, {
    type SidebarFilter,
    type StorageEntry,
} from '@/Components/Library/LibrarySidebar';
import classes from './LibraryLayout.module.css';

const STORAGE_KEY = 'INMACOM_LIBRARY_NAV_COLLAPSED';

interface LibraryLayoutProps {
    children: ReactNode;
    storages?: StorageEntry[];
    filter: SidebarFilter;
    onFilterChange: (filter: SidebarFilter) => void;
    totalCount: number;
}

export default function LibraryLayout({
    children,
    storages,
    filter,
    onFilterChange,
    totalCount,
}: LibraryLayoutProps) {
    const { t } = useTranslation('documents');
    const [mobileOpened, { toggle: toggleMobile, close: closeMobile }] = useDisclosure(false);
    const [collapsed, setCollapsed] = useState<boolean>(() => {
        if (typeof window === 'undefined') return false;
        return window.localStorage.getItem(STORAGE_KEY) === '1';
    });
    const isMobile = useMediaQuery('(max-width: 48em)');

    useEffect(() => {
        if (typeof window !== 'undefined') {
            window.localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
        }
    }, [collapsed]);

    const handleFilter = (f: SidebarFilter) => {
        onFilterChange(f);
        if (isMobile) closeMobile();
    };

    return (
        <>
            <PublicHeader />

            <div className={classes.shell}>
                <aside className={classes.sidebar} data-collapsed={collapsed ? 'true' : 'false'}>
                    <LibrarySidebar
                        storages={storages}
                        filter={filter}
                        onFilterChange={handleFilter}
                        totalCount={totalCount}
                        collapsed={collapsed}
                        onToggleCollapse={() => setCollapsed((c) => !c)}
                    />
                </aside>

                <div className={classes.main}>
                    <div className={classes.mobileTrigger}>
                        <Burger
                            opened={mobileOpened}
                            onClick={toggleMobile}
                            size="sm"
                            aria-label={t('nav.openNav')}
                        />
                        <Text fw={600} size="sm">
                            {t('sections.library')}
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
                <LibrarySidebar
                    storages={storages}
                    filter={filter}
                    onFilterChange={handleFilter}
                    totalCount={totalCount}
                    collapsed={false}
                    onToggleCollapse={closeMobile}
                />
            </Drawer>
        </>
    );
}
