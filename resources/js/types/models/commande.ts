import type { ColisApi, ColisRow } from './colis';
import type {
    AgenceDetail,
    AgenceSummary,
    ClientEmbed,
    ClientSummary,
    CommandeGuestContact,
    CommandeStatut,
} from './common';
import type { OffreInfo, OffreSummary } from './offre';
import type { QuantiteTypeOffre } from '@/lib/format-quantite';
import type { PaiementApi, PaiementInfo } from './paiement';

export type CommissionInfo = {
    id: string;
    montant: string;
    taux: string | null;
    created_at: string;
};

export type ReclamationRow = {
    id: string;
    objet: string;
    statut: string;
    created_at: string;
};

export type CommandeOffreEmbed = OffreSummary & {
    type_offre?: QuantiteTypeOffre | null;
};

export type CommandeRow = CommandeGuestContact & {
    id: string;
    code: string;
    client: ClientSummary | null;
    agence: AgenceSummary | null;
    offre?: CommandeOffreEmbed | null;
    quantite: string;
    montant_sous_total?: string | null;
    montant_commission_client?: string | null;
    montant_total: string;
    statut: CommandeStatut | string;
    created_at: string;
};

export type CommandeDetail = CommandeGuestContact & {
    id: string;
    code: string;
    quantite: string;
    montant_sous_total: string | null;
    montant_commission_client: string | null;
    montant_total: string;
    statut: CommandeStatut | string;
    created_at: string;
    updated_at: string;
    client: ClientEmbed | null;
    agence: AgenceDetail | null;
    offre: OffreInfo | null;
    paiement: PaiementInfo | null;
    paiements?: PaiementInfo[];
    commission: CommissionInfo | null;
    colis: ColisRow[];
    reclamations: ReclamationRow[];
};

export type CommandeApi = {
    id: string;
    code: string;
    quantite: string | number;
    montant_total: string | number;
    statut: string;
    nom?: string | null;
    prenom?: string | null;
    telephone?: string | null;
    created_at: string | null;
    agence?: AgenceSummary | null;
    offre?: { id: string; titre: string; type: string } | null;
    paiement?: PaiementApi | null;
    colis?: ColisApi[];
};
