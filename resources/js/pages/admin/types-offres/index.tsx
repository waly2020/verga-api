import { Head, router } from '@inertiajs/react';
import { Pencil, PlusCircle, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { DataTable } from '@/components/admin/data-table';
import type { Column } from '@/components/admin/data-table';
import { TypeOffreFormDialog } from '@/components/admin/type-offre-form-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';
import type { Paginated, TypeOffreRow } from '@/types';

interface Props {
    types_offres: Paginated<TypeOffreRow>;
    filters: { search?: string; actif?: string };
}

const columns: Column<TypeOffreRow>[] = [
    {
        key: 'nom',
        label: 'Type',
        render: (r) => (
            <div>
                <span className="font-medium">{r.nom}</span>
                <p className="text-xs text-muted-foreground">{r.slug}</p>
            </div>
        ),
    },
    {
        key: 'unite',
        label: 'Unité',
        render: (r) => (
            <span>
                {r.unite}
                <span className="text-muted-foreground"> · {r.unite_label}</span>
            </span>
        ),
    },
    {
        key: 'quantite_min',
        label: 'Qté min.',
        render: (r) => (
            <span className="tabular-nums">
                {Number(r.quantite_min).toLocaleString('fr-FR')}
                {r.quantite_entier && (
                    <span className="ml-1 text-xs text-muted-foreground">(entier)</span>
                )}
            </span>
        ),
    },
    {
        key: 'offres_count',
        label: 'Offres',
        render: (r) => (
            <span className="tabular-nums">{r.offres_count}</span>
        ),
    },
    {
        key: 'actif',
        label: 'Statut',
        render: (r) => (
            <Badge variant={r.actif ? 'default' : 'secondary'}>
                {r.actif ? 'Actif' : 'Inactif'}
            </Badge>
        ),
    },
];

const filterOptions = [
    { label: 'Actifs', value: '1' },
    { label: 'Inactifs', value: '0' },
];

export default function TypesOffresIndex({ types_offres, filters }: Props) {
    const [createOpen, setCreateOpen] = useState(false);
    const [editing, setEditing] = useState<TypeOffreRow | null>(null);

    const go = (params: Record<string, string | number | undefined>) =>
        router.get(admin.typesOffres.index().url, params as Record<string, string>, {
            preserveState: true,
            replace: true,
        });

    const supprimer = (row: TypeOffreRow) =>
        router.delete(admin.typesOffres.destroy(row.id).url, { preserveState: false });

    return (
        <>
            <Head title="Types d'offre" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Types d&apos;offre</h1>
                        <p className="text-sm text-muted-foreground">
                            Gérez les modalités de tarification (kg, m³, conteneur…) et leurs règles de quantité.
                        </p>
                    </div>
                    <Button onClick={() => setCreateOpen(true)}>
                        <PlusCircle className="mr-2 h-4 w-4" />
                        Nouveau type
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    data={types_offres.data}
                    pagination={types_offres.meta}
                    initialSearch={filters.search ?? ''}
                    initialFilter={filters.actif ?? ''}
                    searchPlaceholder="Rechercher un type..."
                    filterKey="actif"
                    filterOptions={filterOptions}
                    emptyTitle="Aucun type d'offre"
                    emptyDescription="Créez un premier type pour classifier les offres."
                    onSearchChange={(v) => go({ ...filters, search: v || undefined, page: 1 })}
                    onFilterChange={(v) => go({ ...filters, actif: v || undefined, page: 1 })}
                    onPageChange={(p) => go({ ...filters, page: p })}
                    actions={(row) => (
                        <div className="flex items-center gap-1">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => setEditing(row)}
                            >
                                <Pencil className="h-3.5 w-3.5" />
                            </Button>
                            <ConfirmDialog
                                trigger={
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="text-destructive hover:text-destructive"
                                        disabled={row.offres_count > 0}
                                    >
                                        <Trash2 className="h-3.5 w-3.5" />
                                    </Button>
                                }
                                title="Supprimer ce type d'offre ?"
                                description={
                                    row.offres_count > 0
                                        ? 'Ce type est lié à des offres et ne peut pas être supprimé.'
                                        : `Le type « ${row.nom} » sera définitivement supprimé.`
                                }
                                confirmLabel="Supprimer"
                                onConfirm={() => supprimer(row)}
                            />
                        </div>
                    )}
                />
            </div>

            <TypeOffreFormDialog open={createOpen} onOpenChange={setCreateOpen} />
            <TypeOffreFormDialog
                open={editing !== null}
                onOpenChange={(open) => !open && setEditing(null)}
                typeOffre={editing}
            />
        </>
    );
}

TypesOffresIndex.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: "Types d'offre", href: admin.typesOffres.index().url },
    ],
};
