import { useState } from 'react';
import { IconChevronDown, IconChevronUp, IconSearch, IconSelector } from '@tabler/icons-react';
import {
    Center,
    Group,
    ScrollArea,
    Table,
    Text,
    TextInput,
    UnstyledButton,
    keys,
} from '@mantine/core';
import classes from './TableSort.module.css';

export interface SortableRow {
    id: string;
    [key: string]: string;
}

export interface SortableColumn {
    key: string;
    label: string;
    sortable?: boolean;
}

interface ThProps {
    children: React.ReactNode;
    reversed: boolean;
    sorted: boolean;
    disabled?: boolean;
    onSort: () => void;
}

function Th({ children, reversed, sorted, disabled, onSort }: ThProps) {
    const Icon = sorted ? (reversed ? IconChevronUp : IconChevronDown) : IconSelector;

    if (disabled) {
        return (
            <Table.Th>
                <Text fw={500} fz="sm" px="md" py="xs">
                    {children}
                </Text>
            </Table.Th>
        );
    }

    return (
        <Table.Th className={classes.th}>
            <UnstyledButton onClick={onSort} className={classes.control}>
                <Group justify="space-between">
                    <Text fw={500} fz="sm">
                        {children}
                    </Text>
                    <Center className={classes.icon}>
                        <Icon size={16} stroke={1.5} />
                    </Center>
                </Group>
            </UnstyledButton>
        </Table.Th>
    );
}

function filterData(data: SortableRow[], search: string, searchableKeys: string[]) {
    const query = search.toLowerCase().trim();

    if (!query) {
        return data;
    }

    return data.filter((item) => {
        const targetKeys = searchableKeys.length > 0 ? searchableKeys : keys(item).filter((key) => key !== 'id');

        return targetKeys.some((key) => String(item[key]).toLowerCase().includes(query));
    });
}

function sortData(
    data: SortableRow[],
    payload: { sortBy: string | null; reversed: boolean; search: string; searchableKeys: string[] },
) {
    const { sortBy } = payload;

    if (!sortBy) {
        return filterData(data, payload.search, payload.searchableKeys);
    }

    return filterData(
        [...data].sort((a, b) => {
            const left = String(a[sortBy] ?? '');
            const right = String(b[sortBy] ?? '');

            if (payload.reversed) {
                return right.localeCompare(left);
            }

            return left.localeCompare(right);
        }),
        payload.search,
        payload.searchableKeys,
    );
}

interface SortableTableProps {
    data: SortableRow[];
    columns: SortableColumn[];
    searchPlaceholder: string;
    emptyLabel: string;
    onRowClick?: (row: SortableRow) => void;
    minWidth?: number;
}

export default function SortableTable({
    data,
    columns,
    searchPlaceholder,
    emptyLabel,
    onRowClick,
    minWidth = 700,
}: SortableTableProps) {
    const [search, setSearch] = useState('');
    const [sortedData, setSortedData] = useState<SortableRow[]>(data);
    const [sortBy, setSortBy] = useState<string | null>(null);
    const [reverseSortDirection, setReverseSortDirection] = useState(false);

    const searchableKeys = columns.map((column) => column.key);

    const setSorting = (field: string) => {
        const reversed = field === sortBy ? !reverseSortDirection : false;
        setReverseSortDirection(reversed);
        setSortBy(field);
        setSortedData(sortData(data, { sortBy: field, reversed, search, searchableKeys }));
    };

    const handleSearchChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const { value } = event.currentTarget;
        setSearch(value);
        setSortedData(
            sortData(data, { sortBy, reversed: reverseSortDirection, search: value, searchableKeys }),
        );
    };

    const rows = sortedData.map((row) => (
        <Table.Tr
            key={row.id}
            className={onRowClick ? classes.clickableRow : undefined}
            onClick={onRowClick ? () => onRowClick(row) : undefined}
        >
            {columns.map((column) => (
                <Table.Td key={`${row.id}-${column.key}`}>{row[column.key]}</Table.Td>
            ))}
        </Table.Tr>
    ));

    return (
        <ScrollArea>
            <TextInput
                placeholder={searchPlaceholder}
                mb="md"
                radius="md"
                leftSection={<IconSearch size={16} stroke={1.5} />}
                value={search}
                onChange={handleSearchChange}
            />
            <Table horizontalSpacing="md" verticalSpacing="xs" miw={minWidth} layout="fixed">
                <Table.Thead>
                    <Table.Tr>
                        {columns.map((column) => (
                            <Th
                                key={column.key}
                                sorted={sortBy === column.key}
                                reversed={reverseSortDirection}
                                disabled={column.sortable === false}
                                onSort={() => setSorting(column.key)}
                            >
                                {column.label}
                            </Th>
                        ))}
                    </Table.Tr>
                </Table.Thead>
                <Table.Tbody>
                    {rows.length > 0 ? (
                        rows
                    ) : (
                        <Table.Tr>
                            <Table.Td colSpan={Math.max(columns.length, 1)}>
                                <Text fw={500} ta="center" className={classes.empty}>
                                    {emptyLabel}
                                </Text>
                            </Table.Td>
                        </Table.Tr>
                    )}
                </Table.Tbody>
            </Table>
        </ScrollArea>
    );
}
