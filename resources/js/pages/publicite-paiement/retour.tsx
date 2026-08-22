import { Head } from '@inertiajs/react';
import { Megaphone } from 'lucide-react';
import { StatusBadge } from '@/components/admin/status-badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Props = {
    paiement: {
        code: string;
        statut: string;
        montant: number;
        montant_sous_total: number;
        montant_frais: number;
        nombre_jours: number;
        bamboo_message: string | null;
    };
    publicite: {
        id: string;
        titre: string;
        statut: string;
        statut_paiement: string;
        date_debut: string;
        date_fin: string;
    } | null;
};

export default function PublicitePaiementRetour({ paiement, publicite }: Props) {
    return (
        <>
            <Head title={`Paiement ${paiement.code}`} />
            <div className="mx-auto flex min-h-svh max-w-lg flex-col justify-center gap-6 p-6">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-lg">
                            <Megaphone className="h-5 w-5" />
                            Paiement publicité
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm">
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground">Référence</span>
                            <span className="font-medium">{paiement.code}</span>
                        </div>
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground">Statut paiement</span>
                            <StatusBadge status={paiement.statut} />
                        </div>
                        {publicite && (
                            <>
                                <div className="flex items-center justify-between">
                                    <span className="text-muted-foreground">Publicité</span>
                                    <span className="font-medium">{publicite.titre}</span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-muted-foreground">Publication</span>
                                    <StatusBadge status={publicite.statut} />
                                </div>
                            </>
                        )}
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground">Montant</span>
                            <span className="tabular-nums font-medium">
                                {paiement.montant.toLocaleString('fr-FR')} FCFA
                            </span>
                        </div>
                        {paiement.bamboo_message && (
                            <p className="text-muted-foreground">{paiement.bamboo_message}</p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
