import { useEffect, useRef, useState } from 'react';
import ReactPaginate from 'react-paginate';
import { ChevronLeft, ChevronRight, Search } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { EmptyState } from '@/components/admin/empty-state';
import type { PaginationMeta } from '@/types';

export interface Column<T extends Record<string, unknown>> {
    key: string;
    label: string;
    className?: string;
    render?: (row: T) => React.ReactNode;
}

export interface FilterOption {
    label: string;
    value: string;
}

interface DataTableProps<T extends Record<string, unknown>> {
    columns: Column<T>[];
    data: T[];
    searchPlaceholder?: string;
    searchKey?: string;
    filterOptions?: FilterOption[];
    filterKey?: string;
    actions?: (row: T) => React.ReactNode;
    emptyTitle?: string;
    emptyDescription?: string;
    // Server-side mode — provide these to enable server pagination
    pagination?: PaginationMeta;
    initialSearch?: string;
    initialFilter?: string;
    onSearchChange?: (value: string) => void;
    onFilterChange?: (value: string) => void;
    onPageChange?: (page: number) => void;
    // Client-side only
    perPage?: number;
}

const PAGINATION_CLASSES = {
    container: 'flex items-center gap-1',
    pageLink:
        'flex h-8 w-8 items-center justify-center rounded-md text-sm hover:bg-accent hover:text-accent-foreground transition-colors',
    activePage: '[&>a]:bg-primary [&>a]:text-primary-foreground [&>a]:hover:bg-primary [&>a]:hover:text-primary-foreground',
    navLink:
        'flex h-8 w-8 items-center justify-center rounded-md hover:bg-accent hover:text-accent-foreground transition-colors',
    disabledLink: 'opacity-40 cursor-not-allowed pointer-events-none',
    break: 'flex h-8 items-center justify-center px-1 text-sm text-muted-foreground',
};

export function DataTable<T extends Record<string, unknown>>({
    columns,
    data,
    searchPlaceholder = 'Rechercher...',
    searchKey,
    filterOptions,
    filterKey,
    actions,
    emptyTitle = 'Aucun résultat',
    emptyDescription = 'Aucun enregistrement trouvé.',
    pagination,
    initialSearch = '',
    initialFilter = '',
    onSearchChange,
    onFilterChange,
    onPageChange,
    perPage = 10,
}: DataTableProps<T>) {
    const isServer = Boolean(pagination);

    const [search, setSearch] = useState(initialSearch);
    const [filterValue, setFilterValue] = useState(initialFilter || 'all');
    const [clientPage, setClientPage] = useState(0);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    // Sync controlled state when server props change (e.g. browser back)
    useEffect(() => { setSearch(initialSearch); }, [initialSearch]);
    useEffect(() => { setFilterValue(initialFilter || 'all'); }, [initialFilter]);

    function handleSearch(value: string) {
        setSearch(value);
        if (isServer && onSearchChange) {
            if (debounceRef.current) clearTimeout(debounceRef.current);
            debounceRef.current = setTimeout(() => onSearchChange(value), 350);
        } else {
            setClientPage(0);
        }
    }

    function handleFilter(value: string) {
        setFilterValue(value);
        if (isServer && onFilterChange) {
            onFilterChange(value === 'all' ? '' : value);
        } else {
            setClientPage(0);
        }
    }

    function handlePageChange({ selected }: { selected: number }) {
        if (isServer && onPageChange) {
            onPageChange(selected + 1);
        } else {
            setClientPage(selected);
        }
    }

    // Client-side filtering + pagination
    const filtered = isServer
        ? data
        : data.filter((row) => {
              const matchSearch =
                  !searchKey ||
                  String(row[searchKey] ?? '')
                      .toLowerCase()
                      .includes(search.toLowerCase());
              const matchFilter =
                  !filterKey || filterValue === 'all' || String(row[filterKey]) === filterValue;
              return matchSearch && matchFilter;
          });

    const pageCount = isServer
        ? (pagination?.last_page ?? 1)
        : Math.max(1, Math.ceil(filtered.length / perPage));

    const forcePage = isServer ? (pagination?.current_page ?? 1) - 1 : clientPage;

    const rows = isServer ? data : filtered.slice(clientPage * perPage, (clientPage + 1) * perPage);

    const showPagination = pageCount > 1;

    return (
        <div className="flex flex-col gap-4">
            {/* Search / filter bar */}
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                {(searchKey || (isServer && onSearchChange)) && (
                    <div className="relative flex-1">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            placeholder={searchPlaceholder}
                            value={search}
                            onChange={(e) => handleSearch(e.target.value)}
                            className="pl-9"
                        />
                    </div>
                )}
                {filterOptions && filterKey && (
                    <Select value={filterValue} onValueChange={handleFilter}>
                        <SelectTrigger className="w-full sm:w-48">
                            <SelectValue placeholder="Tous les statuts" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Tous les statuts</SelectItem>
                            {filterOptions.map((opt) => (
                                <SelectItem key={opt.value} value={opt.value}>
                                    {opt.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}
                {isServer && pagination && (
                    <span className="ml-auto shrink-0 text-sm text-muted-foreground">
                        {pagination.total} résultat{pagination.total !== 1 ? 's' : ''}
                    </span>
                )}
            </div>

            {/* Table */}
            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            {columns.map((col) => (
                                <TableHead key={col.key} className={col.className}>
                                    {col.label}
                                </TableHead>
                            ))}
                            {actions && <TableHead className="text-right">Actions</TableHead>}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={columns.length + (actions ? 1 : 0)}>
                                    <EmptyState title={emptyTitle} description={emptyDescription} />
                                </TableCell>
                            </TableRow>
                        ) : (
                            rows.map((row, i) => (
                                <TableRow key={String(row.id ?? i)}>
                                    {columns.map((col) => (
                                        <TableCell key={col.key} className={col.className}>
                                            {col.render ? col.render(row) : String(row[col.key] ?? '—')}
                                        </TableCell>
                                    ))}
                                    {actions && (
                                        <TableCell className="text-right">{actions(row)}</TableCell>
                                    )}
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>

            {/* Pagination */}
            {showPagination && (
                <div className="flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
                    {isServer && pagination ? (
                        <p className="text-sm text-muted-foreground">
                            {pagination.from ?? 0}–{pagination.to ?? 0} sur {pagination.total}
                        </p>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            {filtered.length} résultat{filtered.length !== 1 ? 's' : ''}
                        </p>
                    )}
                    <ReactPaginate
                        pageCount={pageCount}
                        forcePage={forcePage}
                        onPageChange={handlePageChange}
                        previousLabel={<ChevronLeft className="h-4 w-4" />}
                        nextLabel={<ChevronRight className="h-4 w-4" />}
                        breakLabel="…"
                        marginPagesDisplayed={1}
                        pageRangeDisplayed={3}
                        containerClassName={PAGINATION_CLASSES.container}
                        pageLinkClassName={PAGINATION_CLASSES.pageLink}
                        activeClassName={PAGINATION_CLASSES.activePage}
                        previousLinkClassName={PAGINATION_CLASSES.navLink}
                        nextLinkClassName={PAGINATION_CLASSES.navLink}
                        disabledLinkClassName={PAGINATION_CLASSES.disabledLink}
                        breakLinkClassName={PAGINATION_CLASSES.break}
                        renderOnZeroPageCount={null}
                    />
                </div>
            )}
        </div>
    );
}
