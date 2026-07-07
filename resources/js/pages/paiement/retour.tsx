import { Head } from '@inertiajs/react';
import { Download, Package, Receipt } from 'lucide-react';
import { StatusBadge } from '@/components/admin/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';

type RecapColis = {
    reference: string;
    description: string | null;
    statut: string;
};

type Props = {
    paiement: {
        code: string;
        statut: string;
        methode: string;
        reference: string | null;
        bamboo_reference: string | null;
        bamboo_message: string | null;
        created_at: string | null;
        quantite_label: string | null;
        montant_sous_total: number;
        montant_commission_client: number;
        montant: number;
    };
    commande: {
        code: string;
        statut: string;
        quantite_label: string | null;
        quantite_payee_label: string | null;
        quantite_restante_label: string | null;
        montant_total: number;
    } | null;
    client: {
        nom: string | null;
        email: string | null;
        telephone: string | null;
    };
    agence: {
        nom: string;
        email: string | null;
        telephone: string | null;
        ville: string | null;
    } | null;
    offre: {
        titre: string;
        origine: string;
        destination: string;
        prix: number;
        type_offre: { nom: string; unite_label: string } | null;
    } | null;
    colis: RecapColis[];
    facture_url: string;
};

function fmtFcfa(value: number): string {
    return `${value.toLocaleString('fr-FR')} FCFA`;
}

function Row({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="flex items-start justify-between gap-4 text-sm">
            <span className="text-muted-foreground">{label}</span>
            <span className="text-right font-medium">{children}</span>
        </div>
    );
}

export default function PaiementRetour({
    paiement,
    commande,
    client,
    agence,
    offre,
    colis,
    facture_url,
}: Props) {
    const dateLabel = paiement.created_at
        ? new Date(paiement.created_at).toLocaleString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        })
        : '—';

    return (
        <>
            <Head title={`Paiement ${paiement.code}`} />

            <div className="space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="space-y-2">
                        <div className="flex items-center gap-2 text-muted-foreground">
                            <Receipt className="h-5 w-5" />
                            <span className="text-sm font-medium">Récapitulatif de paiement</span>
                        </div>
                        <h1 className="font-mono text-2xl font-semibold tracking-tight">{paiement.code}</h1>
                        {commande && (
                            <p className="text-sm text-muted-foreground">
                                Commande <span className="font-mono font-medium text-foreground">{commande.code}</span>
                            </p>
                        )}
                    </div>
                    <StatusBadge status={paiement.statut} />
                </div>

                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">Transaction</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <Row label="Date">{dateLabel}</Row>
                        <Row label="Quantité payée">{paiement.quantite_label ?? '—'}</Row>
                        <Row label="Sous-total transport">{fmtFcfa(paiement.montant_sous_total)}</Row>
                        {paiement.montant_commission_client > 0 && (
                            <Row label="Commission VERGA">{fmtFcfa(paiement.montant_commission_client)}</Row>
                        )}
                        <Separator />
                        <Row label="Total payé">
                            <span className="text-base text-primary">{fmtFcfa(paiement.montant)}</span>
                        </Row>
                        {paiement.bamboo_reference && (
                            <Row label="Réf. Bamboo">{paiement.bamboo_reference}</Row>
                        )}
                        {paiement.bamboo_message && (
                            <p className="rounded-md bg-muted px-3 py-2 text-sm text-muted-foreground">
                                {paiement.bamboo_message}
                            </p>
                        )}
                    </CardContent>
                </Card>

                {commande && (
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base">Commande</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <Row label="Statut"><StatusBadge status={commande.statut} /></Row>
                            <Row label="Quantité réservée">{commande.quantite_label ?? '—'}</Row>
                            <Row label="Quantité payée">{commande.quantite_payee_label ?? '—'}</Row>
                            {commande.quantite_restante_label && commande.quantite_restante_label !== '0' && (
                                <Row label="Reste à payer">{commande.quantite_restante_label}</Row>
                            )}
                            <Row label="Total commande validé">{fmtFcfa(commande.montant_total)}</Row>
                        </CardContent>
                    </Card>
                )}

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base">Client</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <p className="font-medium">{client.nom ?? '—'}</p>
                            {client.telephone && <p className="text-muted-foreground">{client.telephone}</p>}
                            {client.email && <p className="text-muted-foreground">{client.email}</p>}
                        </CardContent>
                    </Card>

                    {agence && (
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base">Agence</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2 text-sm">
                                <p className="font-medium">{agence.nom}</p>
                                {agence.ville && <p className="text-muted-foreground">{agence.ville}</p>}
                                {agence.telephone && <p className="text-muted-foreground">{agence.telephone}</p>}
                            </CardContent>
                        </Card>
                    )}
                </div>

                {offre && (
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base">Offre</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <p className="font-medium">{offre.titre}</p>
                            <Row label="Trajet">{`${offre.origine} → ${offre.destination}`}</Row>
                            <Row label="Prix unitaire">{fmtFcfa(offre.prix)}</Row>
                            {offre.type_offre && (
                                <Row label="Type">{offre.type_offre.nom}</Row>
                            )}
                        </CardContent>
                    </Card>
                )}

                {colis.length > 0 && (
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Package className="h-4 w-4" />
                                Colis ({colis.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {colis.map((item) => (
                                <div key={item.reference} className="rounded-lg border p-3 text-sm">
                                    <div className="flex items-center justify-between gap-2">
                                        <span className="font-mono font-medium">{item.reference}</span>
                                        <StatusBadge status={item.statut} />
                                    </div>
                                    {item.description && (
                                        <p className="mt-1 text-muted-foreground">{item.description}</p>
                                    )}
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                <div className="flex justify-center pt-2">
                    <Button asChild size="lg">
                        <a href={facture_url}>
                            <Download className="mr-2 h-4 w-4" />
                            Télécharger la facture PDF
                        </a>
                    </Button>
                </div>
            </div>
        </>
    );
}
