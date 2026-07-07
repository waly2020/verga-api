import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import { DataTable  } from '@/components/admin/data-table';
import type {Column} from '@/components/admin/data-table';
import { ExportButtons } from '@/components/admin/export-buttons';
import { StatusBadge } from '@/components/admin/status-badge';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';
import type { PaiementRow, Paginated } from '@/types';

interface Props {
    paiements: Paginated<PaiementRow>;
    filters: { search?: string; statut?: string };
}

const columns: Column<PaiementRow>[] = [
    {
        key: 'code',
        label: 'Code VERGA',
        render: (r) => <span className="font-mono text-xs font-medium">{r.code ?? '—'}</span>,
    },
    {
        key: 'bamboo_reference',
        label: 'Réf. Bamboo',
        render: (r) => <span className="font-mono text-xs">{r.bamboo_reference ?? r.reference ?? '—'}</span>,
    },
    { key: 'commande', label: 'Commande', render: (r) => <span className="font-mono text-xs">{r.commande?.code ?? '—'}</span> },
    {
        key: 'montant_sous_total',
        label: 'Sous-total',
        render: (r) => (
            <span className="tabular-nums text-muted-foreground">
                {r.montant_sous_total != null
                    ? `${Number(r.montant_sous_total).toLocaleString('fr-FR')} FCFA`
                    : '—'}
            </span>
        ),
    },
    {
        key: 'montant_commission_client',
        label: 'Commission VERGA',
        render: (r) => (
            <span className="tabular-nums text-muted-foreground">
                {r.montant_commission_client != null && Number(r.montant_commission_client) > 0
                    ? `${Number(r.montant_commission_client).toLocaleString('fr-FR')} FCFA`
                    : '—'}
            </span>
        ),
    },
    {
        key: 'montant',
        label: 'Total payé',
        render: (r) => (
            <span className="font-medium tabular-nums">
                {Number(r.montant).toLocaleString('fr-FR')} FCFA
            </span>
        ),
    },
    { key: 'methode', label: 'Méthode' },
    { key: 'statut', label: 'Statut', render: (r) => <StatusBadge status={r.statut} /> },
    {
        key: 'bamboo_message',
        label: 'Message Bamboo',
        render: (r) => (
            <span className="max-w-[220px] truncate text-xs text-muted-foreground" title={r.bamboo_message ?? undefined}>
                {r.bamboo_message ?? '—'}
            </span>
        ),
    },
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

    const verifierStatut = (row: PaiementRow) =>
        router.patch(admin.paiements.verifierStatut(row.id).url, {}, { preserveState: false });

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
                    searchPlaceholder="Rechercher par code ou référence Bamboo..."
                    filterKey="statut"
                    filterOptions={filterOptions}
                    emptyTitle="Aucun paiement"
                    emptyDescription="Aucune transaction n'a encore été enregistrée."
                    onSearchChange={(v) => go({ ...filters, search: v || undefined, page: 1 })}
                    onFilterChange={(v) => go({ ...filters, statut: v || undefined, page: 1 })}
                    onPageChange={(p) => go({ ...filters, page: p })}
                    actions={(row) =>
                        row.statut === 'en_attente' && row.code ? (
                            <Button size="sm" variant="outline" onClick={() => verifierStatut(row)}>
                                <RefreshCw className="mr-1 h-3.5 w-3.5" />
                                Vérifier
                            </Button>
                        ) : null
                    }
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
