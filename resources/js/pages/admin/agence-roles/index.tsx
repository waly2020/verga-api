import { Head, router } from '@inertiajs/react';
import { Pencil, PlusCircle, Shield, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { AgenceRoleFormDialog } from '@/components/admin/agence-role-form-dialog';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { DataTable } from '@/components/admin/data-table';
import type { Column } from '@/components/admin/data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';
import type { AgenceRoleRow, Paginated } from '@/types';

interface Props {
    roles: Paginated<AgenceRoleRow>;
    filters: { search?: string; actif?: string };
}

const columns: Column<AgenceRoleRow>[] = [
    {
        key: 'nom',
        label: 'Rôle',
        render: (r) => (
            <div>
                <span className="font-medium">{r.nom}</span>
                <p className="text-xs text-muted-foreground">{r.slug}</p>
            </div>
        ),
    },
    {
        key: 'description',
        label: 'Description',
        render: (r) => (
            <span className="line-clamp-2 text-sm text-muted-foreground">
                {r.description ?? '—'}
            </span>
        ),
    },
    {
        key: 'users_count',
        label: 'Utilisateurs',
        render: (r) => <span className="tabular-nums">{r.users_count}</span>,
    },
    {
        key: 'actif',
        label: 'Statut',
        render: (r) => (
            <div className="flex flex-wrap gap-1">
                <Badge variant={r.actif ? 'default' : 'secondary'}>
                    {r.actif ? 'Actif' : 'Inactif'}
                </Badge>
                {r.est_systeme && <Badge variant="outline">Système</Badge>}
            </div>
        ),
    },
];

const filterOptions = [
    { label: 'Actifs', value: '1' },
    { label: 'Inactifs', value: '0' },
];

export default function AgenceRolesIndex({ roles, filters }: Props) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<AgenceRoleRow | null>(null);

    const openCreate = () => {
        setEditing(null);
        setDialogOpen(true);
    };

    const openEdit = (row: AgenceRoleRow) => {
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
        router.get(admin.agenceRoles.index().url, params as Record<string, string>, {
            preserveState: true,
            replace: true,
        });

    const supprimer = (row: AgenceRoleRow) =>
        router.delete(admin.agenceRoles.destroy(row.id).url, { preserveState: false });

    return (
        <>
            <Head title="Rôles agence" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Rôles agence</h1>
                        <p className="text-sm text-muted-foreground">
                            Définissez les rôles assignables aux utilisateurs des agences partenaires.
                        </p>
                    </div>
                    <Button onClick={openCreate}>
                        <PlusCircle className="mr-2 h-4 w-4" />
                        Nouveau rôle
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    data={roles.data}
                    pagination={roles.meta}
                    initialSearch={filters.search ?? ''}
                    initialFilter={filters.actif ?? ''}
                    searchPlaceholder="Rechercher un rôle..."
                    filterKey="actif"
                    filterOptions={filterOptions}
                    emptyTitle="Aucun rôle agence"
                    emptyDescription="Créez un premier rôle pour les équipes des agences."
                    onSearchChange={(v) => go({ ...filters, search: v || undefined, page: 1 })}
                    onFilterChange={(v) => go({ ...filters, actif: v || undefined, page: 1 })}
                    onPageChange={(p) => go({ ...filters, page: p })}
                    actions={(row) => (
                        <div className="flex items-center gap-1">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => openEdit(row)}
                                disabled={row.est_systeme}
                            >
                                <Pencil className="h-3.5 w-3.5" />
                            </Button>
                            <ConfirmDialog
                                trigger={
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="text-destructive hover:text-destructive"
                                        disabled={row.est_systeme || row.users_count > 0}
                                    >
                                        <Trash2 className="h-3.5 w-3.5" />
                                    </Button>
                                }
                                title="Supprimer ce rôle ?"
                                description={
                                    row.est_systeme
                                        ? 'Le rôle système administrateur agence ne peut pas être supprimé.'
                                        : row.users_count > 0
                                          ? 'Ce rôle est affecté à des utilisateurs et ne peut pas être supprimé.'
                                          : `Le rôle « ${row.nom} » sera définitivement supprimé.`
                                }
                                confirmLabel="Supprimer"
                                onConfirm={() => supprimer(row)}
                            />
                        </div>
                    )}
                />
            </div>

            <AgenceRoleFormDialog
                key={editing?.id ?? 'create'}
                open={dialogOpen}
                onOpenChange={handleDialogOpenChange}
                agenceRole={editing}
            />
        </>
    );
}

AgenceRolesIndex.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Rôles agence', href: admin.agenceRoles.index().url },
    ],
};
