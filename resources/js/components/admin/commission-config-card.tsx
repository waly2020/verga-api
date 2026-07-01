import { useForm } from '@inertiajs/react';
import { Building2, Loader2, Percent, UserCircle } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

export type CommissionConfig = {
    id: string;
    destinataire: 'client' | 'agence';
    type: 'fixe' | 'pourcentage';
    valeur: string;
    actif: boolean;
    libelle: string | null;
};

type FormData = {
    type: 'fixe' | 'pourcentage';
    valeur: string;
    actif: boolean;
    libelle: string;
};

interface Props {
    config: CommissionConfig;
    updateUrl: string;
}

const EXEMPLE_MONTANT = 10_000;

function fmtFcfa(value: number): string {
    return `${value.toLocaleString('fr-FR')} FCFA`;
}

function previewMontant(type: FormData['type'], valeur: string): number {
    const numeric = Number(valeur);

    if (Number.isNaN(numeric) || numeric < 0) {
        return 0;
    }

    if (type === 'pourcentage') {
        return Math.round(EXEMPLE_MONTANT * (numeric / 100) * 100) / 100;
    }

    return numeric;
}

export function CommissionConfigCard({ config, updateUrl }: Props) {
    const isClient = config.destinataire === 'client';
    const Icon = isClient ? UserCircle : Building2;

    const { data, setData, patch, processing, errors } = useForm<FormData>({
        type: config.type,
        valeur: String(config.valeur),
        actif: config.actif,
        libelle: config.libelle ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        patch(updateUrl, { preserveScroll: true });
    };

    const preview = previewMontant(data.type, data.valeur);

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
                                ? 'Prélevée sur chaque paiement client. S\'applique à tous les clients.'
                                : 'Prélevée sur chaque paiement au profit de VERGA. S\'applique à toutes les agences.'}
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
                        <Label htmlFor={`${config.destinataire}-libelle`}>Libellé</Label>
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
                                onValueChange={(v) => setData('type', v as FormData['type'])}
                            >
                                <SelectTrigger id={`${config.destinataire}-type`}>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="pourcentage">Pourcentage (%)</SelectItem>
                                    <SelectItem value="fixe">Montant fixe (FCFA)</SelectItem>
                                </SelectContent>
                            </Select>
                            {errors.type && <p className="text-xs text-destructive">{errors.type}</p>}
                        </div>

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
                    </div>

                    <div className="rounded-lg border bg-muted/40 px-4 py-3 text-sm">
                        <p className="font-medium text-foreground">Aperçu sur un paiement de {fmtFcfa(EXEMPLE_MONTANT)}</p>
                        <p className="mt-1 text-muted-foreground">
                            Commission VERGA :{' '}
                            <span className="font-medium tabular-nums text-foreground">{fmtFcfa(preview)}</span>
                            {data.type === 'pourcentage' && data.valeur ? ` (${data.valeur} %)` : null}
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
