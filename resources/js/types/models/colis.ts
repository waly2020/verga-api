import type { QuantiteTypeOffre } from '@/lib/format-quantite';
import type { AgenceDetail, AgenceSummary, ClientEmbed, ColisStatut } from './common';

export type ColisPhoto = {
    id: string;
    chemin: string;
    url: string;
    ordre: number;
};

export type ColisCommandeRowEmbed = {
    id: string;
    code: string;
    quantite?: string;
    offre?: { type_offre?: QuantiteTypeOffre | null } | null;
};

export type ColisRow = {
    id: string;
    reference: string;
    description?: string | null;
    commande: ColisCommandeRowEmbed | null;
    agence?: AgenceSummary | null;
    poids?: string | null;
    volume?: string | null;
    statut: ColisStatut | string;
    created_at?: string;
    photos?: ColisPhoto[];
};

export type HistoriqueColisItem = {
    id: string;
    statut: string;
    date_statut: string | null;
    commentaire: string | null;
    created_at: string;
    user: { id: number; name: string } | null;
};

export type ColisCommandeEmbed = {
    id: string;
    code: string;
    quantite?: string;
    montant_total: string;
    statut: string;
    nom?: string | null;
    prenom?: string | null;
    telephone?: string | null;
    offre?: { type_offre?: QuantiteTypeOffre | null } | null;
    client: ClientEmbed | null;
};

export type ColisDetail = {
    id: string;
    reference: string;
    description: string | null;
    poids: string | null;
    volume: string | null;
    statut: ColisStatut | string;
    created_at: string;
    updated_at: string;
    agence: AgenceDetail | null;
    commande: ColisCommandeEmbed | null;
    historique: HistoriqueColisItem[];
    photos?: ColisPhoto[];
};

export type ColisApi = {
    id: string;
    reference: string;
    description?: string | null;
    poids: string | null;
    poids_label?: string | null;
    volume: string | null;
    quantite_label?: string | null;
    statut: string;
    created_at: string | null;
    commande?: { id: string; code: string; quantite?: string; quantite_label?: string | null } | null;
    agence?: AgenceSummary | null;
    photos?: ColisPhoto[];
};
