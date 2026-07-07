import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { Banknote, PlusCircle } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import {
    CreateReversementDialog,
    type AgenceReversementOption,
} from '@/components/admin/create-reversement-dialog';
import { DataTable  } from '@/components/admin/data-table';
import type {Column} from '@/components/admin/data-table';
import { ExportButtons } from '@/components/admin/export-buttons';
import { StatusBadge } from '@/components/admin/status-badge';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';
import type { Paginated } from '@/types';

type ReversementRow = Record<string, unknown> & {
    id: string;
    agence: { id: string; nom: string } | null;
    montant: string;
    periode: string;
    statut: string;
    effectue_le: string | null;
};

interface Props {
    reversements: Paginated<ReversementRow>;
    filters: { search?: string; statut?: string };
    agences: AgenceReversementOption[];
}

const columns: Column<ReversementRow>[] = [
    { key: 'agence', label: 'Agence', render: (r) => <span className="font-medium">{r.agence?.nom ?? '—'}</span> },
    {
        key: 'montant',
        label: 'Montant',
        render: (r) => (
            <span className="font-medium tabular-nums">
                {Number(r.montant).toLocaleString('fr-FR')} FCFA
            </span>
        ),
    },
    { key: 'periode', label: 'Période' },
    { key: 'statut', label: 'Statut', render: (r) => <StatusBadge status={r.statut} /> },
    {
        key: 'effectue_le',
        label: 'Effectué le',
        render: (r) =>
            r.effectue_le ? new Date(String(r.effectue_le)).toLocaleDateString('fr-FR') : '—',
    },
];

const filterOptions = [
    { label: 'En attente', value: 'en_attente' },
    { label: 'Effectué', value: 'effectué' },
];

export default function ReversementsIndex({ reversements, filters, agences }: Props) {
    const [createOpen, setCreateOpen] = useState(false);

    const go = (params: Record<string, string | number | undefined>) =>
        router.get(admin.reversements.index().url, params as Record<string, string>, {
            preserveState: true,
            replace: true,
        });

    const effectuer = (row: ReversementRow) =>
        router.patch(admin.reversements.effectuer(row.id).url, {}, { preserveState: false });

    return (
        <>
            <Head title="Reversements" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Reversements</h1>
                        <p className="text-sm text-muted-foreground">Gérez les reversements aux agences partenaires</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <ExportButtons module="reversements" />
                        <Button onClick={() => setCreateOpen(true)}>
                            <PlusCircle className="mr-2 h-4 w-4" />
                            Nouveau reversement
                        </Button>
                    </div>
                </div>

                <DataTable
                    columns={columns}
                    data={reversements.data}
                    pagination={reversements.meta}
                    initialSearch={filters.search ?? ''}
                    initialFilter={filters.statut ?? ''}
                    searchPlaceholder="Rechercher une agence..."
                    filterKey="statut"
                    filterOptions={filterOptions}
                    emptyTitle="Aucun reversement"
                    emptyDescription="Aucun reversement n'a encore été enregistré."
                    onSearchChange={(v) => go({ ...filters, search: v || undefined, page: 1 })}
                    onFilterChange={(v) => go({ ...filters, statut: v || undefined, page: 1 })}
                    onPageChange={(p) => go({ ...filters, page: p })}
                    actions={(row) =>
                        row.statut === 'en_attente' ? (
                            <ConfirmDialog
                                trigger={
                                    <Button size="sm">
                                        <Banknote className="mr-1 h-3.5 w-3.5" />
                                        Valider
                                    </Button>
                                }
                                title="Valider ce reversement ?"
                                description={`Confirmer le reversement de ${Number(row.montant).toLocaleString('fr-FR')} FCFA à ${row.agence?.nom} pour ${row.periode}. Le solde de l'agence sera mis à jour.`}
                                confirmLabel="Valider"
                                variant="default"
                                onConfirm={() => effectuer(row)}
                            />
                        ) : null
                    }
                />
            </div>

            <CreateReversementDialog
                open={createOpen}
                onOpenChange={setCreateOpen}
                agences={agences}
            />
        </>
    );
}

ReversementsIndex.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Reversements', href: admin.reversements.index().url },
    ],
};
