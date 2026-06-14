import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { ExportButtons } from '@/components/admin/export-buttons';
import { DataTable, type Column } from '@/components/admin/data-table';
import { StatusBadge } from '@/components/admin/status-badge';
import type { Paginated } from '@/types';
import admin from '@/routes/admin';

type PaiementRow = Record<string, unknown> & {
    id: string;
    reference: string;
    commande: { id: string; code: string } | null;
    montant: string;
    methode: string;
    statut: string;
    created_at: string;
};

interface Props {
    paiements: Paginated<PaiementRow>;
    filters: { search?: string; statut?: string };
}

const columns: Column<PaiementRow>[] = [
    { key: 'reference', label: 'Référence', render: (r) => <span className="font-mono text-xs font-medium">{r.reference}</span> },
    { key: 'commande', label: 'Commande', render: (r) => <span className="font-mono text-xs">{r.commande?.code ?? '—'}</span> },
    {
        key: 'montant',
        label: 'Montant',
        render: (r) => (
            <span className="font-medium tabular-nums">
                {Number(r.montant).toLocaleString('fr-FR')} FCFA
            </span>
        ),
    },
    { key: 'methode', label: 'Méthode' },
    { key: 'statut', label: 'Statut', render: (r) => <StatusBadge status={r.statut} /> },
    {
        key: 'created_at',
        label: 'Date',
        render: (r) => new Date(String(r.created_at)).toLocaleDateString('fr-FR'),
    },
];

const filterOptions = [
    { label: 'En attente', value: 'en_attente' },
    { label: 'Validé', value: 'validé' },
    { label: 'Remboursé', value: 'remboursé' },
    { label: 'Échec', value: 'échec' },
];

export default function PaiementsIndex({ paiements, filters }: Props) {
    const go = (params: Record<string, string | number | undefined>) =>
        router.get(admin.paiements.index().url, params as Record<string, string>, {
            preserveState: true,
            replace: true,
        });

    return (
        <>
            <Head title="Paiements" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Paiements</h1>
                        <p className="text-sm text-muted-foreground">Historique des transactions financières</p>
                    </div>
                    <ExportButtons module="paiements" />
                </div>

                <DataTable
                    columns={columns}
                    data={paiements.data}
                    pagination={paiements.meta}
                    initialSearch={filters.search ?? ''}
                    initialFilter={filters.statut ?? ''}
                    searchPlaceholder="Rechercher par référence..."
                    filterKey="statut"
                    filterOptions={filterOptions}
                    emptyTitle="Aucun paiement"
                    emptyDescription="Aucune transaction n'a encore été enregistrée."
                    onSearchChange={(v) => go({ ...filters, search: v || undefined, page: 1 })}
                    onFilterChange={(v) => go({ ...filters, statut: v || undefined, page: 1 })}
                    onPageChange={(p) => go({ ...filters, page: p })}
                />
            </div>
        </>
    );
}

PaiementsIndex.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Paiements', href: admin.paiements.index().url },
    ],
};
