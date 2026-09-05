import { Head, Link, router } from '@inertiajs/react';
import { Pencil, PlusCircle, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { DataTable } from '@/components/admin/data-table';
import type { Column } from '@/components/admin/data-table';
import { VilleFormDialog } from '@/components/admin/ville-form-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';
import type { Paginated, VilleRow } from '@/types';

interface Props {
    villes: Paginated<VilleRow>;
    filters: { search?: string; actif?: string };
    pays_existants: string[];
}

function usages(row: VilleRow): number {
    return row.destinations_depart_count + row.destinations_arrivee_count;
}

const columns: Column<VilleRow>[] = [
    {
        key: 'ville',
        label: 'Ville',
        render: (r) => (
            <div>
                <span className="font-medium">{r.ville}</span>
                <p className="text-xs text-muted-foreground">{r.pays}</p>
            </div>
        ),
    },
    {
        key: 'code',
        label: 'Code',
        render: (r) => <span className="font-mono text-sm">{r.code}</span>,
    },
    {
        key: 'usages',
        label: 'Destinations',
        render: (r) => <span className="tabular-nums">{usages(r)}</span>,
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
    { label: 'Actives', value: '1' },
    { label: 'Inactives', value: '0' },
];

export default function VillesIndex({ villes, filters, pays_existants = [] }: Props) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<VilleRow | null>(null);

    const openCreate = () => {
        setEditing(null);
        setDialogOpen(true);
    };

    const openEdit = (row: VilleRow) => {
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
        router.get(admin.villes.index().url, params as Record<string, string>, {
            preserveState: true,
            replace: true,
        });

    const supprimer = (row: VilleRow) =>
        router.delete(admin.villes.destroy(row.id).url, { preserveState: false });

    return (
        <>
            <Head title="Villes" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Villes</h1>
                        <p className="text-sm text-muted-foreground">
                            Référentiel des villes. Le pays se choisit dans la liste déjà créée, ou
                            s&apos;ajoute à la première ville d&apos;un nouveau pays.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href={admin.destinations.index()} prefetch>
                                Destinations
                            </Link>
                        </Button>
                        <Button onClick={openCreate}>
                            <PlusCircle className="mr-2 h-4 w-4" />
                            Nouvelle ville
                        </Button>
                    </div>
                </div>

                <DataTable
                    columns={columns}
                    data={villes.data}
                    pagination={villes.meta}
                    initialSearch={filters.search ?? ''}
                    initialFilter={filters.actif ?? ''}
                    searchPlaceholder="Rechercher un pays, une ville ou un code..."
                    filterKey="actif"
                    filterOptions={filterOptions}
                    emptyTitle="Aucune ville"
                    emptyDescription="Créez une première ville pour pouvoir construire des trajets."
                    onSearchChange={(v) => go({ ...filters, search: v || undefined, page: 1 })}
                    onFilterChange={(v) => go({ ...filters, actif: v || undefined, page: 1 })}
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
                                        disabled={usages(row) > 0}
                                    >
                                        <Trash2 className="h-3.5 w-3.5" />
                                    </Button>
                                }
                                title="Supprimer cette ville ?"
                                description={
                                    usages(row) > 0
                                        ? 'Cette ville est utilisée par des destinations et ne peut pas être supprimée.'
                                        : `La ville « ${row.ville} (${row.pays}) » sera définitivement supprimée.`
                                }
                                confirmLabel="Supprimer"
                                onConfirm={() => supprimer(row)}
                            />
                        </div>
                    )}
                />
            </div>

            <VilleFormDialog
                key={editing?.id ?? 'create'}
                open={dialogOpen}
                onOpenChange={handleDialogOpenChange}
                ville={editing}
                paysExistants={pays_existants}
            />
        </>
    );
}

VillesIndex.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Villes', href: admin.villes.index().url },
    ],
};
