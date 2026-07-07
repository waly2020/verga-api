import type { PaginationMeta } from '@/types';

type PaginatedSource = {
    data: unknown[];
    meta?: PaginationMeta;
    current_page?: number;
    last_page?: number;
    per_page?: number;
    total?: number;
    from?: number | null;
    to?: number | null;
};

/** Extrait les métadonnées depuis le format Inertia/Laravel ou `{ data, meta }`. */
export function paginationMeta(source: PaginatedSource): PaginationMeta | undefined {
    if (source.meta) {
        return source.meta;
    }

    if (typeof source.current_page !== 'number') {
        return undefined;
    }

    return {
        current_page: source.current_page,
        last_page: source.last_page ?? 1,
        per_page: source.per_page ?? 15,
        total: source.total ?? source.data.length,
        from: source.from ?? null,
        to: source.to ?? null,
    };
}
