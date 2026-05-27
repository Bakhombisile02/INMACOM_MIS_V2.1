import { useState } from 'react';
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import LibraryDoubleNavbar from '@/Components/Library/LibraryDoubleNavbar';
import LibraryView, { type LibraryDocument } from '@/Components/Library/LibraryView';
import type { SidebarFilter, StorageEntry } from '@/Components/Library/LibrarySidebar';
import classes from './Index.module.css';

interface LibraryIndexProps {
    storages: StorageEntry[];
    documents: LibraryDocument[];
    canManage: boolean;
}

export default function LibraryIndex({ storages, documents, canManage }: LibraryIndexProps) {
    const { t } = useTranslation('documents');
    const [filter, setFilter] = useState<SidebarFilter>({ type: 'media', key: 'all' });

    return (
        <AuthenticatedLayout sidebarStorageKey="INMACOM_LIBRARY_SIDEBAR_COLLAPSED" defaultCollapsed>
            <Head title={t('private.title')} />

            <div className={classes.shell}>
                <aside className={classes.secondarySidebar}>
                    <LibraryDoubleNavbar
                        storages={storages}
                        filter={filter}
                        onFilterChange={setFilter}
                    />
                </aside>

                <div className={classes.content}>
                    <LibraryView
                        storages={storages}
                        documents={documents}
                        canManage={canManage}
                        heroTitle={t('private.title')}
                        heroSubtitle={t('private.subtitle')}
                        noLayout
                        filter={filter}
                        onFilterChange={setFilter}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

