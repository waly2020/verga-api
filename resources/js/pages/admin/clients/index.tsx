import { Head, Link } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import { DataTable  } from '@/components/admin/data-table';
import type {Column} from '@/components/admin/data-table';
import { ExportButtons } from '@/components/admin/export-buttons';
import { StatusBadge } from '@/components/admin/status-badge';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';
import type { ClientRow, Paginated } from '@/types';

interface Props {
    clients: Paginated<ClientRow>;
    filters: { search?: string; statut?: string };
}

const columns: Column<ClientRow>[] = [
    {
        key: 'nom',
        label: 'Client',
        render: (r) => (
            <span className="font-medium">
                {r.prenom} {r.nom}
            </span>
        ),
    },
    { key: 'email', label: 'Email' },
    { key: 'telephone', label: 'Téléphone' },
    { key: 'ville', label: 'Ville' },
    { key: 'type', label: 'Type' },
    { key: 'commandes_count', label: 'Commandes', render: (r) => <span className="tabular-nums">{r.commandes_count}</span> },
    { key: 'statut', label: 'Statut', render: (r) => <StatusBadge status={r.statut} /> },
];

const filterOptions = [
    { label: 'Actif', value: 'actif' },
    { label: 'Bloqué', value: 'bloqué' },
];

export default function ClientsIndex({ clients, filters }: Props) {
    const go = (params: Record<string, string | number | undefined>) =>
        router.get(admin.clients.index().url, params as Record<string, string>, {
            preserveState: true,
            replace: true,
        });

    return (
        <>
            <Head title="Clients" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Clients</h1>
                        <p className="text-sm text-muted-foreground">
                            Consultation des clients inscrits via l&apos;application externe
                        </p>
                    </div>
                    <ExportButtons module="clients" />
                </div>

                <DataTable
                    columns={columns}
                    data={clients.data}
                    pagination={clients.meta}
                    initialSearch={filters.search ?? ''}
                    initialFilter={filters.statut ?? ''}
                    searchPlaceholder="Rechercher un client..."
                    filterKey="statut"
                    filterOptions={filterOptions}
                    emptyTitle="Aucun client"
                    emptyDescription="Aucun client n'est encore inscrit sur la plateforme."
                    onSearchChange={(v) => go({ ...filters, search: v || undefined, page: 1 })}
                    onFilterChange={(v) => go({ ...filters, statut: v || undefined, page: 1 })}
                    onPageChange={(p) => go({ ...filters, page: p })}
                    actions={(row) => (
                        <Button variant="outline" size="sm" asChild>
                            <Link href={admin.clients.show(row.id).url}>
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

ClientsIndex.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Clients', href: admin.clients.index().url },
    ],
};
