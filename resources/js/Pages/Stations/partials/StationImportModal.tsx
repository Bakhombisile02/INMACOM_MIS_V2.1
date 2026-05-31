import { useCallback, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import {
    Anchor,
    Badge,
    Button,
    Divider,
    Group,
    Modal,
    Progress,
    ScrollArea,
    Stack,
    Table,
    Text,
    Title,
    Tooltip,
    useMantineTheme,
} from '@mantine/core';
import { Dropzone, MIME_TYPES, MS_EXCEL_MIME_TYPE } from '@mantine/dropzone';
import classes from './StationDropzone.module.css';
import { notifications } from '@mantine/notifications';
import {
    IconAlertTriangle,
    IconCheck,
    IconChevronRight,
    IconCircleCheck,
    IconCloudUpload,
    IconDownload,
    IconFileDownload,
    IconInfoCircle,
    IconTableImport,
    IconX,
} from '@tabler/icons-react';
import * as XLSX from 'xlsx';
import { downloadTemplate } from '@/lib/stationImportTemplate';
import { validateImportRows, ValidatedRow, CellError } from '@/lib/stationImportValidator';

// ─── Column display config ────────────────────────────────────────────────────
const COLUMNS: { key: string; label: string; minWidth?: number }[] = [
    { key: 'code', label: 'Code', minWidth: 90 },
    { key: 'name', label: 'Name', minWidth: 160 },
    { key: 'country', label: 'Country', minWidth: 110 },
    { key: 'category', label: 'Category', minWidth: 110 },
    { key: 'water_source', label: 'Water source', minWidth: 110 },
    { key: 'water_body_type', label: 'Body type', minWidth: 100 },
    { key: 'latitude', label: 'Lat', minWidth: 80 },
    { key: 'longitude', label: 'Lng', minWidth: 80 },
    { key: 'river_basin', label: 'River basin', minWidth: 100 },
    { key: 'owner_org', label: 'Owner org', minWidth: 110 },
    { key: 'is_active', label: 'Active', minWidth: 60 },
    { key: 'is_real_time', label: 'Real-time', minWidth: 80 },
];

// ─── Types ────────────────────────────────────────────────────────────────────
type Step = 1 | 2 | 3;

interface StationImportModalProps {
    opened: boolean;
    onClose: () => void;
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function cellStyle(errors: CellError[], field: string): React.CSSProperties {
    const e = errors.find((err) => err.field === field);
    if (!e) return {};
    if (e.severity === 'error') return { backgroundColor: 'var(--mantine-color-red-0)', color: 'var(--mantine-color-red-9)' };
    if (e.severity === 'warning') return { backgroundColor: 'var(--mantine-color-yellow-0)', color: 'var(--mantine-color-yellow-9)' };
    if (e.severity === 'fixed') return { backgroundColor: 'var(--mantine-color-green-0)', color: 'var(--mantine-color-green-9)' };
    return {};
}

function cellTooltip(errors: CellError[], field: string): string | null {
    const e = errors.find((err) => err.field === field);
    return e ? e.message : null;
}

function boolDisplay(v: boolean) {
    return v ? 'Yes' : 'No';
}

// ─── Component ────────────────────────────────────────────────────────────────
export default function StationImportModal({ opened, onClose }: StationImportModalProps) {
    const theme = useMantineTheme();
    const openRef = useRef<() => void>(null);

    const [step, setStep] = useState<Step>(1);
    const [fileName, setFileName] = useState<string | null>(null);
    const [validatedRows, setValidatedRows] = useState<ValidatedRow[]>([]);
    const [submitting, setSubmitting] = useState(false);

    const readyRows = validatedRows.filter((r) => !r.hasBlockingError);
    const errorRows = validatedRows.filter((r) => r.hasBlockingError);
    const warningCount = validatedRows.filter((r) => r.errors.some((e) => e.severity === 'warning')).length;

    // ── Reset on close ────────────────────────────────────────────────────────
    const handleClose = () => {
        if (submitting) return;
        setStep(1);
        setFileName(null);
        setValidatedRows([]);
        setSubmitting(false);
        onClose();
    };

    // ── File parse ───────────────────────────────────────────────────────────
    const handleDrop = useCallback((files: File[]) => {
        const file = files[0];
        if (!file) return;
        setFileName(file.name);

        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const data = e.target?.result;
                const wb = XLSX.read(data, { type: 'array' });
                    let raw: Record<string, unknown>[] = [];
                    wb.SheetNames.forEach((sheetName) => {
                        const ws = wb.Sheets[sheetName];
                        const sheetRaw = XLSX.utils.sheet_to_json<Record<string, unknown>>(ws, { defval: '' });
                        if (Array.isArray(sheetRaw)) {
                            raw = raw.concat(
                                sheetRaw.filter((r) =>
                                    Object.values(r).some((v) => String(v ?? '').trim() !== ''),
                                ),
                            );
                        }
                    });
                    const validated = validateImportRows(raw as Record<string, unknown>[]);
                    setValidatedRows(validated);
                    setStep(2);
            } catch {
                notifications.show({
                    title: 'Parse error',
                    message: 'Could not read the file. Make sure it is a valid CSV or Excel file.',
                    color: 'red',
                    icon: <IconAlertTriangle size={16} />,
                    autoClose: false,
                    withCloseButton: true,
                });
            }
        };
        reader.readAsArrayBuffer(file);
    }, []);

    // ── Submit ───────────────────────────────────────────────────────────────
    const handleSubmit = () => {
        if (readyRows.length === 0) return;
        setSubmitting(true);

        const payload = readyRows.map((r) => ({
            ...r.normalized,
            latitude: r.normalized.latitude ?? 0,
            longitude: r.normalized.longitude ?? 0,
            country: r.normalized.country || null,
            river_basin: r.normalized.river_basin || null,
            owner_org: r.normalized.owner_org || null,
            telemetry_system: r.normalized.telemetry_system || null,
            gauge_code: r.normalized.gauge_code || null,
            summary: r.normalized.summary || null,
        }));

        router.post(
            route('stations.import'),
            { rows: payload },
            {
                onSuccess: () => {
                    notifications.show({
                        title: 'Import successful',
                        message: `${readyRows.length} station${readyRows.length !== 1 ? 's' : ''} imported successfully.`,
                        color: 'green',
                        icon: <IconCheck size={16} />,
                        autoClose: 5000,
                    });
                    handleClose();
                    router.reload();
                },
                onError: (errors) => {
                    const msg = errors?.message ?? Object.values(errors)[0] ?? 'Import failed. Check for duplicate codes or invalid data.';
                    notifications.show({
                        title: 'Import failed',
                        message: String(msg),
                        color: 'red',
                        icon: <IconAlertTriangle size={16} />,
                        autoClose: false,
                        withCloseButton: true,
                    });
                    setSubmitting(false);
                },
                onFinish: () => setSubmitting(false),
            },
        );
    };

    // ── Step indicators ───────────────────────────────────────────────────────
    const StepBadge = ({ n, label }: { n: number; label: string }) => (
        <Group gap={6} align="center">
            <div
                style={{
                    width: 22,
                    height: 22,
                    borderRadius: '50%',
                    background: step >= n ? 'var(--mantine-color-blue-6)' : 'var(--mantine-color-gray-3)',
                    color: '#fff',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    fontSize: 12,
                    fontWeight: 700,
                    flexShrink: 0,
                }}
            >
                {step > n ? <IconCheck size={12} /> : n}
            </div>
            <Text size="sm" fw={step === n ? 600 : 400} c={step === n ? undefined : 'dimmed'}>
                {label}
            </Text>
        </Group>
    );

    return (
        <Modal
            opened={opened}
            onClose={handleClose}
            size="90vw"
            title={
                <Group gap="sm">
                    <IconTableImport size={20} />
                    <Title order={4}>Import stations</Title>
                </Group>
            }
            scrollAreaComponent={ScrollArea.Autosize}
        >
            {/* Step indicator */}
            <Group gap="xs" mb="md" wrap="nowrap">
                <StepBadge n={1} label="Upload file" />
                <IconChevronRight size={14} color="var(--mantine-color-dimmed)" />
                <StepBadge n={2} label="Review & validate" />
                <IconChevronRight size={14} color="var(--mantine-color-dimmed)" />
                <StepBadge n={3} label="Confirm import" />
            </Group>

            <Divider mb="md" />

            {/* ── STEP 1: Upload ─────────────────────────────────────────── */}
            {step === 1 && (
                <Stack gap="md">
                    <Group justify="space-between" align="flex-end">
                        <Text size="sm" c="dimmed">
                            Upload a CSV or Excel file. Download the template first to ensure your data is structured correctly.
                        </Text>
                        <Group gap="xs">
                            <Anchor size="sm" onClick={() => downloadTemplate('xlsx')} style={{ cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 4 }}>
                                <IconDownload size={13} />
                                Download XLSX template
                            </Anchor>
                            <Text size="sm" c="dimmed">·</Text>
                            <Anchor size="sm" onClick={() => downloadTemplate('csv')} style={{ cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 4 }}>
                                <IconDownload size={13} />
                                CSV
                            </Anchor>
                        </Group>
                    </Group>

                    <div className={classes.wrapper}>
                        <Dropzone
                            openRef={openRef}
                            onDrop={handleDrop}
                            onReject={() =>
                                notifications.show({
                                    title: 'File rejected',
                                    message: 'Only CSV and Excel (.xlsx, .xls) files are accepted. Max size 5 MB.',
                                    color: 'red',
                                    icon: <IconX size={16} />,
                                    autoClose: 4000,
                                    withCloseButton: true,
                                })
                            }
                            maxSize={5 * 1024 * 1024}
                            accept={[MIME_TYPES.csv, ...MS_EXCEL_MIME_TYPE]}
                            className={classes.dropzone}
                            radius="md"
                        >
                            <div style={{ pointerEvents: 'none' }}>
                                <Group justify="center">
                                    <Dropzone.Accept>
                                        <IconFileDownload size={50} color={theme.colors.blue[6]} stroke={1.5} />
                                    </Dropzone.Accept>
                                    <Dropzone.Reject>
                                        <IconX size={50} color={theme.colors.red[6]} stroke={1.5} />
                                    </Dropzone.Reject>
                                    <Dropzone.Idle>
                                        <IconCloudUpload size={50} stroke={1.5} className={classes.icon} />
                                    </Dropzone.Idle>
                                </Group>

                                <Text ta="center" fw={700} fz="lg" mt="xl">
                                    <Dropzone.Accept>Drop files here</Dropzone.Accept>
                                    <Dropzone.Reject>CSV or Excel file, max 5 MB</Dropzone.Reject>
                                    <Dropzone.Idle>Upload station data</Dropzone.Idle>
                                </Text>

                                <Text className={classes.description}>
                                    Drag&apos;n&apos;drop a file here to upload. Accepted formats: <i>.csv</i>, <i>.xlsx</i>, <i>.xls</i> · Max 5 MB · Up to 500 stations.
                                </Text>
                            </div>
                        </Dropzone>

                        <Button
                            className={classes.control}
                            size="md"
                            radius="xl"
                            onClick={() => openRef.current?.()}
                        >
                            Select file
                        </Button>
                    </div>

                    <Divider label="Template column reference" labelPosition="center" />

                    <Table fz="xs" withColumnBorders withTableBorder>
                        <Table.Thead>
                            <Table.Tr>
                                <Table.Th>Column</Table.Th>
                                <Table.Th>Required</Table.Th>
                                <Table.Th>Allowed values</Table.Th>
                            </Table.Tr>
                        </Table.Thead>
                        <Table.Tbody>
                            {[
                                { col: 'code', req: true, vals: 'Unique string, max 50 chars' },
                                { col: 'name', req: true, vals: 'Full station name, max 255 chars' },
                                { col: 'country', req: false, vals: 'Mozambique | South Africa | Eswatini' },
                                { col: 'category', req: true, vals: 'river_gauge | dam | borehole | rainfall_station | lake | wetland | other' },
                                { col: 'water_source', req: true, vals: 'surface | ground | atmospheric' },
                                { col: 'water_body_type', req: true, vals: 'river | dam | borehole | lake | wetland' },
                                { col: 'latitude', req: true, vals: 'Decimal degrees (−90 to 90), e.g. -25.4937' },
                                { col: 'longitude', req: true, vals: 'Decimal degrees (−180 to 180), e.g. 31.9123' },
                                { col: 'river_basin', req: false, vals: 'Text, max 255 chars' },
                                { col: 'owner_org', req: false, vals: 'Text, max 255 chars' },
                                { col: 'telemetry_system', req: false, vals: 'Text, max 255 chars' },
                                { col: 'gauge_code', req: false, vals: 'Text, max 100 chars' },
                                { col: 'summary', req: false, vals: 'Text, max 2000 chars' },
                                { col: 'is_active', req: false, vals: 'true | false | yes | no | 1 | 0 (default: true)' },
                                { col: 'is_real_time', req: false, vals: 'true | false | yes | no | 1 | 0 (default: false)' },
                            ].map(({ col, req, vals }) => (
                                <Table.Tr key={col}>
                                    <Table.Td><Text ff="monospace" fz="xs">{col}</Text></Table.Td>
                                    <Table.Td>
                                        <Badge size="xs" color={req ? 'red' : 'gray'} variant="light">
                                            {req ? 'Required' : 'Optional'}
                                        </Badge>
                                    </Table.Td>
                                    <Table.Td>{vals}</Table.Td>
                                </Table.Tr>
                            ))}
                        </Table.Tbody>
                    </Table>
                </Stack>
            )}

            {/* ── STEP 2: Preview & Validate ─────────────────────────────── */}
            {step === 2 && (
                <Stack gap="md">
                    {/* Summary bar */}
                    <Group gap="xs" align="center" justify="space-between">
                        <Group gap="xs">
                            <Text size="sm" fw={500}>{fileName}</Text>
                            <Text size="sm" c="dimmed">·</Text>
                            <Badge color="blue" variant="light">{validatedRows.length} rows parsed</Badge>
                            <Badge color="green" variant="light" leftSection={<IconCircleCheck size={10} />}>
                                {readyRows.length} ready
                            </Badge>
                            {errorRows.length > 0 && (
                                <Badge color="red" variant="light" leftSection={<IconX size={10} />}>
                                    {errorRows.length} with errors (will be skipped)
                                </Badge>
                            )}
                            {warningCount > 0 && (
                                <Badge color="yellow" variant="light" leftSection={<IconAlertTriangle size={10} />}>
                                    {warningCount} with warnings
                                </Badge>
                            )}
                        </Group>
                        <Group gap="xs">
                            <Button variant="subtle" size="xs" onClick={() => { setStep(1); setFileName(null); setValidatedRows([]); }}>
                                ← Re-upload
                            </Button>
                        </Group>
                    </Group>

                    {/* Legend */}
                    <Group gap="md">
                        <Group gap={4}>
                            <div style={{ width: 12, height: 12, background: 'var(--mantine-color-red-1)', border: '1px solid var(--mantine-color-red-3)', borderRadius: 2 }} />
                            <Text size="xs" c="dimmed">Error (row skipped)</Text>
                        </Group>
                        <Group gap={4}>
                            <div style={{ width: 12, height: 12, background: 'var(--mantine-color-yellow-1)', border: '1px solid var(--mantine-color-yellow-3)', borderRadius: 2 }} />
                            <Text size="xs" c="dimmed">Warning (row accepted)</Text>
                        </Group>
                        <Group gap={4}>
                            <div style={{ width: 12, height: 12, background: 'var(--mantine-color-green-1)', border: '1px solid var(--mantine-color-green-3)', borderRadius: 2 }} />
                            <Text size="xs" c="dimmed">Auto-corrected</Text>
                        </Group>
                    </Group>

                    {validatedRows.length === 0 ? (
                        <Text c="dimmed" ta="center" py="xl">No data rows found in the file. Make sure you have data below the header row.</Text>
                    ) : (
                        <ScrollArea h={420} type="auto" offsetScrollbars>
                            <Table
                                fz="xs"
                                withColumnBorders
                                withTableBorder
                                highlightOnHover
                                stickyHeader
                                style={{ minWidth: 900 }}
                            >
                                <Table.Thead>
                                    <Table.Tr>
                                        <Table.Th style={{ minWidth: 40 }}>#</Table.Th>
                                        <Table.Th style={{ minWidth: 50 }}>Status</Table.Th>
                                        {COLUMNS.map((c) => (
                                            <Table.Th key={c.key} style={{ minWidth: c.minWidth }}>
                                                {c.label}
                                            </Table.Th>
                                        ))}
                                    </Table.Tr>
                                </Table.Thead>
                                <Table.Tbody>
                                    {validatedRows.map((row) => {
                                        const n = row.normalized;
                                        return (
                                            <Table.Tr
                                                key={row.index}
                                                style={{
                                                    opacity: row.hasBlockingError ? 0.7 : 1,
                                                }}
                                            >
                                                <Table.Td>
                                                    <Text ff="monospace" fz="xs" c="dimmed">{row.index + 1}</Text>
                                                </Table.Td>
                                                <Table.Td>
                                                    {row.hasBlockingError ? (
                                                        <Tooltip
                                                            label={row.errors.filter((e) => e.severity === 'error').map((e) => `${e.field}: ${e.message}`).join('\n')}
                                                            multiline
                                                            maw={300}
                                                            withArrow
                                                            styles={{ tooltip: { whiteSpace: 'pre-line' } }}
                                                        >
                                                            <Badge color="red" variant="light" size="xs" style={{ cursor: 'help' }}>
                                                                <Group gap={3} wrap="nowrap"><IconX size={9} /> Error</Group>
                                                            </Badge>
                                                        </Tooltip>
                                                    ) : row.errors.some((e) => e.severity === 'warning') ? (
                                                        <Badge color="yellow" variant="light" size="xs">
                                                            <Group gap={3} wrap="nowrap"><IconAlertTriangle size={9} /> Warn</Group>
                                                        </Badge>
                                                    ) : row.errors.some((e) => e.severity === 'fixed') ? (
                                                        <Badge color="green" variant="light" size="xs">
                                                            <Group gap={3} wrap="nowrap"><IconCheck size={9} /> Fixed</Group>
                                                        </Badge>
                                                    ) : (
                                                        <Badge color="green" variant="light" size="xs">
                                                            <Group gap={3} wrap="nowrap"><IconCircleCheck size={9} /> OK</Group>
                                                        </Badge>
                                                    )}
                                                </Table.Td>

                                                {COLUMNS.map((col) => {
                                                    const tip = cellTooltip(row.errors, col.key);
                                                    const style = cellStyle(row.errors, col.key);
                                                    const rawVal = col.key === 'is_active'
                                                        ? boolDisplay(n.is_active)
                                                        : col.key === 'is_real_time'
                                                        ? boolDisplay(n.is_real_time)
                                                        : String((n as unknown as Record<string, unknown>)[col.key] ?? '');

                                                    return (
                                                        <Table.Td key={col.key} style={style}>
                                                            {tip ? (
                                                                <Tooltip
                                                                    label={tip}
                                                                    withArrow
                                                                    multiline
                                                                    maw={260}
                                                                    styles={{ tooltip: { whiteSpace: 'pre-line' } }}
                                                                >
                                                                    <Group gap={3} wrap="nowrap" style={{ cursor: 'help' }}>
                                                                        <Text fz="xs" style={{ wordBreak: 'break-word' }}>{rawVal || <em style={{ opacity: 0.4 }}>—</em>}</Text>
                                                                        <IconInfoCircle size={11} style={{ flexShrink: 0, opacity: 0.6 }} />
                                                                    </Group>
                                                                </Tooltip>
                                                            ) : (
                                                                <Text fz="xs" style={{ wordBreak: 'break-word' }}>
                                                                    {rawVal || <em style={{ opacity: 0.4 }}>—</em>}
                                                                </Text>
                                                            )}
                                                        </Table.Td>
                                                    );
                                                })}
                                            </Table.Tr>
                                        );
                                    })}
                                </Table.Tbody>
                            </Table>
                        </ScrollArea>
                    )}

                    <Group justify="flex-end" mt="xs">
                        <Button variant="subtle" onClick={() => { setStep(1); setFileName(null); setValidatedRows([]); }}>
                            Cancel
                        </Button>
                        <Button
                            disabled={readyRows.length === 0}
                            onClick={() => setStep(3)}
                            rightSection={<IconChevronRight size={16} />}
                        >
                            Continue with {readyRows.length} station{readyRows.length !== 1 ? 's' : ''}
                        </Button>
                    </Group>
                </Stack>
            )}

            {/* ── STEP 3: Confirm ────────────────────────────────────────── */}
            {step === 3 && (
                <Stack gap="md">
                    <Stack gap="xs" align="center" py="md">
                        <IconTableImport size={48} color="var(--mantine-color-blue-6)" />
                        <Title order={3} ta="center">Ready to import</Title>
                        <Text c="dimmed" ta="center" maw={420}>
                            {readyRows.length} station{readyRows.length !== 1 ? 's' : ''} will be added to the database.
                            {errorRows.length > 0 && ` ${errorRows.length} row${errorRows.length !== 1 ? 's' : ''} with errors will be skipped.`}
                        </Text>
                    </Stack>

                    <Progress value={100} color="blue" size="xs" />

                    <Stack gap="xs">
                        <Group gap="xs">
                            <IconCircleCheck size={16} color="var(--mantine-color-green-6)" />
                            <Text size="sm"><strong>{readyRows.length}</strong> stations ready to insert</Text>
                        </Group>
                        {errorRows.length > 0 && (
                            <Group gap="xs">
                                <IconX size={16} color="var(--mantine-color-red-6)" />
                                <Text size="sm"><strong>{errorRows.length}</strong> rows skipped (errors)</Text>
                            </Group>
                        )}
                        {warningCount > 0 && (
                            <Group gap="xs">
                                <IconAlertTriangle size={16} color="var(--mantine-color-yellow-6)" />
                                <Text size="sm"><strong>{warningCount}</strong> rows imported with warnings (auto-corrections applied)</Text>
                            </Group>
                        )}
                    </Stack>

                    <Divider />

                    <Group justify="flex-end">
                        <Button variant="subtle" onClick={() => setStep(2)} disabled={submitting}>
                            ← Back to review
                        </Button>
                        <Button
                            onClick={handleSubmit}
                            loading={submitting}
                            leftSection={<IconTableImport size={16} />}
                            color="blue"
                        >
                            Import {readyRows.length} station{readyRows.length !== 1 ? 's' : ''}
                        </Button>
                    </Group>
                </Stack>
            )}
        </Modal>
    );
}
