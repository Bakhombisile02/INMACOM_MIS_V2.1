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
import { notifications } from '@mantine/notifications';
import {
    IconAlertTriangle,
    IconCheck,
    IconChevronRight,
    IconCloudUpload,
    IconDownload,
    IconFileDownload,
    IconTableImport,
    IconX,
} from '@tabler/icons-react';
import * as XLSX from 'xlsx';
import {
    downloadMeasurementTemplate,
    type MeasurementType,
} from '@/lib/measurementImportTemplate';
import {
    validateMeasurementRows,
    type ValidatedMeasurementRow,
    type CellError,
} from '@/lib/measurementImportValidator';
import classes from '@/Pages/Stations/partials/StationDropzone.module.css';

// ─── Column configs per type ──────────────────────────────────────────────────

interface ColDef { key: string; label: string; minWidth?: number }

const COLUMNS_FLOW: ColDef[] = [
    { key: 'station_code', label: 'Station code', minWidth: 120 },
    { key: 'value',        label: 'Value (m³/s)',  minWidth: 100 },
    { key: 'unit',         label: 'Unit',          minWidth: 80 },
    { key: 'date',         label: 'Date',          minWidth: 110 },
];
const COLUMNS_DAM: ColDef[] = [
    { key: 'station_code', label: 'Station code', minWidth: 120 },
    { key: 'value',        label: 'Value (m)',     minWidth: 100 },
    { key: 'unit',         label: 'Unit',          minWidth: 80 },
    { key: 'date',         label: 'Date',          minWidth: 110 },
];
const COLUMNS_WQ: ColDef[] = [
    { key: 'station_code',  label: 'Station code',    minWidth: 120 },
    { key: 'parameter_code',label: 'Parameter code',  minWidth: 130 },
    { key: 'value',         label: 'Value',           minWidth: 100 },
    { key: 'unit',          label: 'Unit',            minWidth: 80 },
    { key: 'date',          label: 'Date',            minWidth: 110 },
];
const COLUMNS_RAIN: ColDef[] = [
    { key: 'station_code', label: 'Station code', minWidth: 120 },
    { key: 'value',        label: 'Value (mm)',    minWidth: 100 },
    { key: 'unit',         label: 'Unit',          minWidth: 80 },
    { key: 'date',         label: 'Date',          minWidth: 110 },
];
const COLUMNS_GW: ColDef[] = [
    { key: 'station_code', label: 'Station code', minWidth: 120 },
    { key: 'value',        label: 'Value (m)',     minWidth: 100 },
    { key: 'unit',         label: 'Unit',          minWidth: 80 },
    { key: 'date',         label: 'Date',          minWidth: 110 },
];

const TYPE_COLUMNS: Record<MeasurementType, ColDef[]> = {
    flow:        COLUMNS_FLOW,
    dam:         COLUMNS_DAM,
    wq:          COLUMNS_WQ,
    rainfall:    COLUMNS_RAIN,
    groundwater: COLUMNS_GW,
};

const TYPE_LABELS: Record<MeasurementType, string> = {
    flow:        'flow levels',
    dam:         'dam levels',
    wq:          'water quality',
    rainfall:    'rainfall',
    groundwater: 'groundwater levels',
};

const ROUTE_NAMES: Record<MeasurementType, string> = {
    flow:        'measurements.flow.import',
    dam:         'measurements.dam.import',
    wq:          'measurements.wq.import',
    rainfall:    'measurements.rainfall.import',
    groundwater: 'measurements.groundwater.import',
};

// ─── Types ────────────────────────────────────────────────────────────────────

type Step = 1 | 2 | 3;

export interface MeasurementImportModalProps {
    opened: boolean;
    onClose: () => void;
    type: MeasurementType;
}

// ─── Cell helpers ─────────────────────────────────────────────────────────────

function cellStyle(errors: CellError[], field: string): React.CSSProperties {
    const e = errors.find((err) => err.field === field);
    if (!e) return {};
    if (e.severity === 'error')   return { backgroundColor: 'var(--mantine-color-red-0)',    color: 'var(--mantine-color-red-9)' };
    if (e.severity === 'warning') return { backgroundColor: 'var(--mantine-color-yellow-0)', color: 'var(--mantine-color-yellow-9)' };
    if (e.severity === 'fixed')   return { backgroundColor: 'var(--mantine-color-green-0)',  color: 'var(--mantine-color-green-9)' };
    return {};
}

function cellTooltip(errors: CellError[], field: string): string | null {
    const e = errors.find((err) => err.field === field);
    return e ? e.message : null;
}

// ─── Component ────────────────────────────────────────────────────────────────

export default function MeasurementImportModal({ opened, onClose, type }: MeasurementImportModalProps) {
    const theme = useMantineTheme();
    const openRef = useRef<() => void>(null);

    const [step, setStep] = useState<Step>(1);
    const [fileName, setFileName] = useState<string | null>(null);
    const [validatedRows, setValidatedRows] = useState<ValidatedMeasurementRow[]>([]);
    const [submitting, setSubmitting] = useState(false);

    const columns = TYPE_COLUMNS[type];
    const readyRows  = validatedRows.filter((r) => !r.hasBlockingError);
    const errorRows  = validatedRows.filter((r) => r.hasBlockingError);
    const warnCount  = validatedRows.filter((r) => r.errors.some((e) => e.severity === 'warning')).length;

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
    const handleDrop = useCallback(
        (files: File[]) => {
            const file = files[0];
            if (!file) return;
            setFileName(file.name);

            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const data = e.target?.result;
                    const wb = XLSX.read(data, { type: 'array' });
                    const ws = wb.Sheets[wb.SheetNames[0]];
                    const raw = XLSX.utils.sheet_to_json<Record<string, unknown>>(ws, { defval: '' });
                    const validated = validateMeasurementRows(raw, type);
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
        },
        [type],
    );

    // ── Submit ───────────────────────────────────────────────────────────────
    const handleSubmit = () => {
        if (readyRows.length === 0) return;
        setSubmitting(true);

        const payload = readyRows.map((r) => r.normalized) as unknown as Record<string, string | number | boolean>[];

        router.post(
            route(ROUTE_NAMES[type]),
            { rows: payload },
            {
                onSuccess: () => {
                    notifications.show({
                        title: 'Import successful',
                        message: `${readyRows.length} ${TYPE_LABELS[type]} record${readyRows.length !== 1 ? 's' : ''} imported.`,
                        color: 'green',
                        icon: <IconCheck size={16} />,
                        autoClose: 5000,
                    });
                    handleClose();
                    router.reload();
                },
                onError: (errors) => {
                    const msg = (errors as any)?.message ?? Object.values(errors)[0] ?? 'Import failed. Please check the data and try again.';
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

    // ── Step indicator ────────────────────────────────────────────────────────
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
            zIndex={2000}
            title={
                <Group gap="sm">
                    <IconTableImport size={20} />
                    <Title order={4}>Import {TYPE_LABELS[type]}</Title>
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
                            Upload a CSV or Excel file containing {TYPE_LABELS[type]} data. Download the template to see the expected columns.
                        </Text>
                        <Group gap="xs">
                            <Anchor
                                size="sm"
                                onClick={() => downloadMeasurementTemplate(type, 'xlsx')}
                                style={{ cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 4 }}
                            >
                                <IconDownload size={13} />
                                Download XLSX template
                            </Anchor>
                            <Text size="sm" c="dimmed">·</Text>
                            <Anchor
                                size="sm"
                                onClick={() => downloadMeasurementTemplate(type, 'csv')}
                                style={{ cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 4 }}
                            >
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
                                    <Dropzone.Idle>Upload {TYPE_LABELS[type]} data</Dropzone.Idle>
                                </Text>

                                <Text className={classes.description}>
                                    Drag&apos;n&apos;drop a file here to upload. Accepted: <i>.csv</i>, <i>.xlsx</i>, <i>.xls</i> · Max 5 MB · Up to 2 000 rows.
                                </Text>
                            </div>
                        </Dropzone>

                        <Button className={classes.control} size="md" radius="xl" onClick={() => openRef.current?.()}>
                            Select file
                        </Button>
                    </div>

                    {/* Column reference */}
                    <div>
                        <Text fw={600} size="sm" mb="xs">Template column reference</Text>
                        <Table withTableBorder withColumnBorders fz="xs">
                            <Table.Thead>
                                <Table.Tr>
                                    <Table.Th>Column</Table.Th>
                                    <Table.Th>Required</Table.Th>
                                    <Table.Th>Description</Table.Th>
                                </Table.Tr>
                            </Table.Thead>
                            <Table.Tbody>
                                {columns.map((col) => (
                                    <Table.Tr key={col.key}>
                                        <Table.Td><Text size="xs" fw={600} ff="monospace">{col.key}</Text></Table.Td>
                                        <Table.Td>
                                            {col.key !== 'unit' ? (
                                                <Badge color="red" variant="light" size="xs">Required</Badge>
                                            ) : (
                                                <Badge color="gray" variant="light" size="xs">Optional</Badge>
                                            )}
                                        </Table.Td>
                                        <Table.Td>{col.label}</Table.Td>
                                    </Table.Tr>
                                ))}
                            </Table.Tbody>
                        </Table>
                    </div>
                </Stack>
            )}

            {/* ── STEP 2: Review & Validate ──────────────────────────────── */}
            {step === 2 && (
                <Stack gap="md">
                    <Group justify="space-between" align="center">
                        <div>
                            <Text size="sm">
                                <strong>{validatedRows.length}</strong> rows parsed from <em>{fileName}</em>.
                                {' '}{readyRows.length} ready · {errorRows.length} errors · {warnCount} warnings.
                            </Text>
                        </div>
                        <Group gap="xs">
                            <Button variant="default" size="xs" onClick={() => setStep(1)}>
                                ← Back
                            </Button>
                            <Button
                                size="xs"
                                disabled={readyRows.length === 0}
                                onClick={() => setStep(3)}
                            >
                                Continue →
                            </Button>
                        </Group>
                    </Group>

                    {errorRows.length > 0 && (
                        <Text size="xs" c="red">
                            {errorRows.length} row{errorRows.length !== 1 ? 's' : ''} with blocking errors will be skipped on import.
                        </Text>
                    )}

                    <ScrollArea>
                        <Table withTableBorder withColumnBorders fz="xs" style={{ minWidth: columns.length * 130 }}>
                            <Table.Thead>
                                <Table.Tr>
                                    <Table.Th style={{ minWidth: 36 }}>#</Table.Th>
                                    <Table.Th style={{ minWidth: 60 }}>Status</Table.Th>
                                    {columns.map((col) => (
                                        <Table.Th key={col.key} style={{ minWidth: col.minWidth }}>{col.label}</Table.Th>
                                    ))}
                                </Table.Tr>
                            </Table.Thead>
                            <Table.Tbody>
                                {validatedRows.map((row) => {
                                    const rowOk = !row.hasBlockingError;
                                    return (
                                        <Table.Tr key={row.index} style={{ opacity: rowOk ? 1 : 0.65 }}>
                                            <Table.Td>{row.index + 1}</Table.Td>
                                            <Table.Td>
                                                {row.hasBlockingError ? (
                                                    <Badge color="red" variant="light" size="xs">Error</Badge>
                                                ) : row.errors.length > 0 ? (
                                                    <Badge color="yellow" variant="light" size="xs">Warning</Badge>
                                                ) : (
                                                    <Badge color="green" variant="light" size="xs">OK</Badge>
                                                )}
                                            </Table.Td>
                                            {columns.map((col) => {
                                                const tip = cellTooltip(row.errors, col.key);
                                                const cell = (
                                                    <Table.Td
                                                        key={col.key}
                                                        style={cellStyle(row.errors, col.key)}
                                                    >
                                                        {String((row.normalized as any)[col.key] ?? '')}
                                                    </Table.Td>
                                                );
                                                return tip ? (
                                                    <Tooltip key={col.key} label={tip} withArrow>
                                                        {cell}
                                                    </Tooltip>
                                                ) : cell;
                                            })}
                                        </Table.Tr>
                                    );
                                })}
                            </Table.Tbody>
                        </Table>
                    </ScrollArea>
                </Stack>
            )}

            {/* ── STEP 3: Confirm ────────────────────────────────────────── */}
            {step === 3 && (
                <Stack gap="md">
                    <Text size="sm">
                        Ready to import <strong>{readyRows.length}</strong> {TYPE_LABELS[type]} record{readyRows.length !== 1 ? 's' : ''} into the database.
                        {errorRows.length > 0 && ` ${errorRows.length} row${errorRows.length !== 1 ? 's' : ''} with errors will be skipped.`}
                    </Text>

                    {submitting && (
                        <Progress value={100} animated size="sm" />
                    )}

                    <Group justify="flex-end" gap="xs">
                        <Button variant="default" onClick={() => setStep(2)} disabled={submitting}>
                            ← Back
                        </Button>
                        <Button
                            onClick={handleSubmit}
                            loading={submitting}
                            disabled={readyRows.length === 0}
                            leftSection={<IconCheck size={16} />}
                        >
                            Import {readyRows.length} record{readyRows.length !== 1 ? 's' : ''}
                        </Button>
                    </Group>
                </Stack>
            )}
        </Modal>
    );
}
