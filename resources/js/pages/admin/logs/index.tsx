import { Head, router } from '@inertiajs/react';
import { Download, ScrollText } from 'lucide-react';
import { useState } from 'react';
import { DataTable } from '@/components/admin/data-table';
import type { Column } from '@/components/admin/data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { paginationMeta } from '@/lib/pagination';
import admin from '@/routes/admin';
import type { Paginated } from '@/types';

type Actor = {
    type?: string | null;
    id?: number | string | null;
    name?: string | null;
    email?: string | null;
    role?: string | null;
};

type AuditEntry = Record<string, unknown> & {
    id: string;
    at: string;
    action: string;
    label?: string;
    level: string;
    actor?: Actor | null;
    ip?: string | null;
    context?: Record<string, unknown>;
};

type ActionOption = {
    value: string;
    label: string;
    category: string;
};

interface Props {
    entries: Paginated<AuditEntry>;
    filters: { date: string; action?: string | null; search?: string | null };
    jours: string[];
    actions: ActionOption[];
}

const levelClass: Record<string, string> = {
    critical: 'bg-red-100 text-red-800 border-red-200 hover:bg-red-100',
    warning: 'bg-amber-100 text-amber-800 border-amber-200 hover:bg-amber-100',
    info: 'bg-blue-100 text-blue-800 border-blue-200 hover:bg-blue-100',
};

function formatWhen(value: string): string {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString('fr-FR', {
        dateStyle: 'short',
        timeStyle: 'medium',
    });
}

function actorLabel(actor?: Actor | null): string {
    if (!actor) {
        return 'Système';
    }

    const name = actor.name || actor.email || 'Inconnu';
    const type = actor.type ? ` (${actor.type})` : '';

    return `${name}${type}`;
}

const columns: Column<AuditEntry>[] = [
    {
        key: 'at',
        label: 'Heure',
        render: (row) => <span className="tabular-nums text-muted-foreground">{formatWhen(row.at)}</span>,
    },
    {
        key: 'action',
        label: 'Action',
        render: (row) => <span className="font-medium">{row.label ?? row.action}</span>,
    },
    {
        key: 'level',
        label: 'Niveau',
        render: (row) => (
            <Badge variant="outline" className={levelClass[row.level] ?? ''}>
                {row.level}
            </Badge>
        ),
    },
    {
        key: 'actor',
        label: 'Utilisateur',
        render: (row) => <span>{actorLabel(row.actor)}</span>,
    },
    {
        key: 'ip',
        label: 'IP',
        render: (row) => <span className="font-mono text-xs text-muted-foreground">{row.ip ?? '—'}</span>,
    },
];

export default function AuditLogsIndex({ entries, filters, jours, actions }: Props) {
    const [selected, setSelected] = useState<AuditEntry | null>(null);

    const go = (params: Record<string, string | number | undefined>) =>
        router.get(admin.logs.index().url, params as Record<string, string>, {
            preserveState: true,
            replace: true,
        });

    const canDownload = jours.includes(filters.date);

    return (
        <>
            <Head title="Journal d'audit" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Journal d'audit</h1>
                        <p className="text-sm text-muted-foreground">
                            Consultation seule — un fichier JSON par jour, aucune suppression depuis
                            l'interface.
                        </p>
                    </div>
                    {canDownload ? (
                        <Button asChild variant="outline">
                            <a href={admin.logs.download.url(filters.date)}>
                                <Download className="mr-2 h-4 w-4" />
                                Télécharger {filters.date}
                            </a>
                        </Button>
                    ) : null}
                </div>

                <div className="flex flex-wrap items-end gap-3">
                    <div className="grid gap-1">
                        <label className="text-xs font-medium text-muted-foreground" htmlFor="audit-date">
                            Jour
                        </label>
                        <Input
                            id="audit-date"
                            type="date"
                            value={filters.date}
                            onChange={(event) =>
                                go({
                                    ...filters,
                                    date: event.target.value || undefined,
                                    page: 1,
                                })
                            }
                            className="w-44"
                        />
                    </div>
                </div>

                <DataTable
                    columns={columns}
                    data={entries.data}
                    pagination={paginationMeta(entries)}
                    initialSearch={filters.search ?? ''}
                    initialFilter={filters.action ?? ''}
                    searchPlaceholder="Rechercher un utilisateur, une action, un montant..."
                    filterKey="action"
                    filterOptions={actions.map((action) => ({
                        label: action.label,
                        value: action.value,
                    }))}
                    emptyTitle="Aucune entrée"
                    emptyDescription="Aucun événement d'audit pour ce jour ou ce filtre."
                    onSearchChange={(value) => go({ ...filters, search: value || undefined, page: 1 })}
                    onFilterChange={(value) => go({ ...filters, action: value || undefined, page: 1 })}
                    onPageChange={(page) => go({ ...filters, page })}
                    actions={(row) => (
                        <Button size="sm" variant="outline" onClick={() => setSelected(row)}>
                            <ScrollText className="mr-1 h-3.5 w-3.5" />
                            Détail
                        </Button>
                    )}
                />
            </div>

            <Dialog open={selected !== null} onOpenChange={(open) => !open && setSelected(null)}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>{selected?.label ?? selected?.action}</DialogTitle>
                        <DialogDescription>
                            {selected ? formatWhen(selected.at) : ''} — lecture seule
                        </DialogDescription>
                    </DialogHeader>
                    <pre className="max-h-[60vh] overflow-auto rounded-md bg-muted p-4 text-xs leading-relaxed">
                        {selected ? JSON.stringify(selected, null, 2) : ''}
                    </pre>
                </DialogContent>
            </Dialog>
        </>
    );
}

AuditLogsIndex.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Journal d’audit', href: admin.logs.index().url },
    ],
};
