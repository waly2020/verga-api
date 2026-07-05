export type DashboardPeriode = 'mois' | 'mois_dernier' | 'trimestre' | 'semestre' | 'annee' | 'tout';

export type ClientDashboardStats = {
    nb_commandes: number;
    nb_commandes_en_attente: number;
    nb_commandes_confirmees: number;
    nb_colis: number;
    nb_colis_en_transit: number;
    nb_colis_arrives: number;
    total_depense: number;
    total_sous_total: number;
    total_commissions: number;
    nb_reclamations: number;
    nb_reclamations_ouvertes: number;
};

export type ClientDashboardResponse = {
    periode: DashboardPeriode | string;
    debut: string;
    fin: string;
    profil: {
        type: string;
        nom: string;
        prenom: string;
    };
    stats: ClientDashboardStats;
    commandes_par_statut: Record<string, number>;
    colis_par_statut: Record<string, number>;
    dernieres_commandes: Array<{
        id: string;
        code: string;
        montant_sous_total: string | number | null;
        montant_commission_client: string | number | null;
        montant_total: string | number;
        statut: string;
        created_at: string | null;
        agence: { id: string; nom: string } | null;
    }>;
};

export type AgenceDashboardStats = {
    nb_offres: number;
    nb_offres_actives: number;
    capacite_disponible_totale: number;
    nb_commandes: number;
    nb_commandes_en_attente: number;
    nb_commandes_confirmees: number;
    total_paiements: number;
    total_sous_total: number;
    total_commissions_client: number;
    total_commissions_agence: number;
    /** @deprecated Utiliser total_commissions_agence */
    total_commissions: number;
    revenu_net_estime: number;
    reversements_en_attente: number;
    nb_colis: number;
    nb_colis_en_transit: number;
    nb_reclamations_ouvertes: number;
};

export type AgenceDashboardResponse = {
    periode: DashboardPeriode | string;
    debut: string;
    fin: string;
    profil: {
        nom: string;
        ville: string | null;
        statut: string;
    };
    stats: AgenceDashboardStats;
    commandes_par_statut: Record<string, number>;
    colis_par_statut: Record<string, number>;
    top_offres: Array<{
        id: string;
        titre: string;
        type: string;
        prix: string | number;
        statut: string;
        capacite_disponible: string | number;
        nb_commandes: number;
    }>;
    dernieres_commandes: Array<{
        id: string;
        code: string;
        montant_sous_total: string | number | null;
        montant_commission_client: string | number | null;
        montant_total: string | number;
        statut: string;
        created_at: string | null;
        client: {
            id?: string;
            nom: string;
            prenom: string;
            invite?: boolean;
        } | null;
    }>;
};

export type DashboardApiResponse<T> = {
    data: T;
};
