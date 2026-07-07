import { Head, Link } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { Ban, Eye, PlusCircle, Tag, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { CreateAgenceDialog } from '@/components/admin/create-agence-dialog';
import { CreateTypeAgenceDialog } from '@/components/admin/create-type-agence-dialog';
import { DataTable  } from '@/components/admin/data-table';
import type {Column} from '@/components/admin/data-table';
import { ExportButtons } from '@/components/admin/export-buttons';
import { StatusBadge } from '@/components/admin/status-badge';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';
import { paginationMeta } from '@/lib/pagination';
import type { Paginated } from '@/types';

type AgenceRow = Record<string, unknown> & {
    id: string;
    nom: string;
    email: string;
    telephone: string | null;
    ville: string | null;
    statut: string;
    offres_count: number;
};

type TypeAgence = { id: string; nom: string };

interface Props {
    agences: Paginated<AgenceRow>;
    filters: { search?: string; statut?: string };
    types_agences: TypeAgence[];
}

const columns: Column<AgenceRow>[] = [
    { key: 'nom',          label: 'Agence',     render: (r) => <span className="font-medium">{r.nom}</span> },
    { key: 'email',        label: 'Email' },
    { key: 'telephone',    label: 'Téléphone' },
    { key: 'ville',        label: 'Ville' },
    { key: 'offres_count', label: 'Offres',     render: (r) => <span className="tabular-nums">{r.offres_count}</span> },
    { key: 'statut',       label: 'Statut',     render: (r) => <StatusBadge status={r.statut} /> },
];

const filterOptions = [
    { label: 'Actif',    value: 'actif' },
    { label: 'Bloqué',   value: 'bloqué' },
    { label: 'Suspendu', value: 'suspendu' },
];

export default function AgencesIndex({ agences, filters, types_agences }: Props) {
    const [createOpen, setCreateOpen] = useState(false);
    const [createTypeOpen, setCreateTypeOpen] = useState(false);

    const go = (params: Record<string, string | number | undefined>) =>
        router.get(admin.agences.index().url, params as Record<string, string>, {
            preserveState: true,
            replace: true,
        });

    const toggleStatut = (row: AgenceRow) =>
        router.patch(admin.agences.statut(row.id).url, {}, { preserveState: false });

    const supprimer = (row: AgenceRow) =>
        router.delete(admin.agences.destroy(row.id).url);

    return (
        <>
            <Head title="Agences" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Agences</h1>
                        <p className="text-sm text-muted-foreground">Gérez les agences de transit partenaires</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <ExportButtons module="agences" />
                        <Button variant="outline" onClick={() => setCreateTypeOpen(true)}>
                            <Tag className="mr-2 h-4 w-4" />
                            Type d'agence
                        </Button>
                        <Button onClick={() => setCreateOpen(true)}>
                            <PlusCircle className="mr-2 h-4 w-4" />
                            Créer une agence
                        </Button>
                    </div>
                </div>

                <DataTable
                    columns={columns}
                    data={agences.data}
                    pagination={paginationMeta(agences)}
                    initialSearch={filters.search ?? ''}
                    initialFilter={filters.statut ?? ''}
                    searchPlaceholder="Rechercher par nom, email ou ville..."
                    filterKey="statut"
                    filterOptions={filterOptions}
                    emptyTitle="Aucune agence"
                    emptyDescription="Aucune agence n'a encore été enregistrée sur la plateforme."
                    onSearchChange={(v) => go({ ...filters, search: v || undefined, page: 1 })}
                    onFilterChange={(v) => go({ ...filters, statut: v || undefined, page: 1 })}
                    onPageChange={(p) => go({ ...filters, page: p })}
                    actions={(row) => (
                        <div className="flex items-center justify-end gap-2">
                            <Button variant="outline" size="sm" asChild>
                                <Link href={admin.agences.show(row.id).url}>
                                    <Eye className="mr-1 h-3.5 w-3.5" />
                                    Voir
                                </Link>
                            </Button>

                            <ConfirmDialog
                                trigger={
                                    <Button variant="outline" size="sm">
                                        <Ban className="mr-1 h-3.5 w-3.5" />
                                        {row.statut === 'bloqué' ? 'Débloquer' : 'Bloquer'}
                                    </Button>
                                }
                                title={row.statut === 'bloqué' ? 'Débloquer cette agence ?' : 'Bloquer cette agence ?'}
                                description={
                                    row.statut === 'bloqué'
                                        ? `L'agence "${row.nom}" retrouvera l'accès à la plateforme.`
                                        : `L'agence "${row.nom}" ne pourra plus accéder à la plateforme.`
                                }
                                confirmLabel={row.statut === 'bloqué' ? 'Débloquer' : 'Bloquer'}
                                variant={row.statut === 'bloqué' ? 'default' : 'destructive'}
                                onConfirm={() => toggleStatut(row)}
                            />

                            <ConfirmDialog
                                trigger={
                                    <Button variant="outline" size="sm" className="text-destructive hover:text-destructive">
                                        <Trash2 className="h-3.5 w-3.5" />
                                    </Button>
                                }
                                title="Supprimer cette agence ?"
                                description={`Cette action est irréversible. L'agence "${row.nom}" et toutes ses données seront supprimées.`}
                                confirmLabel="Supprimer"
                                onConfirm={() => supprimer(row)}
                            />
                        </div>
                    )}
                />
            </div>

            <CreateAgenceDialog
                open={createOpen}
                onOpenChange={setCreateOpen}
                typesAgences={types_agences}
            />
            <CreateTypeAgenceDialog
                open={createTypeOpen}
                onOpenChange={setCreateTypeOpen}
            />
        </>
    );
}

AgencesIndex.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Agences', href: admin.agences.index().url },
    ],
};
