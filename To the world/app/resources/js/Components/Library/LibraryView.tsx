import { useMemo, useState } from 'react';
import {
    ActionIcon,
    Badge,
    Box,
    Container,
    Grid,
    Group,
    Menu,
    Text,
    TextInput,
    Title,
} from '@mantine/core';
import { notifications } from '@mantine/notifications';
import {
    IconArchive,
    IconCopy,
    IconDots,
    IconDownload,
    IconEye,
    IconFile,
    IconFileText,
    IconFolderOpen,
    IconHeadphones,
    IconLock,
    IconPhoto,
    IconSearch,
    IconVideo,
    IconWorld,
} from '@tabler/icons-react';
import type { Icon } from '@tabler/icons-react';
import { useTranslation } from 'react-i18next';
import LibraryLayout from '@/Layouts/LibraryLayout';
import EmptyState from '@/Components/Library/EmptyState';
import EmptyLibrarySkeleton from '@/Components/Library/EmptyLibrarySkeleton';
import DropzoneCard from '@/Components/Library/DropzoneCard';
import type { SidebarFilter, StorageEntry } from '@/Components/Library/LibrarySidebar';
import classes from './LibraryView.module.css';

export type LibraryDocument = {
    id: string;
    title: string;
    description?: string | null;
    media_type: 'documents' | 'images' | 'videos' | 'audio' | 'archives';
    visibility?: 'public' | 'private';
    size_bytes: number;
    mime_type?: string | null;
    storage?: { id: string; slug: string; name: string } | null;
    download_url: string;
    created_at?: string | null;
};

interface LibraryViewProps {
    documents: LibraryDocument[];
    storages: StorageEntry[];
    /** If true, render the upload dropzone (private library only). */
    canManage?: boolean;
    /** Heading + intro shown in the hero area. */
    heroTitle: string;
    heroSubtitle: string;
    /**
     * When true, the component renders only the content (no LibraryLayout wrapper).
     * Use this when the page already supplies its own layout (e.g. AuthenticatedLayout).
     * In this mode `filter` and `onFilterChange` must be provided by the parent.
     */
    noLayout?: boolean;
    /** Controlled filter — required when noLayout=true. */
    filter?: SidebarFilter;
    /** Controlled filter setter — required when noLayout=true. */
    onFilterChange?: (f: SidebarFilter) => void;
}

const MEDIA_ICON: Record<LibraryDocument['media_type'], Icon> = {
    documents: IconFileText,
    images: IconPhoto,
    videos: IconVideo,
    audio: IconHeadphones,
    archives: IconArchive,
};

function spanForSize(sizeKb: number): number {
    if (sizeKb >= 10_000) return 12;
    if (sizeKb >= 5_000) return 8;
    if (sizeKb >= 1_000) return 6;
    if (sizeKb >= 250) return 4;
    return 3;
}

function formatSize(sizeBytes: number): string {
    const kb = sizeBytes / 1024;
    if (kb >= 1024) return `${(kb / 1024).toFixed(1)} MB`;
    return `${Math.max(1, Math.round(kb))} KB`;
}

function packRows(docs: LibraryDocument[]): { doc: LibraryDocument; span: number }[] {
    const ROW = 12;
    const remaining = docs.map((doc) => ({ doc, span: spanForSize(doc.size_bytes / 1024) }));
    const out: { doc: LibraryDocument; span: number }[] = [];
    while (remaining.length > 0) {
        let left = ROW;
        const row: typeof remaining = [];
        const first = remaining.shift()!;
        first.span = Math.min(first.span, ROW);
        row.push(first);
        left -= first.span;
        while (left > 0) {
            const idx = remaining.findIndex((e) => e.span <= left);
            if (idx === -1) break;
            const picked = remaining.splice(idx, 1)[0];
            row.push(picked);
            left -= picked.span;
        }
        if (left > 0 && row.length > 0) row[row.length - 1].span += left;
        out.push(...row);
    }
    return out;
}

export default function LibraryView({
    documents,
    storages,
    canManage = false,
    heroTitle,
    heroSubtitle,
    noLayout = false,
    filter: filterProp,
    onFilterChange: onFilterChangeProp,
}: LibraryViewProps) {
    const { t } = useTranslation('documents');
    const [filterState, setFilterState] = useState<SidebarFilter>({ type: 'media', key: 'all' });
    const [query, setQuery] = useState('');

    // Use controlled filter when noLayout=true, otherwise own state.
    const filter = noLayout && filterProp !== undefined ? filterProp : filterState;
    const setFilter = noLayout && onFilterChangeProp !== undefined ? onFilterChangeProp : setFilterState;

    const filtered = useMemo(() => {
        return documents.filter((d) => {
            if (filter.type === 'media' && filter.key !== 'all' && d.media_type !== filter.key) return false;
            if (filter.type === 'storage' && d.storage?.id !== filter.id) return false;
            if (query.trim() && !d.title.toLowerCase().includes(query.trim().toLowerCase())) return false;
            return true;
        });
    }, [documents, filter, query]);

    const packed = useMemo(() => packRows(filtered), [filtered]);

    const handleCopyLink = async (d: LibraryDocument) => {
        try {
            await navigator.clipboard.writeText(`${window.location.origin}${d.download_url}`);
            notifications.show({ message: t('actions.linkCopied'), color: 'green' });
        } catch {
            /* ignore */
        }
    };

    const content = (
        <div className={classes.page}>
            <div className={classes.hero}>
                <Container size="xl">
                    <Title order={1} className={classes.heroTitle}>
                        {heroTitle}
                    </Title>
                    <Text className={classes.heroSubtitle}>{heroSubtitle}</Text>
                </Container>
            </div>

            <Container size="xl" py="md">
                {canManage && (
                    <Box mb="lg">
                        <DropzoneCard storages={storages} />
                    </Box>
                )}

                <div className={classes.toolbar}>
                    <Text className={classes.toolbarTitle}>
                        {t('fileCount', { count: filtered.length })}
                    </Text>
                    <TextInput
                        value={query}
                        onChange={(e) => setQuery(e.currentTarget.value)}
                        placeholder={t('search')}
                        leftSection={<IconSearch size={16} />}
                        w={280}
                    />
                </div>

                {documents.length === 0 ? (
                    <EmptyLibrarySkeleton
                        title={t('empty.noUploadsTitle')}
                        message={t('empty.noUploadsMessage')}
                    />
                ) : filtered.length === 0 ? (
                    <EmptyState
                        title={t('empty.noMatchTitle')}
                        message={t('empty.noMatchMessage')}
                    />
                ) : (
                    <Grid gutter="md">
                        {packed.map(({ doc, span }) => {
                            const KindIcon = MEDIA_ICON[doc.media_type] ?? IconFile;
                            return (
                                <Grid.Col
                                    key={doc.id}
                                    span={{ base: 12, xs: Math.min(12, Math.max(6, span)), sm: span }}
                                >
                                    <article className={classes.card}>
                                        <div className={classes.thumb}>
                                            <KindIcon size={56} stroke={1.4} />
                                            <Menu position="bottom-end" withinPortal>
                                                <Menu.Target>
                                                    <ActionIcon
                                                        variant="subtle"
                                                        color="gray"
                                                        size="sm"
                                                        className={classes.menuBtn}
                                                        aria-label={t('actions.menu')}
                                                    >
                                                        <IconDots size={16} />
                                                    </ActionIcon>
                                                </Menu.Target>
                                                <Menu.Dropdown>
                                                    <Menu.Item
                                                        leftSection={<IconDownload size={14} />}
                                                        component="a"
                                                        href={doc.download_url}
                                                    >
                                                        {t('actions.download')}
                                                    </Menu.Item>
                                                    <Menu.Item
                                                        leftSection={<IconEye size={14} />}
                                                        component="a"
                                                        href={doc.download_url}
                                                        target="_blank"
                                                    >
                                                        {t('actions.preview')}
                                                    </Menu.Item>
                                                    <Menu.Item
                                                        leftSection={<IconCopy size={14} />}
                                                        onClick={() => handleCopyLink(doc)}
                                                    >
                                                        {t('actions.copyLink')}
                                                    </Menu.Item>
                                                </Menu.Dropdown>
                                            </Menu>
                                        </div>
                                        <div className={classes.cardBody}>
                                            <Text className={classes.cardName} title={doc.title}>
                                                {doc.title}
                                            </Text>
                                            <Group gap={6} mt={2}>
                                                <Text className={classes.cardMeta}>
                                                    {formatSize(doc.size_bytes)}
                                                </Text>
                                                {doc.visibility && (
                                                    <Badge
                                                        size="xs"
                                                        variant="light"
                                                        color={doc.visibility === 'public' ? 'green' : 'orange'}
                                                        leftSection={doc.visibility === 'public'
                                                            ? <IconWorld size={10} />
                                                            : <IconLock size={10} />}
                                                    >
                                                        {t(doc.visibility === 'public' ? 'upload.visibilityPublic' : 'upload.visibilityPrivate')}
                                                    </Badge>
                                                )}
                                            </Group>
                                        </div>
                                    </article>
                                </Grid.Col>
                            );
                        })}
                    </Grid>
                )}
            </Container>
        </div>
    );

    if (noLayout) {
        return content;
    }

    return (
        <LibraryLayout
            storages={storages}
            filter={filter}
            onFilterChange={setFilter}
            totalCount={documents.length}
        >
            {content}
        </LibraryLayout>
    );
}
