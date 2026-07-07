import { Head, Link } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowDownRight,
    Ban,
    Building2,
    CreditCard,
    Mail,
    MapPin,
    Package,
    Phone,
    ShoppingCart,
    Trash2,
    User,
    Wallet,
} from 'lucide-react';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { StatusBadge } from '@/components/admin/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import admin from '@/routes/admin';
import { formatQuantite } from '@/lib/format-quantite';
import type { CommandeRow, OffreAgenceRow } from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────

type Agence = {
    id: string;
    nom: string;
    email: string;
    telephone: string | null;
    adresse: string | null;
    ville: string | null;
    pays: string | null;
    statut: string;
    created_at: string;
    user: { id: number; name: string; email: string } | null;
    type_agence: { id: string; nom: string } | null;
};

interface Stats {
    nb_offres: number;
    nb_commandes: number;
    montant_paiements_valides: number;
    montant_reversements: number;
    montant_solde: number;
}

interface Props {
    agence: Agence;
    stats: Stats;
    offres: OffreAgenceRow[];
    commandes: CommandeRow[];
}

// ─── Helpers ──────────────────────────────────────────────────────────────

const fmt     = (n: number) => n.toLocaleString('fr-FR');
const fmtFcfa = (n: number) => `${fmt(n)} FCFA`;

const TYPE_OFFRE: Record<string, string> = {
    particulier: 'Particulier',
    metre_cube:  'Mètre cube',
    conteneur:   'Conteneur',
};

// ─── Composant ────────────────────────────────────────────────────────────

export default function AgenceShow({ agence, stats, offres, commandes }: Props) {
    const toggleStatut = () =>
        router.patch(admin.agences.statut(agence.id).url, {}, { preserveState: false });

    const supprimer = () =>
        router.delete(admin.agences.destroy(agence.id).url);

    return (
        <>
            <Head title={`Agence — ${agence.nom}`} />
            <div className="flex flex-1 flex-col gap-6 p-6">

                {/* Navigation retour */}
                <div>
                    <Button variant="ghost" size="sm" asChild className="-ml-2 mb-2">
                        <Link href={admin.agences.index().url}>
                            <ArrowLeft className="mr-1.5 h-4 w-4" />
                            Retour aux agences
                        </Link>
                    </Button>

                    {/* En-tête */}
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div className="flex items-center gap-3">
                            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10">
                                <Building2 className="h-6 w-6 text-primary" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-semibold tracking-tight">{agence.nom}</h1>
                                <div className="mt-1 flex items-center gap-2">
                                    <StatusBadge status={agence.statut} />
                                    {agence.type_agence && (
                                        <Badge variant="secondary">{agence.type_agence.nom}</Badge>
                                    )}
                                    <span className="text-xs text-muted-foreground">
                                        Membre depuis {new Date(agence.created_at).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex shrink-0 items-center gap-2">
                            <ConfirmDialog
                                trigger={
                                    <Button variant="outline">
                                        <Ban className="mr-1.5 h-4 w-4" />
                                        {agence.statut === 'bloqué' ? 'Débloquer' : 'Bloquer'}
                                    </Button>
                                }
                                title={agence.statut === 'bloqué' ? 'Débloquer cette agence ?' : 'Bloquer cette agence ?'}
                                description={
                                    agence.statut === 'bloqué'
                                        ? `L'agence "${agence.nom}" retrouvera l'accès à la plateforme.`
                                        : `L'agence "${agence.nom}" ne pourra plus accéder à la plateforme.`
                                }
                                confirmLabel={agence.statut === 'bloqué' ? 'Débloquer' : 'Bloquer'}
                                variant={agence.statut === 'bloqué' ? 'default' : 'destructive'}
                                onConfirm={toggleStatut}
                            />
                            <ConfirmDialog
                                trigger={
                                    <Button variant="destructive">
                                        <Trash2 className="mr-1.5 h-4 w-4" />
                                        Supprimer
                                    </Button>
                                }
                                title="Supprimer cette agence ?"
                                description={`Cette action est irréversible. L'agence "${agence.nom}" et toutes ses données seront supprimées définitivement.`}
                                confirmLabel="Supprimer"
                                onConfirm={supprimer}
                            />
                        </div>
                    </div>
                </div>

                {/* Informations & gérant */}
                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-semibold">Informations</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {agence.email && (
                                <div className="flex items-center gap-2 text-sm">
                                    <Mail className="h-4 w-4 shrink-0 text-muted-foreground" />
                                    <span>{agence.email}</span>
                                </div>
                            )}
                            {agence.telephone && (
                                <div className="flex items-center gap-2 text-sm">
                                    <Phone className="h-4 w-4 shrink-0 text-muted-foreground" />
                                    <span>{agence.telephone}</span>
                                </div>
                            )}
                            {(agence.adresse || agence.ville) && (
                                <div className="flex items-start gap-2 text-sm">
                                    <MapPin className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                                    <span>
                                        {[agence.adresse, agence.ville, agence.pays].filter(Boolean).join(', ')}
                                    </span>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-semibold">Gérant</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {agence.user ? (
                                <div className="space-y-3">
                                    <div className="flex items-center gap-2 text-sm">
                                        <User className="h-4 w-4 shrink-0 text-muted-foreground" />
                                        <span className="font-medium">{agence.user.name}</span>
                                    </div>
                                    <div className="flex items-center gap-2 text-sm">
                                        <Mail className="h-4 w-4 shrink-0 text-muted-foreground" />
                                        <span>{agence.user.email}</span>
                                    </div>
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">Aucun gérant associé</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Activité */}
                <div className="grid gap-4 sm:grid-cols-2">
                    {[
                        { label: 'Offres publiées', value: fmt(stats.nb_offres), icon: Package, color: 'text-blue-500' },
                        { label: 'Commandes reçues', value: fmt(stats.nb_commandes), icon: ShoppingCart, color: 'text-violet-500' },
                    ].map((s) => (
                        <Card key={s.label}>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-xs font-medium text-muted-foreground">{s.label}</CardTitle>
                                <s.icon className={`h-4 w-4 ${s.color}`} />
                            </CardHeader>
                            <CardContent>
                                <div className="truncate text-xl font-bold">{s.value}</div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Finance */}
                <div className="grid gap-4 sm:grid-cols-3">
                    {[
                        {
                            label: 'Paiements perçus',
                            value: fmtFcfa(stats.montant_paiements_valides),
                            description: 'Somme des paiements validés (part agence)',
                            icon: CreditCard,
                            color: 'text-emerald-500',
                        },
                        {
                            label: 'Montant reversé',
                            value: fmtFcfa(stats.montant_reversements),
                            description: 'Reversements effectués vers l\'agence',
                            icon: ArrowDownRight,
                            color: 'text-amber-500',
                        },
                        {
                            label: 'Solde restant',
                            value: fmtFcfa(stats.montant_solde),
                            description: 'Paiements perçus − montant reversé',
                            icon: Wallet,
                            color: 'text-primary',
                        },
                    ].map((s) => (
                        <Card key={s.label}>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-xs font-medium text-muted-foreground">{s.label}</CardTitle>
                                <s.icon className={`h-4 w-4 ${s.color}`} />
                            </CardHeader>
                            <CardContent>
                                <div className="truncate text-xl font-bold tabular-nums">{s.value}</div>
                                <p className="mt-1 text-xs text-muted-foreground">{s.description}</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Offres */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-sm font-semibold">
                            Offres publiées
                            <Badge variant="secondary" className="ml-2">{offres.length}</Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {offres.length === 0 ? (
                            <p className="px-6 py-8 text-center text-sm text-muted-foreground">
                                Aucune offre publiée.
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Titre</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Prix</TableHead>
                                        <TableHead>Stock</TableHead>
                                        <TableHead>Trajet</TableHead>
                                        <TableHead>Statut</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {offres.map((offre) => (
                                        <TableRow key={offre.id}>
                                            <TableCell className="font-medium">{offre.titre}</TableCell>
                                            <TableCell>{TYPE_OFFRE[offre.type] ?? offre.type}</TableCell>
                                            <TableCell className="tabular-nums">
                                                {Number(offre.prix).toLocaleString('fr-FR')} FCFA
                                            </TableCell>
                                            <TableCell className="tabular-nums text-sm">
                                                {Number(offre.capacite_disponible).toLocaleString('fr-FR')}
                                                <span className="text-muted-foreground">
                                                    {' / '}
                                                    {Number(offre.capacite_totale).toLocaleString('fr-FR')}
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-sm text-muted-foreground">
                                                {offre.origine} → {offre.destination}
                                            </TableCell>
                                            <TableCell><StatusBadge status={offre.statut} /></TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                {/* Commandes récentes */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-sm font-semibold">
                            Commandes récentes
                            <span className="ml-2 text-xs font-normal text-muted-foreground">(10 dernières)</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {commandes.length === 0 ? (
                            <p className="px-6 py-8 text-center text-sm text-muted-foreground">
                                Aucune commande.
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Code</TableHead>
                                        <TableHead>Client</TableHead>
                                        <TableHead>Quantité</TableHead>
                                        <TableHead>Montant</TableHead>
                                        <TableHead>Statut</TableHead>
                                        <TableHead>Date</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {commandes.map((cmd) => (
                                        <TableRow key={cmd.id}>
                                            <TableCell className="font-mono text-xs font-medium">{cmd.code}</TableCell>
                                            <TableCell>
                                                {cmd.client
                                                    ? `${cmd.client.prenom} ${cmd.client.nom}`
                                                    : cmd.prenom && cmd.nom
                                                      ? `${cmd.prenom} ${cmd.nom} (invité)`
                                                      : '—'}
                                            </TableCell>
                                            <TableCell>
                                                {formatQuantite(cmd.quantite, cmd.offre?.type_offre)}
                                            </TableCell>
                                            <TableCell className="font-medium tabular-nums">
                                                {Number(cmd.montant_total).toLocaleString('fr-FR')} FCFA
                                            </TableCell>
                                            <TableCell><StatusBadge status={cmd.statut} /></TableCell>
                                            <TableCell className="text-sm text-muted-foreground">
                                                {new Date(cmd.created_at).toLocaleDateString('fr-FR')}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

            </div>
        </>
    );
}

AgenceShow.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Agences', href: admin.agences.index().url },
        { title: 'Fiche agence' },
    ],
};
