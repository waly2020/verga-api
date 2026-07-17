import { Head, router } from '@inertiajs/react';
import { Pencil, PlusCircle, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { AgenceUserFormDialog } from '@/components/admin/agence-user-form-dialog';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { DataTable } from '@/components/admin/data-table';
import type { Column } from '@/components/admin/data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';
import type {
    AgenceRoleApi,
    AgenceSummary,
    AgenceUserRow,
    Paginated,
} from '@/types';

interface Props {
    users: Paginated<AgenceUserRow>;
    agences: AgenceSummary[];
    roles: AgenceRoleApi[];
    filters: { search?: string; statut?: string };
}

const columns: Column<AgenceUserRow>[] = [
    {
        key: 'name',
        label: 'Utilisateur',
        render: (user) => (
            <div>
                <p className="font-medium">{user.name}</p>
                <p className="text-xs text-muted-foreground">{user.email}</p>
            </div>
        ),
    },
    {
        key: 'agence',
        label: 'Agence',
        render: (user) => user.agence.nom,
    },
    {
        key: 'role',
        label: 'Rôle',
        render: (user) => (
            <div className="flex flex-wrap items-center gap-1">
                <Badge variant="outline">{user.role.nom}</Badge>
                {user.est_proprietaire && <Badge>Propriétaire</Badge>}
            </div>
        ),
    },
    {
        key: 'telephone',
        label: 'Téléphone',
        render: (user) => user.telephone ?? '—',
    },
    {
        key: 'statut',
        label: 'Statut',
        render: (user) => (
            <Badge variant={user.statut === 'actif' ? 'default' : 'secondary'}>
                {user.statut === 'actif' ? 'Actif' : 'Suspendu'}
            </Badge>
        ),
    },
];

const filterOptions = [
    { label: 'Actifs', value: 'actif' },
    { label: 'Suspendus', value: 'suspendu' },
];

export default function AgenceUsersIndex({ users, agences, roles, filters }: Props) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<AgenceUserRow | null>(null);

    const openCreate = () => {
        setEditing(null);
        setDialogOpen(true);
    };

    const openEdit = (user: AgenceUserRow) => {
        setEditing(user);
        setDialogOpen(true);
    };

    const go = (params: Record<string, string | number | undefined>) =>
        router.get(admin.agenceUsers.index().url, params as Record<string, string>, {
            preserveState: true,
            replace: true,
        });

    const supprimer = (user: AgenceUserRow) =>
        router.delete(admin.agenceUsers.destroy(user.id).url, {
            preserveState: false,
        });

    return (
        <>
            <Head title="Utilisateurs agence" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Utilisateurs agence
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Gérez les collaborateurs rattachés aux agences et leurs rôles.
                        </p>
                    </div>
                    <Button onClick={openCreate} disabled={agences.length === 0 || roles.length === 0}>
                        <PlusCircle className="mr-2 h-4 w-4" />
                        Nouvel utilisateur
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    data={users.data}
                    pagination={users.meta}
                    initialSearch={filters.search ?? ''}
                    initialFilter={filters.statut ?? ''}
                    searchPlaceholder="Nom, email, agence ou rôle..."
                    filterKey="statut"
                    filterOptions={filterOptions}
                    emptyTitle="Aucun utilisateur agence"
                    emptyDescription="Créez un collaborateur pour une agence partenaire."
                    onSearchChange={(value) =>
                        go({ ...filters, search: value || undefined, page: 1 })
                    }
                    onFilterChange={(value) =>
                        go({ ...filters, statut: value || undefined, page: 1 })
                    }
                    onPageChange={(page) => go({ ...filters, page })}
                    actions={(user) => (
                        <div className="flex justify-end gap-1">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => openEdit(user)}
                                disabled={user.est_proprietaire}
                            >
                                <Pencil className="h-3.5 w-3.5" />
                            </Button>
                            <ConfirmDialog
                                trigger={
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="text-destructive hover:text-destructive"
                                        disabled={user.est_proprietaire}
                                    >
                                        <Trash2 className="h-3.5 w-3.5" />
                                    </Button>
                                }
                                title="Supprimer cet utilisateur ?"
                                description={`Le compte de « ${user.name} » sera définitivement supprimé.`}
                                confirmLabel="Supprimer"
                                onConfirm={() => supprimer(user)}
                            />
                        </div>
                    )}
                />
            </div>

            <AgenceUserFormDialog
                key={editing?.id ?? 'create'}
                open={dialogOpen}
                onOpenChange={(open) => {
                    setDialogOpen(open);
                    if (!open) {
                        setEditing(null);
                    }
                }}
                user={editing}
                agences={agences}
                roles={roles}
            />
        </>
    );
}

AgenceUsersIndex.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Agences', href: admin.agences.index().url },
        { title: 'Utilisateurs agence', href: admin.agenceUsers.index().url },
    ],
};
