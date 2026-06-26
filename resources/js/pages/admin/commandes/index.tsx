import { Head, Link } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import { ExportButtons } from '@/components/admin/export-buttons';
import { DataTable, type Column } from '@/components/admin/data-table';
import { StatusBadge } from '@/components/admin/status-badge';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';
import admin from '@/routes/admin';

type CommandeRow = Record<string, unknown> & {
    id: string;
    code: string;
    client: { id: string; nom: string; prenom: string } | null;
    agence: { id: string; nom: string } | null;
    quantite: string;
    montant_total: string;
    statut: string;
    created_at: string;
};

interface Props {
    commandes: Paginated<CommandeRow>;
    filters: { search?: string; statut?: string };
}

const columns: Column<CommandeRow>[] = [
    { key: 'code', label: 'Code', render: (r) => <span className="font-mono text-xs font-medium">{r.code}</span> },
    { key: 'client', label: 'Client', render: (r) => r.client ? `${r.client.prenom} ${r.client.nom}` : '—' },
    { key: 'agence', label: 'Agence', render: (r) => r.agence?.nom ?? '—' },
    { key: 'quantite', label: 'Quantité' },
    {
        key: 'montant_total',
        label: 'Montant',
        render: (r) => (
            <span className="font-medium tabular-nums">
                {Number(r.montant_total).toLocaleString('fr-FR')} FCFA
            </span>
        ),
    },
    { key: 'statut', label: 'Statut', render: (r) => <StatusBadge status={r.statut} /> },
    {
        key: 'created_at',
        label: 'Date',
        render: (r) => new Date(String(r.created_at)).toLocaleDateString('fr-FR'),
    },
];

const filterOptions = [
    { label: 'En attente', value: 'en_attente' },
    { label: 'Confirmée', value: 'confirmée' },
    { label: 'Annulée', value: 'annulée' },
];

export default function CommandesIndex({ commandes, filters }: Props) {
    const go = (params: Record<string, string | number | undefined>) =>
        router.get(admin.commandes.index().url, params as Record<string, string>, {
            preserveState: true,
            replace: true,
        });

    return (
        <>
            <Head title="Commandes" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Commandes</h1>
                        <p className="text-sm text-muted-foreground">Historique des achats effectués par les clients</p>
                    </div>
                    <ExportButtons module="commandes" />
                </div>

                <DataTable
                    columns={columns}
                    data={commandes.data}
                    pagination={commandes.meta}
                    initialSearch={filters.search ?? ''}
                    initialFilter={filters.statut ?? ''}
                    searchPlaceholder="Rechercher par code..."
                    filterKey="statut"
                    filterOptions={filterOptions}
                    emptyTitle="Aucune commande"
                    emptyDescription="Aucune commande n'a encore été passée sur la plateforme."
                    onSearchChange={(v) => go({ ...filters, search: v || undefined, page: 1 })}
                    onFilterChange={(v) => go({ ...filters, statut: v || undefined, page: 1 })}
                    onPageChange={(p) => go({ ...filters, page: p })}
                    actions={(row) => (
                        <Button variant="outline" size="sm" asChild>
                            <Link href={admin.commandes.show(row.id).url}>
                                <Eye className="mr-1 h-3.5 w-3.5" />
                                Voir
                            </Link>
                        </Button>
                    )}
                />
            </div>
        </>
    );
}

CommandesIndex.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Commandes', href: admin.commandes.index().url },
    ],
};
