export type ClientCommissionConfig = {
    type: 'pourcentage' | 'fixe';
    valeur: number;
    libelle: string | null;
};

export type OffrePricingEstimateResponse = {
    offre_id: string;
    quantite: number;
    prix_unitaire: number;
    montant_sous_total: number;
    montant_commission_client: number;
    montant_total: number;
    capacite_disponible: number | null;
    stock_suffisant: boolean;
    commission: ClientCommissionConfig | null;
};
