import type { VilleSummary } from './ville';
import { villeLabel } from './ville';

export type DestinationApi = {
    id: string;
    ville_depart_id: string;
    ville_arrivee_id: string;
    ville_depart: VilleSummary | null;
    ville_arrivee: VilleSummary | null;
    label?: string;
    montant: number | null;
    commission_pourcentage: number | null;
    appliquer_configuration: boolean;
    actif?: boolean;
};

export type DestinationSummary = Pick<
    DestinationApi,
    | 'id'
    | 'montant'
    | 'commission_pourcentage'
    | 'appliquer_configuration'
    | 'ville_depart'
    | 'ville_arrivee'
>;

export type DestinationRow = DestinationApi & {
    actif: boolean;
    offres_count: number;
    created_at: string;
};

export type DestinationFormData = {
    ville_depart_id: string;
    ville_arrivee_id: string;
    appliquer_configuration: boolean;
    montant: string;
    commission_pourcentage: string;
    actif: boolean;
};

type DestinationVilles = {
    ville_depart?: VilleSummary | null;
    ville_arrivee?: VilleSummary | null;
    villeDepart?: VilleSummary | null;
    villeArrivee?: VilleSummary | null;
};

export function destinationVille(
    destination: DestinationVilles,
    side: 'depart' | 'arrivee',
): VilleSummary | null {
    if (side === 'depart') {
        return destination.ville_depart ?? destination.villeDepart ?? null;
    }

    return destination.ville_arrivee ?? destination.villeArrivee ?? null;
}

export function destinationTrajetLabel(destination: DestinationVilles): string {
    const depart = destinationVille(destination, 'depart');
    const arrivee = destinationVille(destination, 'arrivee');

    return `${depart ? villeLabel(depart) : '—'} → ${arrivee ? villeLabel(arrivee) : '—'}`;
}
