import { Head, useForm } from '@inertiajs/react';
import { Loader2, Megaphone, Percent } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import admin from '@/routes/admin';

type Config = {
    id: string;
    prix_par_jour: string | number;
    type_frais: 'fixe' | 'pourcentage';
    valeur_frais: string | number;
    actif: boolean;
    libelle: string | null;
};

type FormData = {
    prix_par_jour: string;
    type_frais: 'fixe' | 'pourcentage';
    valeur_frais: string;
    actif: boolean;
    libelle: string;
};

interface Props {
    configuration: Config;
}

function fmtFcfa(value: number): string {
    return `${value.toLocaleString('fr-FR')} FCFA`;
}

export default function PubliciteConfiguration({ configuration }: Props) {
    const { data, setData, patch, processing, errors } = useForm<FormData>({
        prix_par_jour: String(configuration.prix_par_jour),
        type_frais: configuration.type_frais,
        valeur_frais: String(configuration.valeur_frais),
        actif: configuration.actif,
        libelle: configuration.libelle ?? '',
    });

    const joursExemple = 7;
    const sousTotal = Math.round(Number(data.prix_par_jour) * joursExemple);
    const frais =
        data.type_frais === 'pourcentage'
            ? Math.round(sousTotal * (Number(data.valeur_frais) / 100))
            : Math.round(Number(data.valeur_frais || 0));
    const total = (Number.isNaN(sousTotal) ? 0 : sousTotal) + (Number.isNaN(frais) ? 0 : frais);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        patch(admin.publicites.configuration.update().url, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Tarif publicités" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Tarif publicités</h1>
                    <p className="text-sm text-muted-foreground">
                        Prix par jour et frais de paiement appliqués aux publicités agence et client.
                    </p>
                </div>

                <Card className="max-w-2xl">
                    <CardHeader className="pb-4">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Megaphone className="h-4 w-4 text-primary" />
                                    Configuration
                                </CardTitle>
                                <CardDescription className="mt-1.5">
                                    Une publicité n’est payable que si ce tarif est actif.
                                </CardDescription>
                            </div>
                            <Badge variant={data.actif ? 'default' : 'secondary'}>
                                {data.actif ? 'Actif' : 'Inactif'}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-5">
                            <div className="space-y-1.5">
                                <Label htmlFor="libelle">Libellé</Label>
                                <Input
                                    id="libelle"
                                    value={data.libelle}
                                    onChange={(e) => setData('libelle', e.target.value)}
                                />
                                {errors.libelle && <p className="text-xs text-destructive">{errors.libelle}</p>}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="prix_par_jour">
                                    Prix par jour (FCFA) <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="prix_par_jour"
                                    type="number"
                                    min="1"
                                    step="1"
                                    inputMode="numeric"
                                    value={data.prix_par_jour}
                                    onChange={(e) => setData('prix_par_jour', e.target.value)}
                                />
                                {errors.prix_par_jour && (
                                    <p className="text-xs text-destructive">{errors.prix_par_jour}</p>
                                )}
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label>Type de frais</Label>
                                    <Select
                                        value={data.type_frais}
                                        onValueChange={(v) => setData('type_frais', v as FormData['type_frais'])}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="pourcentage">Pourcentage (%)</SelectItem>
                                            <SelectItem value="fixe">Montant fixe (FCFA)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="valeur_frais">Valeur des frais</Label>
                                    <div className="relative">
                                        <Input
                                            id="valeur_frais"
                                            type="number"
                                            min={0}
                                            max={data.type_frais === 'pourcentage' ? 100 : undefined}
                                            step="1"
                                            inputMode="numeric"
                                            value={data.valeur_frais}
                                            onChange={(e) => setData('valeur_frais', e.target.value)}
                                            className={data.type_frais === 'pourcentage' ? 'pr-9' : undefined}
                                        />
                                        {data.type_frais === 'pourcentage' && (
                                            <Percent className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                        )}
                                    </div>
                                </div>
                            </div>

                            <div className="rounded-lg border bg-muted/40 px-4 py-3 text-sm">
                                <p className="font-medium">Aperçu sur {joursExemple} jours</p>
                                <p className="mt-1 text-muted-foreground">
                                    Sous-total {fmtFcfa(Number.isNaN(sousTotal) ? 0 : sousTotal)} + frais{' '}
                                    {fmtFcfa(Number.isNaN(frais) ? 0 : frais)} ={' '}
                                    <span className="font-medium text-foreground">{fmtFcfa(Number.isNaN(total) ? 0 : total)}</span>
                                </p>
                            </div>

                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="actif"
                                    checked={data.actif}
                                    onCheckedChange={(checked) => setData('actif', checked === true)}
                                />
                                <Label htmlFor="actif" className="cursor-pointer font-normal">
                                    Activer ce tarif
                                </Label>
                            </div>

                            <Button type="submit" disabled={processing}>
                                {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                Enregistrer
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

PubliciteConfiguration.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Publicités', href: admin.publicites.index().url },
        { title: 'Tarif' },
    ],
};
