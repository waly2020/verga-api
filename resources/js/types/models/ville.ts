export type VilleApi = {
    id: string;
    pays: string;
    ville: string;
    code: string;
    label?: string;
    actif: boolean;
};

export type VilleSummary = Pick<VilleApi, 'id' | 'pays' | 'ville' | 'code' | 'actif'>;

export type VilleRow = VilleApi & {
    destinations_depart_count: number;
    destinations_arrivee_count: number;
    created_at: string;
};

export type VilleFormData = {
    pays: string;
    ville: string;
    code: string;
    actif: boolean;
};

export function villeLabel(localite: Pick<VilleApi, 'ville' | 'pays' | 'code'>): string {
    return `${localite.ville} (${localite.pays}) · ${localite.code}`;
}
