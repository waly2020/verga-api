import { Head, Link, router } from '@inertiajs/react';
import { PlusCircle, RefreshCw, Settings } from 'lucide-react';
import { useState } from 'react';
import { DataTable } from '@/components/admin/data-table';
import type { Column } from '@/components/admin/data-table';
import { PubliciteFormDialog } from '@/components/admin/publicite-form-dialog';
import { PubliciteStatutDialog } from '@/components/admin/publicite-statut-dialog';
import { StatusBadge } from '@/components/admin/status-badge';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';
import type { AgenceSummary, ClientSummary, Paginated } from '@/types';

type PubliciteRow = {
    id: string;
    titre: string;
    statut: string;
    statut_paiement: string;
    date_debut: string;
    date_fin: string;
    nombre_jours: number;
    motif_refus: string | null;
    image_url: string | null;
    agence: { id: string; nom: string } | null;
    client: { id: string; nom: string; prenom: string } | null;
    offre: { id: string; titre: string } | null;
};

type OffreOption = {
    id: string;
    titre: string;
    agence_id: string;
};

interface Props {
    publicites: Paginated<PubliciteRow>;
    filters: { search?: string; statut?: string };
    agences: AgenceSummary[];
    clients: ClientSummary[];
    offres: OffreOption[];
    statut_transitions: Record<string, string[]>;
}

function ownerLabel(row: PubliciteRow): string {
    if (row.agence) {
        return row.agence.nom;
    }

    if (row.client) {
        return `${row.client.prenom} ${row.client.nom}`;
    }

    return 'VERGA';
}

const columns: Column<PubliciteRow>[] = [
    { key: 'titre', label: 'Publicité', render: (r) => <span className="font-medium">{r.titre}</span> },
    { key: 'owner', label: 'Annonceur', render: (r) => ownerLabel(r) },
    { key: 'dates', label: 'Période', render: (r) => `${r.date_debut} → ${r.date_fin}` },
    { key: 'statut', label: 'Statut', render: (r) => <StatusBadge status={r.statut} /> },
    { key: 'paiement', label: 'Paiement', render: (r) => <StatusBadge status={r.statut_paiement} /> },
];

const filterOptions = [
    { label: 'En attente', value: 'en_attente' },
    { label: 'Validée', value: 'validée' },
    { label: 'Refusée', value: 'refusée' },
    { label: 'Publiée', value: 'publiée' },
    { label: 'Retirée', value: 'retirée' },
    { label: 'Expirée', value: 'expirée' },
];

export default function PublicitesIndex({
    publicites,
    filters,
    agences,
    clients,
    offres,
    statut_transitions,
}: Props) {
    const [createOpen, setCreateOpen] = useState(false);
    const [statutTarget, setStatutTarget] = useState<PubliciteRow | null>(null);

    const go = (params: Record<string, string | number | undefined>) =>
        router.get(admin.publicites.index().url, params as Record<string, string>, {
            preserveState: true,
            replace: true,
        });

    const hasTransitions = (row: PubliciteRow) => (statut_transitions[row.statut] ?? []).length > 0;

    return (
        <>
            <Head title="Publicités" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Publicités</h1>
                        <p className="text-sm text-muted-foreground">
                            Validez, refusez ou retirez une publicité à tout moment depuis le changement de statut.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href={admin.publicites.configuration().url}>
                                <Settings className="mr-2 h-4 w-4" />
                                Tarif
                            </Link>
                        </Button>
                        <Button onClick={() => setCreateOpen(true)}>
                            <PlusCircle className="mr-2 h-4 w-4" />
                            Nouvelle publicité
                        </Button>
                    </div>
                </div>

                <DataTable
                    columns={columns}
                    data={publicites.data}
                    pagination={publicites.meta}
                    initialSearch={filters.search ?? ''}
                    initialFilter={filters.statut ?? ''}
                    searchPlaceholder="Rechercher une publicité..."
                    filterKey="statut"
                    filterOptions={filterOptions}
                    emptyTitle="Aucune publicité"
                    emptyDescription="Créez une publicité interne ou attendez une demande d’agence / client."
                    onSearchChange={(v) => go({ ...filters, search: v || undefined, page: 1 })}
                    onFilterChange={(v) => go({ ...filters, statut: v || undefined, page: 1 })}
                    onPageChange={(p) => go({ ...filters, page: p })}
                    actions={(row) =>
                        hasTransitions(row) ? (
                            <Button variant="outline" size="sm" onClick={() => setStatutTarget(row)}>
                                <RefreshCw className="h-3.5 w-3.5" />
                            </Button>
                        ) : null
                    }
                />
            </div>

            <PubliciteFormDialog
                open={createOpen}
                onOpenChange={setCreateOpen}
                agences={agences}
                clients={clients}
                offres={offres}
            />

            <PubliciteStatutDialog
                open={Boolean(statutTarget)}
                onOpenChange={(open) => !open && setStatutTarget(null)}
                publicite={statutTarget}
                transitions={statut_transitions}
            />
        </>
    );
}

PublicitesIndex.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Publicités' },
    ],
};
