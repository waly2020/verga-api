import { Head, router } from '@inertiajs/react';
import { Pencil, PlusCircle, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { DataTable } from '@/components/admin/data-table';
import type { Column } from '@/components/admin/data-table';
import { ExportButtons } from '@/components/admin/export-buttons';
import { OffreFormDialog } from '@/components/admin/offre-form-dialog';
import { StatusBadge } from '@/components/admin/status-badge';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';
import type { AgenceSummary, OffreRow, Paginated, TypeOffreApi } from '@/types';

const TYPE_LABELS: Record<string, string> = {
    particulier: 'Au kg',
    metre_cube: 'Au m³',
    conteneur: 'Conteneur',
};

function typeLabel(row: OffreRow): string {
    return row.type_offre?.nom ?? TYPE_LABELS[row.type] ?? row.type;
}

interface Props {
    offres: Paginated<OffreRow>;
    filters: { search?: string; statut?: string };
    agences: AgenceSummary[];
    types_offres: TypeOffreApi[];
}

const columns: Column<OffreRow>[] = [
    { key: 'titre', label: 'Offre', render: (r) => <span className="font-medium">{r.titre}</span> },
    { key: 'agence', label: 'Agence', render: (r) => r.agence?.nom ?? '—' },
    { key: 'type', label: 'Type', render: (r) => typeLabel(r) },
    { key: 'prix', label: 'Prix', render: (r) => `${Number(r.prix).toLocaleString('fr-FR')} FCFA` },
    {
        key: 'capacite_disponible',
        label: 'Stock',
        render: (r) => (
            <span className="tabular-nums text-sm">
                {r.capacite_illimitee ? (
                    <span className="text-muted-foreground">Illimitée</span>
                ) : (
                    <>
                        {Number(r.capacite_disponible).toLocaleString('fr-FR')}
                        <span className="text-muted-foreground">
                            {' '}
                            / {Number(r.capacite_totale).toLocaleString('fr-FR')}
                        </span>
                    </>
                )}
            </span>
        ),
    },
    { key: 'origine', label: 'Origine' },
    { key: 'destination', label: 'Destination' },
    { key: 'statut', label: 'Statut', render: (r) => <StatusBadge status={r.statut} /> },
];

const filterOptions = [
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
    { label: 'Archivée', value: 'archivée' },
];

export default function OffresIndex({ offres, filters, agences, types_offres }: Props) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<OffreRow | null>(null);

    const openCreate = () => {
        setEditing(null);
        setDialogOpen(true);
    };

    const openEdit = (row: OffreRow) => {
        setEditing(row);
        setDialogOpen(true);
    };

    const handleDialogOpenChange = (open: boolean) => {
        setDialogOpen(open);
        if (!open) {
            setEditing(null);
        }
    };

    const go = (params: Record<string, string | number | undefined>) =>
        router.get(admin.offres.index().url, params as Record<string, string>, {
            preserveState: true,
            replace: true,
        });

    const supprimer = (row: OffreRow) =>
        router.delete(admin.offres.destroy(row.id).url, { preserveState: false });

    return (
        <>
            <Head title="Offres" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Offres</h1>
                        <p className="text-sm text-muted-foreground">
                            Consultez et gérez les offres publiées par les agences
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <ExportButtons module="offres" />
                        <Button onClick={openCreate}>
                            <PlusCircle className="mr-2 h-4 w-4" />
                            Nouvelle offre
                        </Button>
                    </div>
                </div>

                <DataTable
                    columns={columns}
                    data={offres.data}
                    pagination={offres.meta}
                    initialSearch={filters.search ?? ''}
                    initialFilter={filters.statut ?? ''}
                    searchPlaceholder="Rechercher une offre..."
                    filterKey="statut"
                    filterOptions={filterOptions}
                    emptyTitle="Aucune offre"
                    emptyDescription="Aucune offre n'a encore été publiée."
                    onSearchChange={(v) => go({ ...filters, search: v || undefined, page: 1 })}
                    onFilterChange={(v) => go({ ...filters, statut: v || undefined, page: 1 })}
                    onPageChange={(p) => go({ ...filters, page: p })}
                    actions={(row) => (
                        <div className="flex items-center gap-1">
                            <Button variant="outline" size="sm" onClick={() => openEdit(row)}>
                                <Pencil className="h-3.5 w-3.5" />
                            </Button>
                            <ConfirmDialog
                                trigger={
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="text-destructive hover:text-destructive"
                                    >
                                        <Trash2 className="h-3.5 w-3.5" />
                                    </Button>
                                }
                                title="Supprimer cette offre ?"
                                description={`L'offre « ${row.titre} » sera définitivement supprimée si elle n'est liée à aucune commande.`}
                                confirmLabel="Supprimer"
                                onConfirm={() => supprimer(row)}
                            />
                        </div>
                    )}
                />
            </div>

            <OffreFormDialog
                key={editing?.id ?? 'create'}
                open={dialogOpen}
                onOpenChange={handleDialogOpenChange}
                agences={agences}
                typesOffres={types_offres}
                offre={editing}
            />
        </>
    );
}

OffresIndex.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Offres', href: admin.offres.index().url },
    ],
};
