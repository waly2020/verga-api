import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Mail,
    MapPin,
    MessageSquareWarning,
    Phone,
    ShoppingCart,
    User,
} from 'lucide-react';
import { StatusBadge } from '@/components/admin/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import admin from '@/routes/admin';
import type { ClientCommandeRow, ClientDetail, ReclamationRow } from '@/types';

interface Stats {
    nb_commandes: number;
    nb_reclamations: number;
    total_paiements: number;
}

interface Props {
    client: ClientDetail;
    stats: Stats;
    commandes: ClientCommandeRow[];
    reclamations: ReclamationRow[];
}

const fmtFcfa = (n: number) => `${n.toLocaleString('fr-FR')} FCFA`;

export default function ClientShow({ client, stats, commandes, reclamations }: Props) {
    return (
        <>
            <Head title={`Client — ${client.prenom} ${client.nom}`} />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div>
                    <Button variant="ghost" size="sm" asChild className="-ml-2 mb-2">
                        <Link href={admin.clients.index().url}>
                            <ArrowLeft className="mr-1.5 h-4 w-4" />
                            Retour aux clients
                        </Link>
                    </Button>

                    <div className="flex items-start gap-3">
                        <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary/10">
                            <User className="h-6 w-6 text-primary" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight">
                                {client.prenom} {client.nom}
                            </h1>
                            <div className="mt-1 flex flex-wrap items-center gap-2">
                                <StatusBadge status={client.statut} />
                                <Badge variant="outline">{client.type}</Badge>
                                <span className="text-xs text-muted-foreground">
                                    Inscrit le {new Date(client.created_at).toLocaleDateString('fr-FR')}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    <Card>
                        <CardContent className="pt-6">
                            <p className="text-xs font-medium uppercase text-muted-foreground">Commandes</p>
                            <p className="mt-1 text-2xl font-semibold tabular-nums">{stats.nb_commandes}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <p className="text-xs font-medium uppercase text-muted-foreground">Paiements validés</p>
                            <p className="mt-1 text-2xl font-semibold tabular-nums">{fmtFcfa(stats.total_paiements)}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <p className="text-xs font-medium uppercase text-muted-foreground">Réclamations</p>
                            <p className="mt-1 text-2xl font-semibold tabular-nums">{stats.nb_reclamations}</p>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-sm font-semibold">Informations</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-3 sm:grid-cols-2">
                        <InfoRow icon={Mail} label={client.email} />
                        {client.telephone && <InfoRow icon={Phone} label={client.telephone} />}
                        {client.ville && <InfoRow icon={MapPin} label={`${client.ville}${client.pays ? `, ${client.pays}` : ''}`} />}
                        {client.adresse && <InfoRow icon={MapPin} label={client.adresse} />}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="flex items-center gap-2 text-sm font-semibold">
                            <ShoppingCart className="h-4 w-4" />
                            Commandes récentes
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {commandes.length === 0 ? (
                            <p className="px-6 py-8 text-center text-sm text-muted-foreground">Aucune commande.</p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Code</TableHead>
                                        <TableHead>Agence</TableHead>
                                        <TableHead>Montant</TableHead>
                                        <TableHead>Statut</TableHead>
                                        <TableHead className="text-right">Action</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {commandes.map((cmd) => (
                                        <TableRow key={cmd.id}>
                                            <TableCell className="font-mono text-xs font-medium">{cmd.code}</TableCell>
                                            <TableCell>{cmd.agence?.nom ?? '—'}</TableCell>
                                            <TableCell className="tabular-nums">
                                                {Number(cmd.montant_total).toLocaleString('fr-FR')} FCFA
                                            </TableCell>
                                            <TableCell><StatusBadge status={cmd.statut} /></TableCell>
                                            <TableCell className="text-right">
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link href={admin.commandes.show(cmd.id).url}>Voir</Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="flex items-center gap-2 text-sm font-semibold">
                            <MessageSquareWarning className="h-4 w-4" />
                            Réclamations récentes
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {reclamations.length === 0 ? (
                            <p className="px-6 py-8 text-center text-sm text-muted-foreground">Aucune réclamation.</p>
                        ) : (
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
                                    {reclamations.map((rec) => (
                                        <TableRow key={rec.id}>
                                            <TableCell className="font-medium">{rec.objet}</TableCell>
                                            <TableCell><StatusBadge status={rec.statut} /></TableCell>
                                            <TableCell className="text-sm text-muted-foreground">
                                                {new Date(rec.created_at).toLocaleDateString('fr-FR')}
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
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

ClientShow.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Clients', href: admin.clients.index().url },
        { title: 'Fiche client' },
    ],
};

function InfoRow({ icon: Icon, label }: { icon: React.ElementType; label: string }) {
    return (
        <div className="flex items-center gap-2 text-sm text-muted-foreground">
            <Icon className="h-4 w-4 shrink-0" />
            <span>{label}</span>
        </div>
    );
}
