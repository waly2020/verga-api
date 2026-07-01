import type { CommandeStatut, PaiementStatut } from './common';

export type CheckoutResponse = {
    commande_id: string;
    code: string;
    montant_total: number;
    paiement_code: string;
    redirect_url: string | null;
    verification_url: string;
};

export type PaymentStatusCheckResponse = {
    paiement_code: string;
    statut: PaiementStatut | string;
    bamboo_reference: string | null;
    commande_code: string | null;
    commande_statut: CommandeStatut | string | null;
    en_attente_bamboo: boolean;
};
