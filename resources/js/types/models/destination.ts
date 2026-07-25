export type DestinationApi = {
    id: string;
    depart: string;
    arrivee: string;
    montant: number | null;
    commission_pourcentage: number | null;
    appliquer_configuration: boolean;
    actif?: boolean;
};

export type DestinationSummary = Pick<
    DestinationApi,
    'id' | 'depart' | 'arrivee' | 'montant' | 'commission_pourcentage' | 'appliquer_configuration'
>;

export type DestinationRow = DestinationApi & {
    actif: boolean;
    offres_count: number;
    created_at: string;
};

export type DestinationFormData = {
    depart: string;
    arrivee: string;
    appliquer_configuration: boolean;
    montant: string;
    commission_pourcentage: string;
    actif: boolean;
};
