import { Head, Link } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import {
    ArrowLeft,
    Building2,
    CheckCircle2,
    Mail,
    MessageSquareWarning,
    Phone,
    ShoppingCart,
    User,
    XCircle,
} from 'lucide-react';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { StatusBadge } from '@/components/admin/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import admin from '@/routes/admin';

// ─── Types ────────────────────────────────────────────────────────────────

type Reclamation = {
    id: string;
    nom: string;
    prenom: string | null;
    telephone: string | null;
    email: string | null;
    objet: string;
    description: string;
    statut: string;
    created_at: string;
    updated_at: string;
    agence: { id: string; nom: string; email: string; ville: string | null } | null;
    commande: { id: string; code: string; montant_total: string; statut: string } | null;
};

interface Props {
    reclamation: Reclamation;
    transitions: string[];
}

// ─── Mapping des actions disponibles ──────────────────────────────────────

const TRANSITION_META: Record<string, {
    label: string;
    confirm: string;
    description: (client: string) => string;
    variant: 'default' | 'destructive';
    icon: React.ElementType;
    className: string;
}> = {
    'en_cours': {
        label: 'Prendre en charge',
        confirm: 'Prendre en charge cette réclamation ?',
        description: (c) => `La réclamation de "${c}" passera en statut "En cours".`,
        variant: 'default',
        icon: MessageSquareWarning,
        className: '',
    },
    'résolue': {
        label: 'Marquer résolue',
        confirm: 'Marquer cette réclamation comme résolue ?',
        description: (c) => `La réclamation de "${c}" sera clôturée comme résolue.`,
        variant: 'default',
        icon: CheckCircle2,
        className: 'bg-emerald-600 hover:bg-emerald-700',
    },
    'fermée': {
        label: 'Fermer',
        confirm: 'Fermer cette réclamation ?',
        description: (c) => `La réclamation de "${c}" sera fermée sans résolution.`,
        variant: 'destructive',
        icon: XCircle,
        className: '',
    },
};

// ─── Composant ────────────────────────────────────────────────────────────

export default function ReclamationShow({ reclamation, transitions }: Props) {
    const clientName = reclamation.prenom
        ? `${reclamation.prenom} ${reclamation.nom}`
        : reclamation.nom;

    const changeStatut = (statut: string) =>
        router.patch(admin.reclamations.statut(reclamation.id).url, { statut }, { preserveState: false });

    const isFinal = transitions.length === 0;

    return (
        <>
            <Head title={`Réclamation — ${reclamation.objet}`} />
            <div className="flex flex-1 flex-col gap-6 p-6">

                {/* Retour + en-tête */}
                <div>
                    <Button variant="ghost" size="sm" asChild className="-ml-2 mb-2">
                        <Link href={admin.reclamations.index().url}>
                            <ArrowLeft className="mr-1.5 h-4 w-4" />
                            Retour aux réclamations
                        </Link>
                    </Button>

                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div className="flex items-start gap-3">
                            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-destructive/10">
                                <MessageSquareWarning className="h-6 w-6 text-destructive" />
                            </div>
                            <div>
                                <h1 className="text-xl font-semibold leading-tight tracking-tight">{reclamation.objet}</h1>
                                <div className="mt-1 flex flex-wrap items-center gap-2">
                                    <StatusBadge status={reclamation.statut} />
                                    <span className="text-xs text-muted-foreground">
                                        Soumise le {new Date(reclamation.created_at).toLocaleDateString('fr-FR', {
                                            day: 'numeric', month: 'long', year: 'numeric',
                                        })}
                                    </span>
                                    {reclamation.updated_at !== reclamation.created_at && (
                                        <span className="text-xs text-muted-foreground">
                                            · Mise à jour le {new Date(reclamation.updated_at).toLocaleDateString('fr-FR')}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Actions de statut */}
                        {!isFinal && (
                            <div className="flex shrink-0 flex-wrap items-center gap-2">
                                {transitions.map((statut) => {
                                    const meta = TRANSITION_META[statut];
                                    if (!meta) return null;
                                    const Icon = meta.icon;
                                    return (
                                        <ConfirmDialog
                                            key={statut}
                                            trigger={
                                                <Button variant={meta.variant} className={meta.className}>
                                                    <Icon className="mr-1.5 h-4 w-4" />
                                                    {meta.label}
                                                </Button>
                                            }
                                            title={meta.confirm}
                                            description={meta.description(clientName)}
                                            confirmLabel={meta.label}
                                            variant={meta.variant}
                                            onConfirm={() => changeStatut(statut)}
                                        />
                                    );
                                })}
                            </div>
                        )}

                        {isFinal && (
                            <div className="flex items-center gap-2 rounded-lg border border-border bg-muted/40 px-4 py-2 text-sm text-muted-foreground">
                                {reclamation.statut === 'résolue'
                                    ? <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                                    : <XCircle className="h-4 w-4 text-slate-400" />
                                }
                                Réclamation {reclamation.statut}
                            </div>
                        )}
                    </div>
                </div>

                {/* Description */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-sm font-semibold">Description du problème</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="whitespace-pre-wrap text-sm leading-relaxed text-foreground">
                            {reclamation.description}
                        </p>
                    </CardContent>
                </Card>

                {/* Client + Agence + Commande */}
                <div className="grid gap-4 lg:grid-cols-3">
                    {/* Client */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-1.5 text-sm font-semibold">
                                <User className="h-4 w-4 text-muted-foreground" />
                                Client
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <p className="font-medium">{clientName}</p>
                            {reclamation.telephone && (
                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <Phone className="h-3.5 w-3.5 shrink-0" />
                                    {reclamation.telephone}
                                </div>
                            )}
                            {reclamation.email && (
                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <Mail className="h-3.5 w-3.5 shrink-0" />
                                    {reclamation.email}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Agence */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-1.5 text-sm font-semibold">
                                <Building2 className="h-4 w-4 text-muted-foreground" />
                                Agence concernée
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {reclamation.agence ? (
                                <div className="space-y-3">
                                    <p className="font-medium">{reclamation.agence.nom}</p>
                                    {reclamation.agence.ville && (
                                        <p className="text-sm text-muted-foreground">{reclamation.agence.ville}</p>
                                    )}
                                    {reclamation.agence.email && (
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <Mail className="h-3.5 w-3.5 shrink-0" />
                                            {reclamation.agence.email}
                                        </div>
                                    )}
                                    <Button variant="outline" size="sm" asChild className="mt-1 w-full">
                                        <Link href={admin.agences.show(reclamation.agence.id).url}>
                                            Voir l'agence
                                        </Link>
                                    </Button>
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">Aucune agence associée.</p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Commande liée */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-1.5 text-sm font-semibold">
                                <ShoppingCart className="h-4 w-4 text-muted-foreground" />
                                Commande liée
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {reclamation.commande ? (
                                <div className="space-y-3">
                                    <Row label="Code">
                                        <span className="font-mono text-sm font-medium">{reclamation.commande.code}</span>
                                    </Row>
                                    <Row label="Montant">
                                        <span className="font-semibold tabular-nums">
                                            {Number(reclamation.commande.montant_total).toLocaleString('fr-FR')} FCFA
                                        </span>
                                    </Row>
                                    <Row label="Statut">
                                        <StatusBadge status={reclamation.commande.statut} />
                                    </Row>
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">Aucune commande associée.</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

            </div>
        </>
    );
}

ReclamationShow.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Réclamations', href: admin.reclamations.index().url },
        { title: 'Détail réclamation' },
    ],
};

// ─── Sub-composant Row ────────────────────────────────────────────────────

function Row({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="flex items-center justify-between text-sm">
            <span className="text-muted-foreground">{label}</span>
            <span>{children}</span>
        </div>
    );
}
