import { Badge } from '@/components/ui/badge';

const STATUS_MAP: Record<string, { label: string; className: string }> = {
    // Agence
    actif:       { label: 'Actif',       className: 'bg-green-100 text-green-800 border-green-200 hover:bg-green-100' },
    bloqué:      { label: 'Bloqué',      className: 'bg-red-100 text-red-800 border-red-200 hover:bg-red-100' },
    suspendu:    { label: 'Suspendu',    className: 'bg-orange-100 text-orange-800 border-orange-200 hover:bg-orange-100' },
    // Commande
    en_attente:  { label: 'En attente',  className: 'bg-yellow-100 text-yellow-800 border-yellow-200 hover:bg-yellow-100' },
    confirmée:   { label: 'Confirmée',   className: 'bg-green-100 text-green-800 border-green-200 hover:bg-green-100' },
    annulée:     { label: 'Annulée',     className: 'bg-red-100 text-red-800 border-red-200 hover:bg-red-100' },
    // Paiement
    validé:      { label: 'Validé',      className: 'bg-green-100 text-green-800 border-green-200 hover:bg-green-100' },
    remboursé:   { label: 'Remboursé',   className: 'bg-blue-100 text-blue-800 border-blue-200 hover:bg-blue-100' },
    échec:       { label: 'Échec',       className: 'bg-red-100 text-red-800 border-red-200 hover:bg-red-100' },
    // Colis
    déposé:      { label: 'Déposé',      className: 'bg-gray-100 text-gray-700 border-gray-200 hover:bg-gray-100' },
    en_transit:  { label: 'En transit',  className: 'bg-blue-100 text-blue-800 border-blue-200 hover:bg-blue-100' },
    arrivé:      { label: 'Arrivé',      className: 'bg-green-100 text-green-800 border-green-200 hover:bg-green-100' },
    récupéré:    { label: 'Récupéré',    className: 'bg-emerald-100 text-emerald-800 border-emerald-200 hover:bg-emerald-100' },
    // Reversement
    effectué:    { label: 'Effectué',    className: 'bg-green-100 text-green-800 border-green-200 hover:bg-green-100' },
    // Réclamation
    ouverte:     { label: 'Ouverte',     className: 'bg-red-100 text-red-800 border-red-200 hover:bg-red-100' },
    en_cours:    { label: 'En cours',    className: 'bg-yellow-100 text-yellow-800 border-yellow-200 hover:bg-yellow-100' },
    résolue:     { label: 'Résolue',     className: 'bg-green-100 text-green-800 border-green-200 hover:bg-green-100' },
    fermée:      { label: 'Fermée',      className: 'bg-gray-100 text-gray-600 border-gray-200 hover:bg-gray-100' },
};

export function StatusBadge({ status }: { status: string }) {
    const config = STATUS_MAP[status] ?? { label: status, className: '' };
    return (
        <Badge variant="outline" className={config.className}>
            {config.label}
        </Badge>
    );
}
