export type AgenceSolde = {
    montant_paiements_valides: number;
    montant_reversements: number;
    montant_solde: number;
    montant_reversements_en_attente: number;
    montant_disponible: number;
};

export type AgenceSoldeResponse = {
    data: AgenceSolde;
};

export type AgenceReversement = {
    id: string;
    montant: number;
    periode: string;
    statut: 'en_attente' | 'effectué';
    effectue_le: string | null;
    created_at: string;
};
