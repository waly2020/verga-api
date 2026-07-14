import type { CommandeStatut } from './common';

export type ClientRow = {
    id: string;
    nom: string;
    prenom: string;
    email: string;
    telephone: string | null;
    ville: string | null;
    type: string;
    statut: string;
    commandes_count: number;
};

export type ClientDocument = {
    id: string;
    type_document: string;
    chemin: string;
    url: string;
    nom_original: string | null;
};

export type ClientDetail = {
    id: string;
    nom: string;
    prenom: string;
    email: string;
    telephone: string | null;
    adresse: string | null;
    ville: string | null;
    pays: string | null;
    type: string;
    statut: string;
    created_at: string;
    user: { id: number; name: string; email: string } | null;
    documents?: ClientDocument[];
};

export type ClientCommandeRow = {
    id: string;
    code: string;
    agence: { id: string; nom: string } | null;
    quantite: string;
    montant_total: string;
    statut: CommandeStatut | string;
    created_at: string;
};
