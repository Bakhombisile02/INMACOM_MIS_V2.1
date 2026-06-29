import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import LibraryView, { type LibraryDocument } from '@/Components/Library/LibraryView';
import type { StorageEntry } from '@/Components/Library/LibrarySidebar';

interface DocumentsIndexProps {
    storages: StorageEntry[];
    documents: LibraryDocument[];
}

export default function DocumentsIndex({ storages, documents }: DocumentsIndexProps) {
    const { t } = useTranslation('documents');

    return (
        <>
            <Head title={t('public.title')} />
            <LibraryView
                storages={storages}
                documents={documents}
                heroTitle={t('public.title')}
                heroSubtitle={t('public.subtitle')}
            />
        </>
    );
}
