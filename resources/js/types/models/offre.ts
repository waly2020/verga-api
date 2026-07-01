import type { AgenceSummary, OffreStatut, OffreType } from './common';

export type OffreCapacite = {
    capacite_totale: string | number;
    capacite_disponible: string | number;
};

export type OffreRow = OffreCapacite & {
    id: string;
    titre: string;
    agence: AgenceSummary | null;
    type: OffreType | string;
    prix: string;
    origine: string;
    destination: string;
    statut: OffreStatut | string;
    description?: string | null;
};

export type OffreAgenceRow = Omit<OffreRow, 'agence'>;

export type OffreSummary = {
    id: string;
    titre: string;
    type: string;
    prix: string;
};

export type OffreInfo = OffreSummary &
    OffreCapacite & {
        origine: string | null;
        destination: string | null;
        statut: string;
    };

export type OffreApi = OffreCapacite & {
    id: string;
    titre: string;
    description: string | null;
    type: string;
    prix: string | number;
    origine: string;
    destination: string;
    statut: string;
    agence?: (AgenceSummary & { ville?: string | null }) | null;
};

export type CreateOffreFormData = {
    agence_id: string;
    titre: string;
    type: string;
    prix: string;
    capacite_totale: string;
    origine: string;
    destination: string;
    description: string;
    statut: string;
};
