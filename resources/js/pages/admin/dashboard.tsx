import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import {
    AlertCircle,
    Banknote,
    Building2,
    CreditCard,
    ShoppingCart,
    TrendingUp,
} from 'lucide-react';
import { BarAgence, DoughnutStatut } from '@/components/admin/dashboard-charts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { dashboard } from '@/routes/admin';

// ─── Types ────────────────────────────────────────────────────────────────

interface Stats {
    agences: number;
    commandes_total: number;
    solde_commissions: number;
    solde_paiements: number;
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

const fmt     = (n: number) => n.toLocaleString('fr-FR');
const fmtFcfa = (n: number) => `${fmt(n)} FCFA`;

const PERIODES = [
    { value: 'mois',         label: 'Ce mois' },
    { value: 'mois_dernier', label: 'Mois dernier' },
    { value: 'trimestre',    label: 'Ce trimestre' },
    { value: 'semestre',     label: '6 derniers mois' },
    { value: 'annee',        label: 'Cette année' },
    { value: 'tout',         label: 'Tout' },
];

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

    const kpis = [
        {
            title: 'Agences actives',
            value: fmt(stats.agences),
            description: 'Partenaires sur la plateforme',
            icon: Building2,
            color: 'text-blue-500',
        },
        {
            title: 'Commandes',
            value: fmt(stats.commandes_total),
            description: 'Période sélectionnée',
            icon: ShoppingCart,
            color: 'text-violet-500',
        },
        {
            title: 'Solde paiements',
            value: fmtFcfa(stats.solde_paiements),
            description: 'Paiements validés',
            icon: CreditCard,
            color: 'text-emerald-500',
        },
        {
            title: 'Solde commissions',
            value: fmtFcfa(stats.solde_commissions),
            description: 'Commissions VERGA',
            icon: TrendingUp,
            color: 'text-amber-500',
        },
        {
            title: 'Réclamations',
            value: fmt(stats.reclamations_ouvertes),
            description: 'En attente de traitement',
            icon: AlertCircle,
            color: 'text-red-500',
        },
        {
            title: 'Reversements',
            value: fmtFcfa(stats.reversements_attente),
            description: 'En attente de versement',
            icon: Banknote,
            color: 'text-orange-500',
        },
    ];

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

                {/* KPI cards */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    {kpis.map((kpi) => (
                        <Card key={kpi.title}>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-xs font-medium text-muted-foreground">
                                    {kpi.title}
                                </CardTitle>
                                <kpi.icon className={`h-4 w-4 ${kpi.color}`} />
                            </CardHeader>
                            <CardContent>
                                <div className="truncate text-xl font-bold">{kpi.value}</div>
                                <p className="mt-0.5 text-xs text-muted-foreground">{kpi.description}</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Solde récapitulatif */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-sm font-semibold">Récapitulatif financier</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 sm:grid-cols-3">
                            <div className="flex flex-col gap-1 rounded-lg bg-emerald-50 p-4 dark:bg-emerald-950/30">
                                <span className="text-xs font-medium text-emerald-700 dark:text-emerald-400">
                                    Total paiements validés
                                </span>
                                <span className="text-2xl font-bold text-emerald-700 dark:text-emerald-400">
                                    {fmtFcfa(stats.solde_paiements)}
                                </span>
                            </div>
                            <div className="flex flex-col gap-1 rounded-lg bg-amber-50 p-4 dark:bg-amber-950/30">
                                <span className="text-xs font-medium text-amber-700 dark:text-amber-400">
                                    Total commissions VERGA
                                </span>
                                <span className="text-2xl font-bold text-amber-700 dark:text-amber-400">
                                    {fmtFcfa(stats.solde_commissions)}
                                </span>
                            </div>
                            <div className="flex flex-col gap-1 rounded-lg bg-orange-50 p-4 dark:bg-orange-950/30">
                                <span className="text-xs font-medium text-orange-700 dark:text-orange-400">
                                    Reversements en attente
                                </span>
                                <span className="text-2xl font-bold text-orange-700 dark:text-orange-400">
                                    {fmtFcfa(stats.reversements_attente)}
                                </span>
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
                        <CardTitle className="text-sm font-semibold">Commissions par agence</CardTitle>
                        <p className="text-xs text-muted-foreground">Top 10 — commissions générées</p>
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
