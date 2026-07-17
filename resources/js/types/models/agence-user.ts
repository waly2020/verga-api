import type { AgenceRoleApi } from './agence-role';
import type { AgenceSummary } from './common';

export type AgenceUserRow = Record<string, unknown> & {
    id: number;
    name: string;
    email: string;
    telephone: string | null;
    statut: 'actif' | 'suspendu';
    est_proprietaire: boolean;
    agence_id: string;
    agence_role_id: string;
    agence: AgenceSummary;
    role: AgenceRoleApi;
    created_at: string;
};

export type AgenceUserFormData = {
    agence_id: string;
    agence_role_id: string;
    name: string;
    email: string;
    telephone: string;
    password: string;
    password_confirmation: string;
    statut: 'actif' | 'suspendu';
};
