import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import {
    AlertCircle,
    Building2,
    CreditCard,
    ShoppingCart,
} from 'lucide-react';
import { BarAgence, DoughnutStatut } from '@/components/admin/dashboard-charts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { dashboard } from '@/routes/admin';

// ─── Types ────────────────────────────────────────────────────────────────

interface Stats {
    agences: number;
    commandes_total: number;
    solde_paiements: number;
    solde_sous_total: number;
    solde_commissions_client: number;
    solde_commissions_agence: number;
    reclamations_ouvertes: number;
    reversements_attente: number;
}

interface AgenceRow {
    nom: string;
    total: number;
}

interface Props {
    stats: Stats;
    commandes_par_statut: Record<string, number>;
    paiements_par_agence: AgenceRow[];
    commissions_par_agence: AgenceRow[];
    periode: string;
}

// ─── Helpers ──────────────────────────────────────────────────────────────

const fmt = (n: number) => n.toLocaleString('fr-FR');
const fmtFcfa = (n: number) => `${fmt(n)} FCFA`;

const PERIODES = [
    { value: 'mois', label: 'Ce mois' },
    { value: 'mois_dernier', label: 'Mois dernier' },
    { value: 'trimestre', label: 'Ce trimestre' },
    { value: 'semestre', label: '6 derniers mois' },
    { value: 'annee', label: 'Cette année' },
    { value: 'tout', label: 'Tout' },
];

// ─── Sous-composants ──────────────────────────────────────────────────────

function OperationalKpi({
    title,
    value,
    description,
    icon: Icon,
    iconClass,
}: {
    title: string;
    value: string;
    description: string;
    icon: React.ComponentType<{ className?: string }>;
    iconClass: string;
}) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium text-muted-foreground">{title}</CardTitle>
                <Icon className={`h-4 w-4 shrink-0 ${iconClass}`} />
            </CardHeader>
            <CardContent>
                <div className="text-3xl font-bold tabular-nums">{value}</div>
                <p className="mt-1 text-xs text-muted-foreground">{description}</p>
            </CardContent>
        </Card>
    );
}

function FinancialLine({
    label,
    amount,
    hint,
    emphasis = false,
    muted = false,
}: {
    label: string;
    amount: number;
    hint?: string;
    emphasis?: boolean;
    muted?: boolean;
}) {
    return (
        <div
            className={`flex flex-col gap-0.5 border-b border-border/60 py-3 last:border-0 sm:flex-row sm:items-baseline sm:justify-between sm:gap-4 ${
                muted ? 'opacity-80' : ''
            }`}
        >
            <div className="min-w-0">
                <p className={`text-sm ${emphasis ? 'font-semibold' : 'font-medium text-muted-foreground'}`}>
                    {label}
                </p>
                {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
            </div>
            <p
                className={`shrink-0 text-right font-mono tabular-nums tracking-tight ${
                    emphasis ? 'text-xl font-bold sm:text-2xl' : 'text-base font-semibold sm:text-lg'
                }`}
            >
                {fmtFcfa(amount)}
            </p>
        </div>
    );
}

// ─── Composant ────────────────────────────────────────────────────────────

export default function AdminDashboard({
    stats,
    commandes_par_statut,
    paiements_par_agence,
    commissions_par_agence,
    periode,
}: Props) {
    const setPeriode = (v: string) =>
        router.get(dashboard().url, { periode: v }, { preserveState: true, replace: true });

    const totalCommandes = Object.values(commandes_par_statut).reduce((a, b) => a + b, 0);
    const revenuVerga = stats.solde_commissions_client + stats.solde_commissions_agence;

    return (
        <>
            <Head title="Tableau de bord — Admin" />

            <div className="flex flex-1 flex-col gap-6 p-6">

                {/* En-tête + filtre période */}
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Tableau de bord</h1>
                        <p className="text-sm text-muted-foreground">Vue d'ensemble de la plateforme VERGA</p>
                    </div>
                    <Select value={periode} onValueChange={setPeriode}>
                        <SelectTrigger className="w-48">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {PERIODES.map((p) => (
                                <SelectItem key={p.value} value={p.value}>
                                    {p.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* Activité — compteurs uniquement */}
                <div className="grid gap-4 sm:grid-cols-3">
                    <OperationalKpi
                        title="Agences actives"
                        value={fmt(stats.agences)}
                        description="Partenaires sur la plateforme"
                        icon={Building2}
                        iconClass="text-blue-500"
                    />
                    <OperationalKpi
                        title="Commandes"
                        value={fmt(stats.commandes_total)}
                        description="Sur la période sélectionnée"
                        icon={ShoppingCart}
                        iconClass="text-violet-500"
                    />
                    <OperationalKpi
                        title="Réclamations ouvertes"
                        value={fmt(stats.reclamations_ouvertes)}
                        description="En attente de traitement"
                        icon={AlertCircle}
                        iconClass="text-red-500"
                    />
                </div>

                {/* Finances — une seule carte, montants en liste */}
                <Card>
                    <CardHeader className="pb-2">
                        <div className="flex items-center gap-2">
                            <CreditCard className="h-4 w-4 text-muted-foreground" />
                            <CardTitle className="text-sm font-semibold">Récapitulatif financier</CardTitle>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            Paiements validés sur la période
                        </p>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-6 lg:grid-cols-5">
                            {/* Montant principal — occupe 2 colonnes */}
                            <div className="flex flex-col justify-center rounded-xl bg-emerald-50 p-6 dark:bg-emerald-950/30 lg:col-span-2">
                                <p className="text-sm font-medium text-emerald-700 dark:text-emerald-400">
                                    Total encaissé
                                </p>
                                <p className="mt-2 break-words font-mono text-3xl font-bold tabular-nums tracking-tight text-emerald-700 sm:text-4xl dark:text-emerald-400">
                                    {fmt(stats.solde_paiements)}
                                </p>
                                <p className="mt-1 text-sm font-medium text-emerald-600/80 dark:text-emerald-500/80">
                                    FCFA
                                </p>
                                <p className="mt-3 text-xs text-emerald-700/70 dark:text-emerald-500/70">
                                    Transport + commissions client
                                </p>
                            </div>

                            {/* Détail — 3 colonnes */}
                            <div className="lg:col-span-3">
                                <FinancialLine
                                    label="Sous-total transport"
                                    hint="Part agence (brut)"
                                    amount={stats.solde_sous_total}
                                />
                                <FinancialLine
                                    label="Commissions VERGA (client)"
                                    hint="Prélevées sur le client"
                                    amount={stats.solde_commissions_client}
                                />
                                <FinancialLine
                                    label="Commissions agence"
                                    hint="Prélevées sur le transport agence"
                                    amount={stats.solde_commissions_agence}
                                />
                                <FinancialLine
                                    label="Revenu VERGA total"
                                    hint="Commissions client + agence"
                                    amount={revenuVerga}
                                    emphasis
                                />
                                <FinancialLine
                                    label="Reversements en attente"
                                    hint="À verser aux agences"
                                    amount={stats.reversements_attente}
                                    muted
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Charts row 1 */}
                <div className="grid gap-4 lg:grid-cols-5">

                    {/* Paiements par agence */}
                    <Card className="lg:col-span-3">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-semibold">Paiements par agence</CardTitle>
                            <p className="text-xs text-muted-foreground">Top 10 — montants validés</p>
                        </CardHeader>
                        <CardContent>
                            <div style={{ height: Math.max(160, paiements_par_agence.length * 40) }}>
                                <BarAgence
                                    data={paiements_par_agence}
                                    color="#3b82f6"
                                    emptyLabel="Aucun paiement validé sur cette période"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Commandes par statut */}
                    <Card className="lg:col-span-2">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-semibold">Commandes par statut</CardTitle>
                            <p className="text-xs text-muted-foreground">
                                {totalCommandes} commande{totalCommandes !== 1 ? 's' : ''} au total
                            </p>
                        </CardHeader>
                        <CardContent>
                            <div className="h-52">
                                <DoughnutStatut
                                    data={commandes_par_statut}
                                    total={totalCommandes}
                                />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Commissions par agence */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-sm font-semibold">Commissions VERGA par agence</CardTitle>
                        <p className="text-xs text-muted-foreground">Top 10 — commissions client prélevées</p>
                    </CardHeader>
                    <CardContent>
                        <div style={{ height: Math.max(160, commissions_par_agence.length * 40) }}>
                            <BarAgence
                                data={commissions_par_agence}
                                color="#22c55e"
                                emptyLabel="Aucune commission sur cette période"
                            />
                        </div>
                    </CardContent>
                </Card>

            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [
        { title: 'Administration', href: dashboard().url },
        { title: 'Tableau de bord', href: dashboard().url },
    ],
};
