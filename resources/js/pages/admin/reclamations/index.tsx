import { Head, Link } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { Eye, PlayCircle } from 'lucide-react';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { ExportButtons } from '@/components/admin/export-buttons';
import { DataTable, type Column } from '@/components/admin/data-table';
import { StatusBadge } from '@/components/admin/status-badge';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';
import admin from '@/routes/admin';

type ReclamationRow = Record<string, unknown> & {
    id: string;
    nom: string;
    prenom: string | null;
    objet: string;
    agence: { id: string; nom: string } | null;
    statut: string;
    created_at: string;
};

interface Props {
    reclamations: Paginated<ReclamationRow>;
    filters: { search?: string; statut?: string };
}

const columns: Column<ReclamationRow>[] = [
    {
        key: 'nom',
        label: 'Client',
        render: (r) => (
            <span className="font-medium">
                {r.prenom ? `${r.prenom} ${r.nom}` : r.nom}
            </span>
        ),
    },
    { key: 'objet', label: 'Objet', className: 'max-w-xs truncate' },
    { key: 'agence', label: 'Agence', render: (r) => r.agence?.nom ?? '—' },
    { key: 'statut', label: 'Statut', render: (r) => <StatusBadge status={r.statut} /> },
    {
        key: 'created_at',
        label: 'Date',
        render: (r) => new Date(String(r.created_at)).toLocaleDateString('fr-FR'),
    },
];

const filterOptions = [
    { label: 'Ouverte',  value: 'ouverte' },
    { label: 'En cours', value: 'en_cours' },
    { label: 'Résolue',  value: 'résolue' },
    { label: 'Fermée',   value: 'fermée' },
];

export default function ReclamationsIndex({ reclamations, filters }: Props) {
    const go = (params: Record<string, string | number | undefined>) =>
        router.get(admin.reclamations.index().url, params as Record<string, string>, {
            preserveState: true,
            replace: true,
        });

    const prendreEnCharge = (row: ReclamationRow) =>
        router.patch(admin.reclamations.statut(row.id).url, { statut: 'en_cours' }, { preserveState: false });

    return (
        <>
            <Head title="Réclamations" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Réclamations</h1>
                        <p className="text-sm text-muted-foreground">Suivez et traitez les litiges clients</p>
                    </div>
                    <ExportButtons module="réclamations" />
                </div>

                <DataTable
                    columns={columns}
                    data={reclamations.data}
                    pagination={reclamations.meta}
                    initialSearch={filters.search ?? ''}
                    initialFilter={filters.statut ?? ''}
                    searchPlaceholder="Rechercher un client..."
                    filterKey="statut"
                    filterOptions={filterOptions}
                    emptyTitle="Aucune réclamation"
                    emptyDescription="Aucune réclamation n'a encore été soumise."
                    onSearchChange={(v) => go({ ...filters, search: v || undefined, page: 1 })}
                    onFilterChange={(v) => go({ ...filters, statut: v || undefined, page: 1 })}
                    onPageChange={(p) => go({ ...filters, page: p })}
                    actions={(row) => (
                        <div className="flex items-center justify-end gap-2">
                            <Button variant="outline" size="sm" asChild>
                                <Link href={admin.reclamations.show(row.id).url}>
                                    <Eye className="mr-1 h-3.5 w-3.5" />
                                    Voir
                                </Link>
                            </Button>

                            {row.statut === 'ouverte' && (
                                <ConfirmDialog
                                    trigger={
                                        <Button variant="outline" size="sm" className="text-blue-600 hover:text-blue-600">
                                            <PlayCircle className="mr-1 h-3.5 w-3.5" />
                                            Prendre en charge
                                        </Button>
                                    }
                                    title="Prendre en charge cette réclamation ?"
                                    description={`La réclamation de "${row.prenom ? `${row.prenom} ${row.nom}` : row.nom}" passera en statut "En cours".`}
                                    confirmLabel="Prendre en charge"
                                    onConfirm={() => prendreEnCharge(row)}
                                />
                            )}
                        </div>
                    )}
                />
            </div>
        </>
    );
}

ReclamationsIndex.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Réclamations', href: admin.reclamations.index().url },
    ],
};
