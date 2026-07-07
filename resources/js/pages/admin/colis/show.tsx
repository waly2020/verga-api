import { Head, Link } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    Box,
    CheckCircle2,
    Circle,
    Clock,
    Package,
    PackageCheck,
    Truck,
    User,
} from 'lucide-react';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { StatusBadge } from '@/components/admin/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import admin from '@/routes/admin';
import type { ColisDetail } from '@/types';

interface Props {
    colis: ColisDetail;
    next_statut: string | null;
}

// ─── Helpers ──────────────────────────────────────────────────────────────

const STATUT_LABELS: Record<string, string> = {
    'chez_client': 'Chez le client',
    'déposé':      'Déposé',
    'en_transit':  'En transit',
    'arrivé':      'Arrivé',
    'récupéré':    'Récupéré',
};

// Clé = statut CIBLE. Label = action à effectuer pour y arriver.
const NEXT_ACTION: Record<string, { label: string; confirm: string }> = {
    'déposé':     { label: 'Confirmer dépôt',    confirm: 'Confirmer que le colis a été remis à l\'agence ?' },
    'en_transit': { label: 'Expédier',           confirm: 'Confirmer l\'expédition de ce colis ?' },
    'arrivé':     { label: 'Confirmer arrivée',  confirm: 'Confirmer l\'arrivée à destination ?' },
    'récupéré':   { label: 'Marquer récupéré',   confirm: 'Marquer ce colis comme récupéré par le client ?' },
};

// Icône selon statut dans la timeline
const STATUT_ICON: Record<string, React.ElementType> = {
    'chez_client': User,
    'déposé':      Box,
    'en_transit':  Truck,
    'arrivé':      PackageCheck,
    'récupéré':    CheckCircle2,
};

// Couleur de la pastille timeline
const STATUT_COLOR: Record<string, string> = {
    'chez_client': 'bg-violet-500',
    'déposé':      'bg-slate-400',
    'en_transit':  'bg-blue-500',
    'arrivé':      'bg-amber-500',
    'récupéré':    'bg-emerald-500',
};

// Étapes visuelles de la barre de progression
const STEPS = [
    { key: 'chez_client', label: 'Chez le client', Icon: User },
    { key: 'déposé',      label: 'Déposé',         Icon: Box },
    { key: 'en_transit',  label: 'En transit',     Icon: Truck },
    { key: 'arrivé',      label: 'Arrivé',         Icon: PackageCheck },
    { key: 'récupéré',    label: 'Récupéré',       Icon: CheckCircle2 },
];

// ─── Composant ────────────────────────────────────────────────────────────

export default function ColisShow({ colis, next_statut }: Props) {
    const stepIndex = STEPS.findIndex((s) => s.key === colis.statut);

    const avancerStatut = () =>
        router.patch(admin.colis.statut(colis.id).url, {}, { preserveState: false });

    const nextAction = next_statut ? NEXT_ACTION[next_statut] : null;

    return (
        <>
            <Head title={`Colis — ${colis.reference}`} />
            <div className="flex flex-1 flex-col gap-6 p-6">

                {/* Retour + en-tête */}
                <div>
                    <Button variant="ghost" size="sm" asChild className="-ml-2 mb-2">
                        <Link href={admin.colis.index().url}>
                            <ArrowLeft className="mr-1.5 h-4 w-4" />
                            Retour aux colis
                        </Link>
                    </Button>

                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div className="flex items-center gap-3">
                            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10">
                                <Package className="h-6 w-6 text-primary" />
                            </div>
                            <div>
                                <h1 className="font-mono text-2xl font-semibold tracking-tight">{colis.reference}</h1>
                                <div className="mt-1 flex items-center gap-2">
                                    <StatusBadge status={colis.statut} />
                                    <span className="text-xs text-muted-foreground">
                                        Créé le {new Date(colis.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {nextAction && nextAction.label && (
                            <ConfirmDialog
                                trigger={
                                    <Button>
                                        <ArrowRight className="mr-1.5 h-4 w-4" />
                                        {nextAction.label}
                                    </Button>
                                }
                                title={nextAction.confirm}
                                description={`Colis ${colis.reference} — le statut passera à "${STATUT_LABELS[next_statut!]}" et un historique sera enregistré.`}
                                confirmLabel={nextAction.label}
                                onConfirm={avancerStatut}
                            />
                        )}
                    </div>
                </div>

                {/* Barre de progression */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-start justify-between">
                            {STEPS.map((step, i) => {
                                const done  = i <= stepIndex;
                                const Icon  = step.Icon;

                                return (
                                    <div key={step.key} className="flex flex-1 flex-col items-center">
                                        <div className="flex w-full items-center">
                                            {i > 0 && (
                                                <div className={`h-0.5 flex-1 ${i <= stepIndex ? 'bg-primary' : 'bg-muted'}`} />
                                            )}
                                            <div className={`flex h-9 w-9 items-center justify-center rounded-full border-2 transition-colors ${
                                                done
                                                    ? 'border-primary bg-primary text-primary-foreground'
                                                    : 'border-muted bg-background text-muted-foreground'
                                            }`}>
                                                <Icon className="h-4 w-4" />
                                            </div>
                                            {i < STEPS.length - 1 && (
                                                <div className={`h-0.5 flex-1 ${i < stepIndex ? 'bg-primary' : 'bg-muted'}`} />
                                            )}
                                        </div>
                                        <span className={`mt-2 text-center text-xs font-medium ${done ? 'text-primary' : 'text-muted-foreground'}`}>
                                            {step.label}
                                        </span>
                                    </div>
                                );
                            })}
                        </div>
                    </CardContent>
                </Card>

                {/* Informations + commande */}
                <div className="grid gap-4 lg:grid-cols-2">
                    {/* Détails colis */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-semibold">Informations colis</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <Row label="Référence">
                                <span className="font-mono text-sm font-medium">{colis.reference}</span>
                            </Row>
                            {colis.description && <Row label="Description">{colis.description}</Row>}
                            {colis.poids && <Row label="Poids">{colis.poids} kg</Row>}
                            {colis.volume && <Row label="Volume">{colis.volume} m³</Row>}
                            <Row label="Agence">
                                {colis.agence ? (
                                    <span className="font-medium">{colis.agence.nom}</span>
                                ) : '—'}
                            </Row>
                            <Row label="Dernière mise à jour">
                                {new Date(colis.updated_at).toLocaleDateString('fr-FR', {
                                    day: 'numeric', month: 'long', year: 'numeric',
                                    hour: '2-digit', minute: '2-digit',
                                })}
                            </Row>
                        </CardContent>
                    </Card>

                    {/* Commande liée */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-semibold">Commande liée</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {colis.commande ? (
                                <div className="space-y-3">
                                    <Row label="Code commande">
                                        <Button variant="link" size="sm" asChild className="h-auto p-0 font-mono text-sm font-medium">
                                            <Link href={admin.commandes.show(colis.commande.id).url}>
                                                {colis.commande.code}
                                            </Link>
                                        </Button>
                                    </Row>
                                    <Row label="Montant">
                                        <span className="font-semibold tabular-nums">
                                            {Number(colis.commande.montant_total).toLocaleString('fr-FR')} FCFA
                                        </span>
                                    </Row>
                                    <Row label="Statut commande">
                                        <StatusBadge status={colis.commande.statut} />
                                    </Row>
                                    {colis.commande.client ? (
                                        <Row label="Client">
                                            <div className="flex items-center gap-1.5">
                                                <User className="h-3.5 w-3.5 text-muted-foreground" />
                                                <span>{colis.commande.client.prenom} {colis.commande.client.nom}</span>
                                            </div>
                                        </Row>
                                    ) : colis.commande.prenom && colis.commande.nom ? (
                                        <Row label="Client">
                                            <div className="flex items-center gap-1.5">
                                                <User className="h-3.5 w-3.5 text-muted-foreground" />
                                                <span>{colis.commande.prenom} {colis.commande.nom} (invité)</span>
                                            </div>
                                        </Row>
                                    ) : null}
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">Aucune commande associée.</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {colis.photos && colis.photos.length > 0 && (
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-semibold">
                                Photos du colis
                                <Badge variant="secondary" className="ml-2">{colis.photos.length}</Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                {colis.photos.map((photo) => (
                                    <a
                                        key={photo.id}
                                        href={`/storage/${photo.chemin}`}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="overflow-hidden rounded-lg border bg-muted/30"
                                    >
                                        <img
                                            src={`/storage/${photo.chemin}`}
                                            alt={`Photo colis ${colis.reference}`}
                                            className="aspect-video w-full object-cover"
                                        />
                                    </a>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Historique des statuts */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="flex items-center gap-2 text-sm font-semibold">
                            Historique des statuts
                            <Badge variant="secondary">{colis.historique.length}</Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {colis.historique.length === 0 ? (
                            <div className="flex flex-col items-center gap-2 py-8 text-center text-muted-foreground">
                                <Clock className="h-8 w-8 opacity-30" />
                                <p className="text-sm">Aucun mouvement enregistré.</p>
                                <p className="text-xs">Le premier changement de statut apparaîtra ici.</p>
                            </div>
                        ) : (
                            <ol className="relative border-l border-muted pl-6 space-y-6">
                                {colis.historique.map((h, i) => {
                                    const Icon  = STATUT_ICON[h.statut] ?? Circle;
                                    const color = STATUT_COLOR[h.statut] ?? 'bg-slate-400';

                                    return (
                                        <li key={h.id} className="relative">
                                            {/* pastille */}
                                            <div className={`absolute -left-[1.85rem] flex h-5 w-5 items-center justify-center rounded-full ${color}`}>
                                                <Icon className="h-2.5 w-2.5 text-white" />
                                            </div>

                                            <div className="flex flex-col gap-0.5">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <StatusBadge status={h.statut} />
                                                    {i === 0 && (
                                                        <Badge variant="outline" className="text-xs">Dernier</Badge>
                                                    )}
                                                </div>

                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    {new Date(h.created_at).toLocaleDateString('fr-FR', {
                                                        day: 'numeric', month: 'long', year: 'numeric',
                                                        hour: '2-digit', minute: '2-digit',
                                                    })}
                                                    {h.user && (
                                                        <span className="ml-2">— par <strong className="font-medium text-foreground">{h.user.name}</strong></span>
                                                    )}
                                                </p>

                                                {h.commentaire && (
                                                    <p className="mt-1 text-sm text-muted-foreground italic">
                                                        "{h.commentaire}"
                                                    </p>
                                                )}
                                            </div>
                                        </li>
                                    );
                                })}
                            </ol>
                        )}
                    </CardContent>
                </Card>

            </div>
        </>
    );
}

ColisShow.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Colis', href: admin.colis.index().url },
        { title: 'Fiche colis' },
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
