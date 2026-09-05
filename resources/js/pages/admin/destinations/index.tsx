import { Head, Link, router } from '@inertiajs/react';
import { Globe, Pencil, PlusCircle, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { DataTable } from '@/components/admin/data-table';
import type { Column } from '@/components/admin/data-table';
import { DestinationFormDialog } from '@/components/admin/destination-form-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';
import type { DestinationRow, Paginated, VilleSummary } from '@/types';
import { destinationTrajetLabel, destinationVille } from '@/types/models/destination';

interface Props {
    destinations: Paginated<DestinationRow>;
    filters: { search?: string; actif?: string };
    villes: VilleSummary[];
}

function LocaliteCell({ destination, side }: { destination: DestinationRow; side: 'depart' | 'arrivee' }) {
    const localite = destinationVille(destination, side);

    if (!localite) {
        return <span className="text-muted-foreground">—</span>;
    }

    return (
        <div>
            <span className="font-medium">{localite.ville}</span>
            <p className="text-xs text-muted-foreground">
                {localite.pays} · {localite.code}
            </p>
        </div>
    );
}

const columns: Column<DestinationRow>[] = [
    {
        key: 'depart',
        label: 'Départ',
        render: (r) => <LocaliteCell destination={r} side="depart" />,
    },
    {
        key: 'arrivee',
        label: 'Arrivée',
        render: (r) => <LocaliteCell destination={r} side="arrivee" />,
    },
    {
        key: 'configuration',
        label: 'Configuration',
        render: (r) =>
            r.appliquer_configuration ? (
                <div className="text-sm">
                    <span className="tabular-nums">
                        {Number(r.montant).toLocaleString('fr-FR')} FCFA
                    </span>
                    <span className="text-muted-foreground">
                        {' '}
                        · {Number(r.commission_pourcentage).toLocaleString('fr-FR')} %
                    </span>
                </div>
            ) : (
                <span className="text-muted-foreground">Libre</span>
            ),
    },
    {
        key: 'offres_count',
        label: 'Offres',
        render: (r) => <span className="tabular-nums">{r.offres_count}</span>,
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

export default function DestinationsIndex({ destinations, filters, villes = [] }: Props) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<DestinationRow | null>(null);

    const openCreate = () => {
        setEditing(null);
        setDialogOpen(true);
    };

    const openEdit = (row: DestinationRow) => {
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
        router.get(admin.destinations.index().url, params as Record<string, string>, {
            preserveState: true,
            replace: true,
        });

    const supprimer = (row: DestinationRow) =>
        router.delete(admin.destinations.destroy(row.id).url, { preserveState: false });

    return (
        <>
            <Head title="Destinations" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Destinations</h1>
                        <p className="text-sm text-muted-foreground">
                            Composez des trajets à partir de deux localités (ville, pays, code) et
                            optionnellement une configuration tarifaire.
                        </p>
                    </div>
                    <div className="flex shrink-0 items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href={admin.villes.index()} prefetch>
                                <Globe className="mr-2 h-4 w-4" />
                                Villes
                            </Link>
                        </Button>
                        <Button onClick={openCreate}>
                            <PlusCircle className="mr-2 h-4 w-4" />
                            Nouvelle destination
                        </Button>
                    </div>
                </div>

                <DataTable
                    columns={columns}
                    data={destinations.data}
                    pagination={destinations.meta}
                    initialSearch={filters.search ?? ''}
                    initialFilter={filters.actif ?? ''}
                    searchPlaceholder="Ville, pays ou code..."
                    filterKey="actif"
                    filterOptions={filterOptions}
                    emptyTitle="Aucune destination"
                    emptyDescription="Créez d'abord des localités, puis un trajet départ → arrivée."
                    onSearchChange={(v) => go({ ...filters, search: v || undefined, page: 1 })}
                    onFilterChange={(v) => go({ ...filters, actif: v || undefined, page: 1 })}
                    onPageChange={(p) => go({ ...filters, page: p })}
                    actions={(row) => (
                        <div className="flex items-center gap-1">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => openEdit(row)}
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
                                title="Supprimer cette destination ?"
                                description={
                                    row.offres_count > 0
                                        ? 'Cette destination est liée à des offres et ne peut pas être supprimée.'
                                        : `La destination « ${destinationTrajetLabel(row)} » sera définitivement supprimée.`
                                }
                                confirmLabel="Supprimer"
                                onConfirm={() => supprimer(row)}
                            />
                        </div>
                    )}
                />
            </div>

            <DestinationFormDialog
                key={editing?.id ?? 'create'}
                open={dialogOpen}
                onOpenChange={handleDialogOpenChange}
                destination={editing}
                villes={villes}
            />
        </>
    );
}

DestinationsIndex.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Destinations', href: admin.destinations.index().url },
    ],
};
