export type TypeOffreApi = {
    id: string;
    slug: string;
    nom: string;
    description: string | null;
    unite: string;
    unite_label: string;
    quantite_entier: boolean;
    quantite_min: number;
    actif?: boolean;
};

export type TypeOffreSummary = Pick<TypeOffreApi, 'id' | 'slug' | 'nom' | 'unite_label'>;

export type TypeOffreRow = TypeOffreApi & {
    actif: boolean;
    offres_count: number;
    created_at: string;
};

export type TypeOffreFormData = {
    slug: string;
    nom: string;
    description: string;
    unite: string;
    unite_label: string;
    quantite_entier: boolean;
    quantite_min: string;
    actif: boolean;
};
