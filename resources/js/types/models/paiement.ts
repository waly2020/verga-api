import type { PaiementStatut } from './common';

export type PaiementRow = {
    id: string;
    code: string | null;
    reference: string | null;
    bamboo_reference: string | null;
    commande: { id: string; code: string } | null;
    montant: string;
    methode: string;
    statut: PaiementStatut | string;
    created_at: string;
};

export type PaiementInfo = {
    id: string;
    code: string | null;
    montant: string;
    methode: string;
    reference: string | null;
    bamboo_reference: string | null;
    statut: PaiementStatut | string;
    created_at: string;
};

export type PaiementApi = PaiementInfo & {
    commande?: { id: string; code: string } | null;
};
