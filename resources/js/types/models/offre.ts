import type { AgenceSummary, LogoApi, OffreStatut, OffreType } from './common';
import type { DestinationSummary } from './destination';
import type { TypeOffreApi, TypeOffreSummary } from './type-offre';

export type OffrePalier = {
    min: number | string;
    max: number | string | null;
    prix: number | string;
};

export type OffrePalierForm = {
    min: string;
    max: string;
    prix: string;
};

export type OffreCapacite = {
    capacite_illimitee?: boolean;
    capacite_totale: string | number | null;
    capacite_disponible: string | number | null;
};

export type OffreRow = OffreCapacite & {
    id: string;
    agence_id: string;
    destination_id: string;
    titre: string;
    agence: (AgenceSummary & { logo?: LogoApi | null }) | null;
    type: OffreType | string;
    type_offre_id?: string | null;
    type_offre?: TypeOffreSummary | null;
    destination?: DestinationSummary | null;
    prix: string;
    paliers?: OffrePalier[] | null;
    date_depart?: string | null;
    date_depot_colis?: string | null;
    statut: OffreStatut | string;
    description?: string | null;
};

export type OffreAgenceRow = Omit<OffreRow, 'agence'>;

export type OffreSummary = {
    id: string;
    titre: string;
    type: string;
    type_offre_id?: string | null;
    prix: string;
};

export type OffreInfo = OffreSummary &
    OffreCapacite & {
        destination?: DestinationSummary | null;
        date_depart?: string | null;
        date_depot_colis?: string | null;
        statut: string;
        type_offre?: TypeOffreSummary | null;
    };

export type OffreApi = OffreCapacite & {
    id: string;
    titre: string;
    description: string | null;
    type: string;
    type_offre_id?: string | null;
    type_offre?: TypeOffreApi | null;
    prix: string | number;
    paliers?: OffrePalier[] | null;
    destination_id: string;
    destination?: DestinationSummary | null;
    date_depart?: string | null;
    date_depot_colis?: string | null;
    statut: string;
    created_at: string | null;
    agence?: (AgenceSummary & {
        ville?: string | null;
        logo?: LogoApi | null;
    }) | null;
};

export type ListClientOffresFilters = {
    search?: string;
    destination?: string;
    type?: 'particulier' | 'metre_cube' | 'conteneur';
    type_offre_id?: string;
    date_debut?: string;
    date_fin?: string;
    page?: number;
    per_page?: number;
};

export type OffreFormData = {
    agence_id: string;
    destination_id: string;
    titre: string;
    type_offre_id: string;
    prix: string;
    paliers: OffrePalierForm[] | null;
    capacite_illimitee: boolean;
    capacite_totale: string;
    date_depart: string;
    date_depot_colis: string;
    description: string;
    statut: string;
};

/** @deprecated Utiliser OffreFormData */
export type CreateOffreFormData = OffreFormData;
