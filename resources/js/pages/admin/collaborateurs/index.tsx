import { Head, Link } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { PlusCircle, Trash2 } from 'lucide-react';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { DataTable, type Column } from '@/components/admin/data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';
import admin from '@/routes/admin';

const ROLE_META: Record<string, { label: string; variant: 'default' | 'secondary' | 'outline' }> = {
    admin:         { label: 'Administrateur', variant: 'default' },
    collaborateur: { label: 'Collaborateur',  variant: 'secondary' },
};

type CollaborateurRow = Record<string, unknown> & {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string;
};

interface Props {
    collaborateurs: Paginated<CollaborateurRow>;
    filters: { search?: string };
}

const columns: Column<CollaborateurRow>[] = [
    { key: 'name',  label: 'Nom',   render: (r) => <span className="font-medium">{r.name}</span> },
    { key: 'email', label: 'Email' },
    {
        key: 'role',
        label: 'Rôle',
        render: (r) => {
            const meta = ROLE_META[r.role] ?? { label: r.role, variant: 'outline' as const };
            return <Badge variant={meta.variant}>{meta.label}</Badge>;
        },
    },
    {
        key: 'created_at',
        label: 'Créé le',
        render: (r) => new Date(String(r.created_at)).toLocaleDateString('fr-FR'),
    },
];

export default function CollaborateursIndex({ collaborateurs, filters }: Props) {
    const go = (params: Record<string, string | number | undefined>) =>
        router.get(admin.collaborateurs.index().url, params as Record<string, string>, {
            preserveState: true,
            replace: true,
        });

    const supprimer = (row: CollaborateurRow) =>
        router.delete(admin.collaborateurs.destroy(row.id).url, { preserveState: false });

    return (
        <>
            <Head title="Collaborateurs" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Collaborateurs</h1>
                        <p className="text-sm text-muted-foreground">Gérez les comptes administrateurs et collaborateurs</p>
                    </div>
                    <Button asChild>
                        <Link href={admin.collaborateurs.create().url}>
                            <PlusCircle className="mr-2 h-4 w-4" />
                            Ajouter
                        </Link>
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    data={collaborateurs.data}
                    pagination={collaborateurs.meta}
                    initialSearch={filters.search ?? ''}
                    searchPlaceholder="Rechercher un collaborateur..."
                    emptyTitle="Aucun collaborateur"
                    emptyDescription="Aucun collaborateur n'a encore été ajouté."
                    onSearchChange={(v) => go({ ...filters, search: v || undefined, page: 1 })}
                    onPageChange={(p) => go({ ...filters, page: p })}
                    actions={(row) => (
                        <ConfirmDialog
                            trigger={
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="text-destructive hover:text-destructive"
                                    disabled={row.role === 'admin'}
                                >
                                    <Trash2 className="h-3.5 w-3.5" />
                                </Button>
                            }
                            title="Supprimer ce collaborateur ?"
                            description={`Le compte de "${row.name}" sera définitivement supprimé. Cette action est irréversible.`}
                            confirmLabel="Supprimer"
                            onConfirm={() => supprimer(row)}
                        />
                    )}
                />
            </div>
        </>
    );
}

CollaborateursIndex.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Collaborateurs', href: admin.collaborateurs.index().url },
    ],
};
