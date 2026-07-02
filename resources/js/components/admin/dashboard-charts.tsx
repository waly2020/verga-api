import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Tooltip,
} from 'chart.js';
import { Bar, Doughnut } from 'react-chartjs-2';

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Tooltip, Legend);

const fmt = (n: number) => n.toLocaleString('fr-FR') + ' FCFA';

// ─── Bar horizontal : paiements ou commissions par agence ──────────────────

interface AgenceData {
    nom: string;
    total: number;
}

interface BarAgenceProps {
    data: AgenceData[];
    color: string;
    emptyLabel?: string;
}

export function BarAgence({ data, color, emptyLabel = 'Aucune donnée' }: BarAgenceProps) {
    if (data.length === 0) {
        return (
            <div className="flex h-48 items-center justify-center text-sm text-muted-foreground">
                {emptyLabel}
            </div>
        );
    }

    const chartData = {
        labels: data.map((d) => d.nom),
        datasets: [
            {
                data: data.map((d) => d.total),
                backgroundColor: color + 'cc',
                borderColor: color,
                borderWidth: 1,
                borderRadius: 4,
            },
        ],
    };

    return (
        <Bar
            data={chartData}
            options={{
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ' ' + fmt(ctx.parsed.x ?? 0),
                        },
                    },
                },
                scales: {
                    x: {
                        ticks: {
                            callback: (v) => Number(v).toLocaleString('fr-FR'),
                            maxTicksLimit: 5,
                        },
                        grid: { color: 'rgba(0,0,0,0.06)' },
                    },
                    y: {
                        ticks: { font: { size: 12 } },
                        grid: { display: false },
                    },
                },
            }}
        />
    );
}

// ─── Doughnut : commandes par statut ──────────────────────────────────────

const STATUT_CONFIG: Record<string, { label: string; color: string }> = {
    en_attente: { label: 'En attente', color: '#f59e0b' },
    réservée:   { label: 'Réservée',   color: '#3b82f6' },
    confirmée:  { label: 'Confirmée',  color: '#22c55e' },
    annulée:    { label: 'Annulée',    color: '#ef4444' },
};

const FALLBACK_COLORS = ['#3b82f6', '#a855f7', '#14b8a6', '#f97316'];

interface DoughnutStatutProps {
    data: Record<string, number>;
    total: number;
}

export function DoughnutStatut({ data, total }: DoughnutStatutProps) {
    const statuts = Object.keys(data);

    if (statuts.length === 0 || total === 0) {
        return (
            <div className="flex h-48 items-center justify-center text-sm text-muted-foreground">
                Aucune commande sur cette période
            </div>
        );
    }

    const labels  = statuts.map((s) => STATUT_CONFIG[s]?.label ?? s);
    const counts  = statuts.map((s) => data[s]);
    const colors  = statuts.map((s, i) => STATUT_CONFIG[s]?.color ?? FALLBACK_COLORS[i % FALLBACK_COLORS.length]);

    const chartData = {
        labels,
        datasets: [
            {
                data: counts,
                backgroundColor: colors.map((c) => c + 'cc'),
                borderColor: colors,
                borderWidth: 2,
                hoverOffset: 6,
            },
        ],
    };

    return (
        <Doughnut
            data={chartData}
            options={{
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 16, font: { size: 12 } },
                    },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                const pct = ((ctx.parsed / total) * 100).toFixed(1);

                                return ` ${ctx.parsed} commande${ctx.parsed > 1 ? 's' : ''} (${pct}%)`;
                            },
                        },
                    },
                },
            }}
        />
    );
}
