import type { PaiementStatut } from './common';

export type PaiementRow = {
    id: string;
    code: string | null;
    reference: string | null;
    bamboo_reference: string | null;
    bamboo_message?: string | null;
    commande: { id: string; code: string } | null;
    quantite?: string | null;
    montant_sous_total?: string | null;
    montant_commission_client?: string | null;
    montant: string;
    methode: string;
    statut: PaiementStatut | string;
    created_at: string;
};

/** Réponse API client / agence — liste paiements */
export type PaiementApi = {
    code: string;
    montant: number;
    created_at: string | null;
    bamboo_reference: string | null;
    commande_code?: string | null;
};

/** Détail paiement admin (back-office) */
export type PaiementInfo = {
    id: string;
    code: string | null;
    quantite?: string | null;
    quantite_label?: string | null;
    montant_sous_total?: string | null;
    montant_commission_client?: string | null;
    montant: string;
    methode: string;
    reference: string | null;
    bamboo_reference: string | null;
    bamboo_message?: string | null;
    statut: PaiementStatut | string;
    created_at: string;
};
