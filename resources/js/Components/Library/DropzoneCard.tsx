import { useRef, useState } from 'react';
import { Dropzone, type FileWithPath } from '@mantine/dropzone';
import {
    Button,
    Group,
    Modal,
    Radio,
    Select,
    Stack,
    Text,
    TextInput,
    Textarea,
    useMantineTheme,
} from '@mantine/core';
import { IconCloudUpload, IconUpload, IconX } from '@tabler/icons-react';
import classes from './DropzoneCard.module.css';
import { notifications } from '@mantine/notifications';
import { router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import type { StorageEntry } from './LibrarySidebar';

interface DropzoneCardProps {
    storages: StorageEntry[];
}

const MEDIA_TYPES = ['documents', 'images', 'videos', 'audio', 'archives'] as const;

function detectMediaType(mimeType: string): string {
    if (mimeType.startsWith('image/')) return 'images';
    if (mimeType.startsWith('video/')) return 'videos';
    if (mimeType.startsWith('audio/')) return 'audio';
    const archives = [
        'application/zip',
        'application/x-zip-compressed',
        'application/x-rar-compressed',
        'application/x-tar',
        'application/gzip',
        'application/x-gzip',
        'application/x-7z-compressed',
        'application/x-bzip2',
    ];
    if (archives.includes(mimeType)) return 'archives';
    return 'documents';
}

export default function DropzoneCard({ storages }: DropzoneCardProps) {
    const theme = useMantineTheme();
    const openRef = useRef<() => void>(null);
    const { t } = useTranslation('documents');
    const [file, setFile] = useState<FileWithPath | null>(null);
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [mediaType, setMediaType] = useState<string>('documents');
    const [visibility, setVisibility] = useState<'public' | 'private'>('private');
    const [storageId, setStorageId] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const reset = () => {
        setFile(null);
        setTitle('');
        setDescription('');
        setMediaType('documents');
        setVisibility('private');
        setStorageId(null);
    };

    const handleDrop = (files: FileWithPath[]) => {
        if (files.length === 0) return;
        const dropped = files[0];
        setFile(dropped);
        setTitle(dropped.name.replace(/\.[^/.]+$/, ''));
        setMediaType(detectMediaType(dropped.type));
    };

    const submit = () => {
        if (!file) return;
        setSubmitting(true);
        const data = new FormData();
        data.append('file', file);
        data.append('title', title);
        data.append('description', description);
        data.append('media_type', mediaType);
        data.append('visibility', visibility);
        if (storageId) data.append('storage_id', storageId);

        router.post('/library/documents', data, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                notifications.show({ message: t('upload.success'), color: 'green' });
                reset();
            },
            onError: () => {
                notifications.show({ message: t('upload.error'), color: 'red' });
            },
            onFinish: () => setSubmitting(false),
        });
    };

    return (
        <>
            <div className={classes.wrapper}>
                <Dropzone
                    openRef={openRef}
                    onDrop={handleDrop}
                    className={classes.dropzone}
                    radius="md"
                    maxFiles={1}
                    maxSize={50 * 1024 ** 2}
                    aria-label="Drop files here"
                >
                    <div style={{ pointerEvents: 'none' }}>
                        <Group justify="center">
                            <Dropzone.Accept>
                                <IconUpload size={50} color={theme.colors.blue[6]} stroke={1.5} />
                            </Dropzone.Accept>
                            <Dropzone.Reject>
                                <IconX size={50} color={theme.colors.red[6]} stroke={1.5} />
                            </Dropzone.Reject>
                            <Dropzone.Idle>
                                <IconCloudUpload size={50} stroke={1.5} className={classes.icon} />
                            </Dropzone.Idle>
                        </Group>

                        <Text ta="center" fw={700} fz="lg" mt="xl">
                            <Dropzone.Accept>{t('upload.titleAccept')}</Dropzone.Accept>
                            <Dropzone.Reject>{t('upload.titleReject')}</Dropzone.Reject>
                            <Dropzone.Idle>{t('upload.title')}</Dropzone.Idle>
                        </Text>

                        <Text className={classes.description}>
                            {t('upload.subtitle')}
                        </Text>
                    </div>
                </Dropzone>

                <Button
                    className={classes.control}
                    size="md"
                    onClick={() => openRef.current?.()}
                >
                    {t('upload.selectFiles')}
                </Button>
            </div>

            <Modal
                opened={!!file}
                onClose={() => !submitting && reset()}
                title={t('upload.modalTitle')}
                size="lg"
                centered
            >
                <Stack>
                    <Text size="sm" c="dimmed">
                        {file?.name} {file ? `· ${(file.size / 1024).toFixed(1)} KB` : ''}
                    </Text>
                    <TextInput
                        label={t('upload.titleLabel')}
                        value={title}
                        onChange={(e) => setTitle(e.currentTarget.value)}
                        required
                    />
                    <Textarea
                        label={t('upload.descriptionLabel')}
                        value={description}
                        onChange={(e) => setDescription(e.currentTarget.value)}
                        autosize
                        minRows={2}
                        maxRows={4}
                    />
                    <Select
                        label={t('upload.mediaTypeLabel')}
                        value={mediaType}
                        onChange={(v) => v && setMediaType(v)}
                        data={MEDIA_TYPES.map((m) => ({ value: m, label: t(`library.${m}`) }))}
                        allowDeselect={false}
                    />
                    {storages.length > 0 && (
                        <Select
                            label={t('upload.storageLabel')}
                            value={storageId}
                            onChange={setStorageId}
                            data={storages.map((s) => ({ value: s.id, label: s.name }))}
                            clearable
                            placeholder={t('upload.storagePlaceholder')}
                        />
                    )}
                    <Radio.Group
                        label={t('upload.visibilityLabel')}
                        value={visibility}
                        onChange={(v) => setVisibility(v as 'public' | 'private')}
                    >
                        <Group mt={6}>
                            <Radio value="private" label={t('upload.visibilityPrivate')} />
                            <Radio value="public" label={t('upload.visibilityPublic')} />
                        </Group>
                    </Radio.Group>
                    <Group justify="flex-end" mt="sm">
                        <Button variant="default" onClick={reset} disabled={submitting}>
                            {t('upload.cancel')}
                        </Button>
                        <Button onClick={submit} loading={submitting} disabled={!title.trim()}>
                            {t('upload.submit')}
                        </Button>
                    </Group>
                </Stack>
            </Modal>
        </>
    );
}
