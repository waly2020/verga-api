import type { TypeOffreApi } from '@/types/models/type-offre';

export type QuantiteTypeOffre = Pick<TypeOffreApi, 'unite' | 'quantite_entier'>;

const UNIT_SYMBOLS: Record<string, string> = {
    kg: 'kg',
    m3: 'm³',
    tonne: 'tonne',
    conteneur: 'conteneur',
    camion: 'camion',
};

const UNIT_PLURALS: Record<string, string> = {
    tonne: 'tonnes',
    conteneur: 'conteneurs',
    camion: 'camions',
};

function formatNumber(value: number, entier: boolean): string {
    if (entier) {
        return String(Math.round(value));
    }

    return value.toLocaleString('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 3,
    });
}

function formatUnite(unite: string, qty: number): string {
    const base = UNIT_SYMBOLS[unite] ?? unite;

    if (Math.abs(qty) <= 1) {
        return base;
    }

    return UNIT_PLURALS[base] ?? UNIT_PLURALS[unite] ?? base;
}

export function formatQuantite(
    value: string | number | null | undefined,
    typeOffre?: QuantiteTypeOffre | null,
): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    const numeric = Number(value);

    if (Number.isNaN(numeric)) {
        return '—';
    }

    const formattedNumber = formatNumber(numeric, typeOffre?.quantite_entier ?? false);

    if (!typeOffre?.unite) {
        return formattedNumber;
    }

    return `${formattedNumber} ${formatUnite(typeOffre.unite, numeric)}`;
}
