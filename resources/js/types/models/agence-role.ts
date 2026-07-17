export type AgenceRoleApi = {
    id: string;
    slug: string;
    nom: string;
    description: string | null;
    actif?: boolean;
    est_systeme?: boolean;
};

export type AgenceRoleRow = AgenceRoleApi & {
    actif: boolean;
    est_systeme: boolean;
    users_count: number;
    created_at: string;
};

export type AgenceRoleFormData = {
    slug: string;
    nom: string;
    description: string;
    actif: boolean;
};
