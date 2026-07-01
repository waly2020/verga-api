import type { AgenceDetail, AgenceSummary, ClientEmbed, ColisStatut } from './common';

export type ColisPhoto = {
    id: string;
    chemin: string;
    ordre: number;
};

export type ColisRow = {
    id: string;
    reference: string;
    description?: string | null;
    commande: { id: string; code: string } | null;
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
    commentaire: string | null;
    created_at: string;
    user: { id: number; name: string } | null;
};

export type ColisCommandeEmbed = {
    id: string;
    code: string;
    montant_total: string;
    statut: string;
    nom?: string | null;
    prenom?: string | null;
    telephone?: string | null;
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
    volume: string | null;
    statut: string;
    created_at: string | null;
    commande?: { id: string; code: string } | null;
    agence?: AgenceSummary | null;
    photos?: ColisPhoto[];
};
