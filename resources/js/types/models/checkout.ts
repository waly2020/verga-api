import type { CommandeStatut, PaiementStatut } from './common';

export type CheckoutResponse = {
    commande_id: string;
    code: string;
    commande_statut: CommandeStatut | string;
    quantite_reservee: number;
    quantite_payee: number;
    quantite_a_payer: number;
    quantite_restante: number;
    montant_sous_total: number;
    montant_commission_client: number;
    montant_total: number;
    paiement_code: string;
    redirect_url: string | null;
    verification_url: string;
    mode: 'reservation' | 'complet';
};

export type PaymentStatusCheckResponse = {
    paiement_code: string;
    statut: PaiementStatut | string;
    bamboo_reference: string | null;
    commande_code: string | null;
    commande_statut: CommandeStatut | string | null;
    quantite_reservee: number | null;
    quantite_payee: number | null;
    quantite_restante: number | null;
    en_attente_bamboo: boolean;
};
