import { useForm } from '@inertiajs/react';
import { Building2, Loader2, Percent, PlusCircle, Trash2, UserCircle } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

export type CommissionType = 'fixe' | 'pourcentage' | 'grille';

export type CommissionPalierForm = {
    montant_min: string;
    montant_max: string;
    frais: string;
    libelle: string;
};

export type CommissionConfig = {
    id: string;
    destinataire: 'client' | 'agence';
    type: CommissionType;
    valeur: string;
    actif: boolean;
    libelle: string | null;
    paliers?: Array<{
        id: string;
        montant_min: string | number;
        montant_max: string | number | null;
        frais: string | number;
        libelle: string | null;
    }>;
};

type FormData = {
    type: CommissionType;
    valeur: string;
    actif: boolean;
    libelle: string;
    paliers: CommissionPalierForm[] | null;
};

interface Props {
    config: CommissionConfig;
    updateUrl: string;
}

const EXEMPLE_MONTANT = 10_000;

function fmtFcfa(value: number): string {
    return `${value.toLocaleString('fr-FR')} FCFA`;
}

function emptyPalier(min = '0'): CommissionPalierForm {
    return { montant_min: min, montant_max: '', frais: '', libelle: '' };
}

function toPaliers(config: CommissionConfig): CommissionPalierForm[] | null {
    if (config.type !== 'grille') {
        return null;
    }

    if (!config.paliers?.length) {
        return [emptyPalier()];
    }

    return config.paliers.map((palier) => ({
        montant_min: String(palier.montant_min),
        montant_max: palier.montant_max == null ? '' : String(palier.montant_max),
        frais: String(palier.frais),
        libelle: palier.libelle ?? '',
    }));
}

function previewMontant(data: FormData): { montant: number; libelle: string | null } {
    if (data.type === 'grille') {
        const paliers = [...(data.paliers ?? [])]
            .map((palier) => ({
                min: Number(palier.montant_min),
                max: palier.montant_max === '' ? null : Number(palier.montant_max),
                frais: Number(palier.frais),
                libelle: palier.libelle.trim() || null,
            }))
            .filter((palier) => !Number.isNaN(palier.min) && !Number.isNaN(palier.frais))
            .sort((a, b) => a.min - b.min);

        const match = paliers.find(
            (palier) =>
                EXEMPLE_MONTANT >= palier.min && (palier.max === null || EXEMPLE_MONTANT <= palier.max),
        );

        return {
            montant: match?.frais ?? 0,
            libelle: match?.libelle ?? null,
        };
    }

    const numeric = Number(data.valeur);

    if (Number.isNaN(numeric) || numeric < 0) {
        return { montant: 0, libelle: data.libelle.trim() || null };
    }

    if (data.type === 'pourcentage') {
        return {
            montant: Math.round(EXEMPLE_MONTANT * (numeric / 100) * 100) / 100,
            libelle: data.libelle.trim() || null,
        };
    }

    return { montant: numeric, libelle: data.libelle.trim() || null };
}

function palierError(
    errors: Record<string, string>,
    index: number,
    field: 'montant_min' | 'montant_max' | 'frais' | 'libelle',
): string | undefined {
    return errors[`paliers.${index}.${field}`];
}

export function CommissionConfigCard({ config, updateUrl }: Props) {
    const isClient = config.destinataire === 'client';
    const Icon = isClient ? UserCircle : Building2;

    const { data, setData, patch, processing, errors } = useForm<FormData>({
        type: config.type === 'grille' && !isClient ? 'pourcentage' : config.type,
        valeur: String(config.valeur),
        actif: config.actif,
        libelle: config.libelle ?? '',
        paliers: toPaliers(config),
    });

    const setType = (type: CommissionType) => {
        setData({
            ...data,
            type,
            paliers: type === 'grille' ? (data.paliers?.length ? data.paliers : [emptyPalier()]) : null,
        });
    };

    const updatePalier = (index: number, field: keyof CommissionPalierForm, value: string) => {
        if (!data.paliers) {
            return;
        }

        const next = data.paliers.map((palier, i) =>
            i === index ? { ...palier, [field]: value } : palier,
        );
        setData('paliers', next);
    };

    const addPalier = () => {
        const last = data.paliers?.[data.paliers.length - 1];
        const nextMin =
            last && last.montant_max !== '' && !Number.isNaN(Number(last.montant_max))
                ? String(Number(last.montant_max) + 1)
                : '';

        setData('paliers', [...(data.paliers ?? []), emptyPalier(nextMin)]);
    };

    const removePalier = (index: number) => {
        if (!data.paliers || data.paliers.length <= 1) {
            return;
        }

        setData(
            'paliers',
            data.paliers.filter((_, i) => i !== index),
        );
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        patch(updateUrl, { preserveScroll: true });
    };

    const preview = previewMontant(data);

    return (
        <Card>
            <CardHeader className="pb-4">
                <div className="flex items-start justify-between gap-3">
                    <div>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Icon className="h-4 w-4 text-primary" />
                            Commission {isClient ? 'clients' : 'agences'}
                        </CardTitle>
                        <CardDescription className="mt-1.5">
                            {isClient
                                ? 'Appliquée sur le sous-total de chaque versement client, selon la configuration active.'
                                : 'Prélevée sur chaque paiement au profit de VERGA. S’applique à toutes les agences.'}
                        </CardDescription>
                    </div>
                    <Badge variant={data.actif ? 'default' : 'secondary'}>
                        {data.actif ? 'Active' : 'Inactive'}
                    </Badge>
                </div>
            </CardHeader>

            <CardContent>
                <form onSubmit={submit} className="space-y-5">
                    <div className="space-y-1.5">
                        <Label htmlFor={`${config.destinataire}-libelle`}>Libellé général</Label>
                        <Input
                            id={`${config.destinataire}-libelle`}
                            value={data.libelle}
                            onChange={(e) => setData('libelle', e.target.value)}
                            placeholder={isClient ? 'Commission globale clients' : 'Commission globale agences'}
                        />
                        {errors.libelle && <p className="text-xs text-destructive">{errors.libelle}</p>}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor={`${config.destinataire}-type`}>
                                Type de calcul <span className="text-destructive">*</span>
                            </Label>
                            <Select
                                value={data.type}
                                onValueChange={(v) => setType(v as CommissionType)}
                            >
                                <SelectTrigger id={`${config.destinataire}-type`}>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="pourcentage">Pourcentage (%)</SelectItem>
                                    <SelectItem value="fixe">Montant fixe (FCFA)</SelectItem>
                                    {isClient && (
                                        <SelectItem value="grille">Grille tarifaire</SelectItem>
                                    )}
                                </SelectContent>
                            </Select>
                            {errors.type && <p className="text-xs text-destructive">{errors.type}</p>}
                        </div>

                        {data.type !== 'grille' && (
                            <div className="space-y-1.5">
                                <Label htmlFor={`${config.destinataire}-valeur`}>
                                    Valeur <span className="text-destructive">*</span>
                                </Label>
                                <div className="relative">
                                    <Input
                                        id={`${config.destinataire}-valeur`}
                                        type="number"
                                        min={0}
                                        max={data.type === 'pourcentage' ? 100 : undefined}
                                        step={data.type === 'pourcentage' ? '0.01' : '1'}
                                        value={data.valeur}
                                        onChange={(e) => setData('valeur', e.target.value)}
                                        className={data.type === 'pourcentage' ? 'pr-9' : undefined}
                                    />
                                    {data.type === 'pourcentage' && (
                                        <Percent className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    )}
                                </div>
                                {errors.valeur && <p className="text-xs text-destructive">{errors.valeur}</p>}
                                <p className="text-xs text-muted-foreground">
                                    {data.type === 'pourcentage'
                                        ? 'Pourcentage appliqué sur le montant du paiement (max. 100 %).'
                                        : 'Montant fixe prélevé à chaque paiement, quel que soit le montant.'}
                                </p>
                            </div>
                        )}
                    </div>

                    {data.type === 'grille' && data.paliers && (
                        <div className="space-y-3">
                            <div>
                                <p className="text-sm font-medium">Tranches selon le montant payé</p>
                                <p className="text-xs text-muted-foreground">
                                    Au paiement, le sous-total du versement choisit la tranche. Le
                                    libellé de chaque ligne est optionnel. La dernière tranche reste
                                    ouverte (max vide).
                                </p>
                            </div>
                            {errors.paliers && <p className="text-xs text-destructive">{errors.paliers}</p>}

                            {data.paliers.map((palier, index) => {
                                const isLast = index === data.paliers!.length - 1;

                                return (
                                    <div
                                        key={index}
                                        className="grid gap-3 rounded-lg border p-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_1fr_auto]"
                                    >
                                        <div className="space-y-1.5">
                                            <Label htmlFor={`${config.destinataire}-palier-min-${index}`}>
                                                De (FCFA)
                                            </Label>
                                            <Input
                                                id={`${config.destinataire}-palier-min-${index}`}
                                                type="number"
                                                min="0"
                                                step="1"
                                                value={palier.montant_min}
                                                onChange={(e) =>
                                                    updatePalier(index, 'montant_min', e.target.value)
                                                }
                                            />
                                            {palierError(errors, index, 'montant_min') && (
                                                <p className="text-xs text-destructive">
                                                    {palierError(errors, index, 'montant_min')}
                                                </p>
                                            )}
                                        </div>
                                        <div className="space-y-1.5">
                                            <Label htmlFor={`${config.destinataire}-palier-max-${index}`}>
                                                À (FCFA)
                                            </Label>
                                            <Input
                                                id={`${config.destinataire}-palier-max-${index}`}
                                                type="number"
                                                min="0"
                                                step="1"
                                                value={isLast ? '' : palier.montant_max}
                                                placeholder={isLast ? 'et plus' : ''}
                                                disabled={isLast}
                                                readOnly={isLast}
                                                onChange={(e) =>
                                                    updatePalier(index, 'montant_max', e.target.value)
                                                }
                                            />
                                            {palierError(errors, index, 'montant_max') && (
                                                <p className="text-xs text-destructive">
                                                    {palierError(errors, index, 'montant_max')}
                                                </p>
                                            )}
                                        </div>
                                        <div className="space-y-1.5">
                                            <Label htmlFor={`${config.destinataire}-palier-frais-${index}`}>
                                                Frais
                                            </Label>
                                            <Input
                                                id={`${config.destinataire}-palier-frais-${index}`}
                                                type="number"
                                                min="0"
                                                step="1"
                                                value={palier.frais}
                                                onChange={(e) =>
                                                    updatePalier(index, 'frais', e.target.value)
                                                }
                                            />
                                            {palierError(errors, index, 'frais') && (
                                                <p className="text-xs text-destructive">
                                                    {palierError(errors, index, 'frais')}
                                                </p>
                                            )}
                                        </div>
                                        <div className="space-y-1.5">
                                            <Label htmlFor={`${config.destinataire}-palier-libelle-${index}`}>
                                                Message client
                                            </Label>
                                            <Input
                                                id={`${config.destinataire}-palier-libelle-${index}`}
                                                value={palier.libelle}
                                                onChange={(e) =>
                                                    updatePalier(index, 'libelle', e.target.value)
                                                }
                                                placeholder="Optionnel"
                                            />
                                            {palierError(errors, index, 'libelle') && (
                                                <p className="text-xs text-destructive">
                                                    {palierError(errors, index, 'libelle')}
                                                </p>
                                            )}
                                        </div>
                                        <div className="flex items-end">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="icon"
                                                disabled={data.paliers!.length <= 1}
                                                onClick={() => removePalier(index)}
                                                aria-label="Supprimer cette tranche"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                );
                            })}
                            <Button type="button" variant="outline" size="sm" onClick={addPalier}>
                                <PlusCircle className="mr-2 h-4 w-4" />
                                Ajouter une tranche
                            </Button>
                        </div>
                    )}

                    <div className="rounded-lg border bg-muted/40 px-4 py-3 text-sm">
                        <p className="font-medium text-foreground">
                            Aperçu sur un paiement de {fmtFcfa(EXEMPLE_MONTANT)}
                        </p>
                        <p className="mt-1 text-muted-foreground">
                            Commission VERGA :{' '}
                            <span className="font-medium tabular-nums text-foreground">
                                {fmtFcfa(preview.montant)}
                            </span>
                            {data.type === 'pourcentage' && data.valeur ? ` (${data.valeur} %)` : null}
                            {preview.libelle ? ` — ${preview.libelle}` : null}
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id={`${config.destinataire}-actif`}
                            checked={data.actif}
                            onCheckedChange={(checked) => setData('actif', checked === true)}
                        />
                        <Label htmlFor={`${config.destinataire}-actif`} className="cursor-pointer font-normal">
                            Activer cette commission
                        </Label>
                    </div>

                    <Button type="submit" disabled={processing}>
                        {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        Enregistrer
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}
