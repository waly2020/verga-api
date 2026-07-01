import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { PlusCircle } from 'lucide-react';
import { useState } from 'react';
import { CreateOffreDialog } from '@/components/admin/create-offre-dialog';
import { DataTable  } from '@/components/admin/data-table';
import type {Column} from '@/components/admin/data-table';
import { ExportButtons } from '@/components/admin/export-buttons';
import { StatusBadge } from '@/components/admin/status-badge';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';
import type { AgenceSummary, OffreRow, Paginated } from '@/types';

const TYPE_LABELS: Record<string, string> = {
    particulier: 'Au kg',
    metre_cube: 'Au m³',
    conteneur: 'Conteneur',
};

interface Props {
    offres: Paginated<OffreRow>;
    filters: { search?: string; statut?: string };
    agences: AgenceSummary[];
}

const columns: Column<OffreRow>[] = [
    { key: 'titre',       label: 'Offre',       render: (r) => <span className="font-medium">{r.titre}</span> },
    { key: 'agence',      label: 'Agence',       render: (r) => r.agence?.nom ?? '—' },
    { key: 'type',        label: 'Type',         render: (r) => TYPE_LABELS[r.type] ?? r.type },
    { key: 'prix',        label: 'Prix',         render: (r) => `${Number(r.prix).toLocaleString('fr-FR')} FCFA` },
    {
        key: 'capacite_disponible',
        label: 'Stock',
        render: (r) => (
            <span className="tabular-nums text-sm">
                {Number(r.capacite_disponible).toLocaleString('fr-FR')}
                <span className="text-muted-foreground"> / {Number(r.capacite_totale).toLocaleString('fr-FR')}</span>
            </span>
        ),
    },
    { key: 'origine',     label: 'Origine' },
    { key: 'destination', label: 'Destination' },
    { key: 'statut',      label: 'Statut',       render: (r) => <StatusBadge status={r.statut} /> },
];

const filterOptions = [
    { label: 'Active',   value: 'active' },
    { label: 'Inactive', value: 'inactive' },
    { label: 'Archivée', value: 'archivée' },
];

export default function OffresIndex({ offres, filters, agences }: Props) {
    const [createOpen, setCreateOpen] = useState(false);

    const go = (params: Record<string, string | number | undefined>) =>
        router.get(admin.offres.index().url, params as Record<string, string>, {
            preserveState: true,
            replace: true,
        });

    return (
        <>
            <Head title="Offres" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Offres</h1>
                        <p className="text-sm text-muted-foreground">Consultez et gérez les offres publiées par les agences</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <ExportButtons module="offres" />
                        <Button onClick={() => setCreateOpen(true)}>
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
                />
            </div>

            <CreateOffreDialog
                open={createOpen}
                onOpenChange={setCreateOpen}
                agences={agences}
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
