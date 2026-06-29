import {
    IconArchive,
    IconChevronLeft,
    IconChevronRight,
    IconFileText,
    IconFolder,
    IconHeadphones,
    IconPhoto,
    IconVideo,
} from '@tabler/icons-react';
import type { Icon } from '@tabler/icons-react';
import { ActionIcon, Code, Group, Text, Tooltip, UnstyledButton } from '@mantine/core';
import { useTranslation } from 'react-i18next';
import classes from './LibrarySidebar.module.css';

export type MediaTypeKey = 'documents' | 'images' | 'videos' | 'audio' | 'archives';

export type StorageEntry = {
    id: string;
    name: string;
    slug: string;
};

export type SidebarFilter =
    | { type: 'media'; key: MediaTypeKey | 'all' }
    | { type: 'storage'; id: string };

const MEDIA_TYPES: { key: MediaTypeKey; icon: Icon }[] = [
    { key: 'documents', icon: IconFileText },
    { key: 'images', icon: IconPhoto },
    { key: 'videos', icon: IconVideo },
    { key: 'audio', icon: IconHeadphones },
    { key: 'archives', icon: IconArchive },
];

interface LibrarySidebarProps {
    storages?: StorageEntry[];
    filter: SidebarFilter;
    onFilterChange: (filter: SidebarFilter) => void;
    totalCount: number;
    /** Visual mode; ignored on mobile (always expanded). */
    collapsed: boolean;
    onToggleCollapse: () => void;
}

export default function LibrarySidebar({
    storages = [],
    filter,
    onFilterChange,
    totalCount,
    collapsed,
    onToggleCollapse,
}: LibrarySidebarProps) {
    const { t } = useTranslation('documents');
    const mode = collapsed ? 'collapsed' : 'expanded';

    const renderLink = (
        key: string,
        label: string,
        IconComp: Icon,
        active: boolean,
        onClick: () => void,
    ) => {
        const button = (
            <button
                type="button"
                key={key}
                className={classes.link}
                data-active={active || undefined}
                onClick={onClick}
                aria-label={label}
            >
                <IconComp className={classes.linkIcon} stroke={1.5} />
                {!collapsed && <span>{label}</span>}
            </button>
        );
        if (collapsed) {
            return (
                <Tooltip
                    label={label}
                    position="right"
                    withinPortal
                    transitionProps={{ duration: 0 }}
                    key={key}
                >
                    {button}
                </Tooltip>
            );
        }
        return button;
    };

    return (
        <nav className={classes.navbar} data-mode={mode} aria-label={t('sections.library')}>
            <div className={classes.navbarMain}>
                {!collapsed && (
                    <Group className={classes.header} justify="space-between">
                        <Text fw={700} size="sm" tt="uppercase" c="dimmed">
                            {t('sections.library')}
                        </Text>
                        <Code fw={700}>{totalCount}</Code>
                    </Group>
                )}

                {!collapsed && (
                    <Text fw={700} size="xs" tt="uppercase" c="dimmed" className={classes.sectionHeading}>
                        {t('sections.mediaTypes')}
                    </Text>
                )}

                {renderLink(
                    'all',
                    t('categories.all'),
                    IconFolder,
                    filter.type === 'media' && filter.key === 'all',
                    () => onFilterChange({ type: 'media', key: 'all' }),
                )}

                {MEDIA_TYPES.map(({ key, icon }) =>
                    renderLink(
                        key,
                        t(`library.${key}`),
                        icon,
                        filter.type === 'media' && filter.key === key,
                        () => onFilterChange({ type: 'media', key }),
                    ),
                )}

                {collapsed && storages.length > 0 && <div className={classes.miniDivider} />}

                {!collapsed && (
                    <Text fw={700} size="xs" tt="uppercase" c="dimmed" className={classes.sectionHeading}>
                        {t('sections.storages')}
                    </Text>
                )}

                {storages.length === 0
                    ? !collapsed && (
                          <Text size="xs" c="dimmed" className={classes.emptyText}>
                              {t('sections.storagesEmpty')}
                          </Text>
                      )
                    : storages.map((s) =>
                          renderLink(
                              `storage-${s.id}`,
                              s.name,
                              IconFolder,
                              filter.type === 'storage' && filter.id === s.id,
                              () => onFilterChange({ type: 'storage', id: s.id }),
                          ),
                      )}
            </div>

            <div className={classes.footer}>
                {collapsed ? (
                    <Tooltip label={t('nav.expand')} position="right" withinPortal>
                        <ActionIcon
                            variant="subtle"
                            size="lg"
                            onClick={onToggleCollapse}
                            aria-label={t('nav.expand')}
                            className={classes.toggleBtn}
                        >
                            <IconChevronRight size={18} />
                        </ActionIcon>
                    </Tooltip>
                ) : (
                    <UnstyledButton
                        onClick={onToggleCollapse}
                        className={classes.link}
                        aria-label={t('nav.collapse')}
                    >
                        <IconChevronLeft className={classes.linkIcon} stroke={1.5} />
                        <span>{t('nav.collapse')}</span>
                    </UnstyledButton>
                )}
            </div>
        </nav>
    );
}
