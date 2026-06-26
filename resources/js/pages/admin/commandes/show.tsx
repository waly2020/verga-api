import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Building2,
    CreditCard,
    Mail,
    MessageSquareWarning,
    Package,
    Percent,
    Phone,
    ShoppingCart,
    Tag,
    User,
} from 'lucide-react';
import { StatusBadge } from '@/components/admin/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import admin from '@/routes/admin';

// ─── Types ────────────────────────────────────────────────────────────────

type ClientInfo = { id: string; nom: string; prenom: string; email: string; telephone: string | null };
type AgenceInfo = { id: string; nom: string; email: string; ville: string | null };
type OffreInfo = {
    id: string;
    titre: string;
    type: string;
    prix: string;
    origine: string | null;
    destination: string | null;
    statut: string;
};
type PaiementInfo = {
    id: string;
    montant: string;
    methode: string;
    reference: string | null;
    statut: string;
    created_at: string;
};
type CommissionInfo = {
    id: string;
    montant: string;
    taux: string | null;
    created_at: string;
};
type ColisRow = {
    id: string;
    reference: string;
    statut: string;
    created_at: string;
};
type ReclamationRow = {
    id: string;
    objet: string;
    statut: string;
    created_at: string;
};
type CommandeDetail = {
    id: string;
    code: string;
    quantite: string;
    montant_total: string;
    statut: string;
    created_at: string;
    updated_at: string;
    client: ClientInfo | null;
    agence: AgenceInfo | null;
    offre: OffreInfo | null;
    paiement: PaiementInfo | null;
    commission: CommissionInfo | null;
    colis: ColisRow[];
    reclamations: ReclamationRow[];
};

interface Props {
    commande: CommandeDetail;
}

// ─── Helpers ──────────────────────────────────────────────────────────────

function fmtFcfa(value: string | number): string {
    return `${Number(value).toLocaleString('fr-FR')} FCFA`;
}

function fmtDate(value: string, withTime = false): string {
    return new Date(value).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}),
    });
}

// ─── Composant ────────────────────────────────────────────────────────────

export default function CommandeShow({ commande }: Props) {
    const montantTotal = Number(commande.montant_total);
    const commissionMontant = commande.commission ? Number(commande.commission.montant) : 0;
    const montantAgence = montantTotal - commissionMontant;

    return (
        <>
            <Head title={`Commande — ${commande.code}`} />
            <div className="flex flex-1 flex-col gap-6 p-6">

                {/* Retour + en-tête */}
                <div>
                    <Button variant="ghost" size="sm" asChild className="-ml-2 mb-2">
                        <Link href={admin.commandes.index().url}>
                            <ArrowLeft className="mr-1.5 h-4 w-4" />
                            Retour aux commandes
                        </Link>
                    </Button>

                    <div className="flex items-start gap-3">
                        <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary/10">
                            <ShoppingCart className="h-6 w-6 text-primary" />
                        </div>
                        <div>
                            <h1 className="font-mono text-2xl font-semibold tracking-tight">{commande.code}</h1>
                            <div className="mt-1 flex flex-wrap items-center gap-2">
                                <StatusBadge status={commande.statut} />
                                <span className="text-xs text-muted-foreground">
                                    Passée le {fmtDate(commande.created_at)}
                                </span>
                                {commande.updated_at !== commande.created_at && (
                                    <span className="text-xs text-muted-foreground">
                                        · Mise à jour le {fmtDate(commande.updated_at)}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Récapitulatif financier */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-sm font-semibold">Récapitulatif financier</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <SummaryItem label="Montant commande" value={fmtFcfa(commande.montant_total)} />
                            <SummaryItem
                                label="Commission VERGA"
                                value={commande.commission ? fmtFcfa(commande.commission.montant) : '—'}
                                hint={commande.commission?.taux ? `Taux : ${commande.commission.taux} %` : undefined}
                            />
                            <SummaryItem
                                label="Part agence (estimée)"
                                value={commande.commission ? fmtFcfa(montantAgence) : '—'}
                            />
                            <SummaryItem
                                label="Paiement"
                                value={commande.paiement ? fmtFcfa(commande.paiement.montant) : '—'}
                                hint={commande.paiement?.statut ? undefined : 'Aucun paiement enregistré'}
                                badge={commande.paiement?.statut}
                            />
                        </div>
                    </CardContent>
                </Card>

                {/* Client + Agence + Offre */}
                <div className="grid gap-4 lg:grid-cols-3">
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-1.5 text-sm font-semibold">
                                <User className="h-4 w-4 text-muted-foreground" />
                                Client
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {commande.client ? (
                                <>
                                    <p className="font-medium">{commande.client.prenom} {commande.client.nom}</p>
                                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                        <Mail className="h-3.5 w-3.5 shrink-0" />
                                        {commande.client.email}
                                    </div>
                                    {commande.client.telephone && (
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <Phone className="h-3.5 w-3.5 shrink-0" />
                                            {commande.client.telephone}
                                        </div>
                                    )}
                                </>
                            ) : (
                                <p className="text-sm text-muted-foreground">Client introuvable.</p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-1.5 text-sm font-semibold">
                                <Building2 className="h-4 w-4 text-muted-foreground" />
                                Agence
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {commande.agence ? (
                                <div className="space-y-3">
                                    <p className="font-medium">{commande.agence.nom}</p>
                                    {commande.agence.ville && (
                                        <p className="text-sm text-muted-foreground">{commande.agence.ville}</p>
                                    )}
                                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                        <Mail className="h-3.5 w-3.5 shrink-0" />
                                        {commande.agence.email}
                                    </div>
                                    <Button variant="outline" size="sm" asChild className="mt-1 w-full">
                                        <Link href={admin.agences.show(commande.agence.id).url}>
                                            Voir l'agence
                                        </Link>
                                    </Button>
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">Aucune agence associée.</p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-1.5 text-sm font-semibold">
                                <Tag className="h-4 w-4 text-muted-foreground" />
                                Offre souscrite
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {commande.offre ? (
                                <div className="space-y-3">
                                    <p className="font-medium">{commande.offre.titre}</p>
                                    <Row label="Type">{commande.offre.type}</Row>
                                    <Row label="Prix unitaire">{fmtFcfa(commande.offre.prix)}</Row>
                                    {(commande.offre.origine || commande.offre.destination) && (
                                        <Row label="Trajet">
                                            {[commande.offre.origine, commande.offre.destination]
                                                .filter(Boolean)
                                                .join(' → ')}
                                        </Row>
                                    )}
                                    <Row label="Statut offre">
                                        <StatusBadge status={commande.offre.statut} />
                                    </Row>
                                    <Row label="Quantité commandée">{commande.quantite}</Row>
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">Aucune offre associée.</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Paiement + Commission */}
                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-1.5 text-sm font-semibold">
                                <CreditCard className="h-4 w-4 text-muted-foreground" />
                                Paiement lié
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {commande.paiement ? (
                                <div className="space-y-3">
                                    <Row label="Montant">{fmtFcfa(commande.paiement.montant)}</Row>
                                    <Row label="Méthode">{commande.paiement.methode}</Row>
                                    <Row label="Référence">
                                        <span className="font-mono text-xs">
                                            {commande.paiement.reference ?? '—'}
                                        </span>
                                    </Row>
                                    <Row label="Statut">
                                        <StatusBadge status={commande.paiement.statut} />
                                    </Row>
                                    <Row label="Enregistré le">{fmtDate(commande.paiement.created_at, true)}</Row>
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">Aucun paiement enregistré pour cette commande.</p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-1.5 text-sm font-semibold">
                                <Percent className="h-4 w-4 text-muted-foreground" />
                                Commission VERGA
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {commande.commission ? (
                                <div className="space-y-3">
                                    <Row label="Montant">{fmtFcfa(commande.commission.montant)}</Row>
                                    <Row label="Taux">
                                        {commande.commission.taux ? `${commande.commission.taux} %` : '—'}
                                    </Row>
                                    <Row label="Calculée le">{fmtDate(commande.commission.created_at, true)}</Row>
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">Aucune commission enregistrée pour cette commande.</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Colis */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="flex items-center gap-2 text-sm font-semibold">
                            <Package className="h-4 w-4 text-muted-foreground" />
                            Colis associés
                            <Badge variant="secondary">{commande.colis.length}</Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {commande.colis.length === 0 ? (
                            <p className="px-6 py-8 text-center text-sm text-muted-foreground">
                                Aucun colis enregistré pour cette commande.
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Référence</TableHead>
                                        <TableHead>Statut</TableHead>
                                        <TableHead>Date</TableHead>
                                        <TableHead className="text-right">Action</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {commande.colis.map((colis) => (
                                        <TableRow key={colis.id}>
                                            <TableCell className="font-mono text-xs font-medium">{colis.reference}</TableCell>
                                            <TableCell><StatusBadge status={colis.statut} /></TableCell>
                                            <TableCell className="text-sm text-muted-foreground">
                                                {fmtDate(colis.created_at)}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link href={admin.colis.show(colis.id).url}>Voir</Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                {/* Réclamations */}
                {commande.reclamations.length > 0 && (
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-2 text-sm font-semibold">
                                <MessageSquareWarning className="h-4 w-4 text-muted-foreground" />
                                Réclamations liées
                                <Badge variant="secondary">{commande.reclamations.length}</Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Objet</TableHead>
                                        <TableHead>Statut</TableHead>
                                        <TableHead>Date</TableHead>
                                        <TableHead className="text-right">Action</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {commande.reclamations.map((rec) => (
                                        <TableRow key={rec.id}>
                                            <TableCell className="font-medium">{rec.objet}</TableCell>
                                            <TableCell><StatusBadge status={rec.statut} /></TableCell>
                                            <TableCell className="text-sm text-muted-foreground">
                                                {fmtDate(rec.created_at)}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link href={admin.reclamations.show(rec.id).url}>Voir</Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}

            </div>
        </>
    );
}

CommandeShow.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Commandes', href: admin.commandes.index().url },
        { title: 'Détail commande' },
    ],
};

// ─── Sub-composants ───────────────────────────────────────────────────────

function Row({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-4 text-sm">
            <span className="text-muted-foreground">{label}</span>
            <span className="text-right">{children}</span>
        </div>
    );
}

function SummaryItem({
    label,
    value,
    hint,
    badge,
}: {
    label: string;
    value: string;
    hint?: string;
    badge?: string;
}) {
    return (
        <div className="rounded-lg border border-border bg-muted/30 p-4">
            <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{label}</p>
            <p className="mt-1 text-lg font-semibold tabular-nums">{value}</p>
            {badge && (
                <div className="mt-2">
                    <StatusBadge status={badge} />
                </div>
            )}
            {hint && !badge && (
                <p className="mt-1 text-xs text-muted-foreground">{hint}</p>
            )}
        </div>
    );
}
