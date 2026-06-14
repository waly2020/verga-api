import { Head, Link } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { ArrowRight, Eye } from 'lucide-react';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { DataTable, type Column } from '@/components/admin/data-table';
import { StatusBadge } from '@/components/admin/status-badge';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';
import admin from '@/routes/admin';

type ColisRow = Record<string, unknown> & {
    id: string;
    reference: string;
    commande: { id: string; code: string } | null;
    agence: { id: string; nom: string } | null;
    poids: string | null;
    statut: string;
};

interface Props {
    colis: Paginated<ColisRow>;
    filters: { search?: string; statut?: string };
}

const NEXT_ACTION: Record<string, { label: string; confirm: string }> = {
    'déposé':    { label: 'Expédier',           confirm: 'Confirmer l\'expédition de ce colis ?' },
    'en_transit': { label: 'Confirmer arrivée', confirm: 'Confirmer l\'arrivée à destination ?' },
    'arrivé':    { label: 'Marquer récupéré',   confirm: 'Marquer ce colis comme récupéré par le client ?' },
};

const columns: Column<ColisRow>[] = [
    { key: 'reference', label: 'Référence', render: (r) => <span className="font-mono text-xs font-medium">{r.reference}</span> },
    { key: 'commande',  label: 'Commande',  render: (r) => <span className="font-mono text-xs">{r.commande?.code ?? '—'}</span> },
    { key: 'agence',    label: 'Agence',    render: (r) => r.agence?.nom ?? '—' },
    { key: 'poids',     label: 'Poids',     render: (r) => r.poids ? `${r.poids} kg` : '—' },
    { key: 'statut',    label: 'Statut',    render: (r) => <StatusBadge status={r.statut} /> },
];

const filterOptions = [
    { label: 'Déposé',     value: 'déposé' },
    { label: 'En transit', value: 'en_transit' },
    { label: 'Arrivé',     value: 'arrivé' },
    { label: 'Récupéré',   value: 'récupéré' },
];

export default function ColisIndex({ colis, filters }: Props) {
    const go = (params: Record<string, string | number | undefined>) =>
        router.get(admin.colis.index().url, params as Record<string, string>, {
            preserveState: true,
            replace: true,
        });

    const avancerStatut = (row: ColisRow) =>
        router.patch(admin.colis.statut(row.id).url, {}, { preserveState: false });

    return (
        <>
            <Head title="Colis" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Colis</h1>
                    <p className="text-sm text-muted-foreground">Suivi des colis expédiés par les agences</p>
                </div>

                <DataTable
                    columns={columns}
                    data={colis.data}
                    pagination={colis.meta}
                    initialSearch={filters.search ?? ''}
                    initialFilter={filters.statut ?? ''}
                    searchPlaceholder="Rechercher par référence..."
                    filterKey="statut"
                    filterOptions={filterOptions}
                    emptyTitle="Aucun colis"
                    emptyDescription="Aucun colis n'a encore été enregistré."
                    onSearchChange={(v) => go({ ...filters, search: v || undefined, page: 1 })}
                    onFilterChange={(v) => go({ ...filters, statut: v || undefined, page: 1 })}
                    onPageChange={(p) => go({ ...filters, page: p })}
                    actions={(row) => {
                        const next = NEXT_ACTION[row.statut];
                        return (
                            <div className="flex items-center justify-end gap-2">
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={admin.colis.show(row.id).url}>
                                        <Eye className="mr-1 h-3.5 w-3.5" />
                                        Voir
                                    </Link>
                                </Button>

                                {next && (
                                    <ConfirmDialog
                                        trigger={
                                            <Button variant="outline" size="sm" className="text-primary hover:text-primary">
                                                <ArrowRight className="mr-1 h-3.5 w-3.5" />
                                                {next.label}
                                            </Button>
                                        }
                                        title={next.confirm}
                                        description={`Colis ${row.reference} — cette action met à jour le statut et enregistre un historique.`}
                                        confirmLabel={next.label}
                                        onConfirm={() => avancerStatut(row)}
                                    />
                                )}
                            </div>
                        );
                    }}
                />
            </div>
        </>
    );
}

ColisIndex.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Colis', href: admin.colis.index().url },
    ],
};
