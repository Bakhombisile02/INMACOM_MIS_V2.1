import {
    IconArchive,
    IconFiles,
    IconFileText,
    IconHeadphones,
    IconPhoto,
    IconServer,
    IconVideo,
} from '@tabler/icons-react';
import type { Icon } from '@tabler/icons-react';
import { useTranslation } from 'react-i18next';
import type { MediaTypeKey, SidebarFilter, StorageEntry } from './LibrarySidebar';
import classes from './LibraryDoubleNavbar.module.css';

interface LibraryDoubleNavbarProps {
    storages?: StorageEntry[];
    filter: SidebarFilter;
    onFilterChange: (filter: SidebarFilter) => void;
}

const MEDIA_ITEMS: { key: MediaTypeKey | 'all'; labelKey: string; icon: Icon }[] = [
    { key: 'all', labelKey: 'categories.all', icon: IconFiles },
    { key: 'documents', labelKey: 'library.documents', icon: IconFileText },
    { key: 'images', labelKey: 'library.images', icon: IconPhoto },
    { key: 'videos', labelKey: 'library.videos', icon: IconVideo },
    { key: 'audio', labelKey: 'library.audio', icon: IconHeadphones },
    { key: 'archives', labelKey: 'library.archives', icon: IconArchive },
];

export default function LibraryDoubleNavbar({
    storages = [],
    filter,
    onFilterChange,
}: LibraryDoubleNavbarProps) {
    const { t } = useTranslation('documents');

    return (
        <nav className={classes.navbar} aria-label={t('sections.library')}>
            <div className={classes.navbarMain}>
                {/* Media Types section */}
                <div className={classes.sectionHeading}>{t('sections.mediaTypes')}</div>
                {MEDIA_ITEMS.map(({ key, labelKey, icon: IconComp }) => {
                    const isActive = filter.type === 'media' && filter.key === key;
                    return (
                        <button
                            key={key}
                            type="button"
                            className={classes.link}
                            data-active={isActive || undefined}
                            onClick={() => onFilterChange({ type: 'media', key })}
                        >
                            <IconComp className={classes.linkIcon} size={18} stroke={1.5} />
                            <span>{t(labelKey)}</span>
                        </button>
                    );
                })}

                {/* Storages section */}
                {storages.length > 0 && (
                    <>
                        <div className={classes.sectionHeading}>{t('sections.storages')}</div>
                        {storages.map((storage) => {
                            const isActive = filter.type === 'storage' && filter.id === storage.id;
                            return (
                                <button
                                    key={storage.id}
                                    type="button"
                                    className={classes.link}
                                    data-active={isActive || undefined}
                                    onClick={() =>
                                        onFilterChange({ type: 'storage', id: storage.id })
                                    }
                                >
                                    <IconServer className={classes.linkIcon} size={18} stroke={1.5} />
                                    <span>{storage.name}</span>
                                </button>
                            );
                        })}
                    </>
                )}
            </div>
        </nav>
    );
}
