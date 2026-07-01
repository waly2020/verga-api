export type OffreType = 'particulier' | 'metre_cube' | 'conteneur';

export type OffreStatut = 'active' | 'inactive';

export type CommandeStatut = 'en_attente' | 'confirmée' | 'annulée';

export type PaiementStatut = 'en_attente' | 'validé' | 'remboursé' | 'échec';

export type ColisStatut = 'déposé' | 'en_transit' | 'arrivé' | 'récupéré';

export type AgenceSummary = {
    id: string;
    nom: string;
};

export type AgenceDetail = AgenceSummary & {
    email?: string;
    ville?: string | null;
};

export type ClientSummary = {
    id: string;
    nom: string;
    prenom: string;
};

export type ClientEmbed = ClientSummary & {
    email: string;
    telephone?: string | null;
};

export type CommandeGuestContact = {
    nom: string | null;
    prenom: string | null;
    telephone: string | null;
};
