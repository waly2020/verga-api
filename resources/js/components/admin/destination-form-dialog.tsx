import { Link, useForm } from '@inertiajs/react';
import { Loader2, MapPin } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import admin from '@/routes/admin';
import type { DestinationFormData, DestinationRow, VilleSummary } from '@/types';
import { destinationVille } from '@/types/models/destination';
import { villeLabel } from '@/types/models/ville';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    destination?: DestinationRow | null;
    villes: VilleSummary[];
}

const defaultForm: DestinationFormData = {
    ville_depart_id: '',
    ville_arrivee_id: '',
    appliquer_configuration: false,
    montant: '',
    commission_pourcentage: '',
    actif: true,
};

function toFormData(destination: DestinationRow): DestinationFormData {
    return {
        ville_depart_id:
            destination.ville_depart_id ?? destinationVille(destination, 'depart')?.id ?? '',
        ville_arrivee_id:
            destination.ville_arrivee_id ?? destinationVille(destination, 'arrivee')?.id ?? '',
        appliquer_configuration: Boolean(destination.appliquer_configuration),
        montant: destination.montant != null ? String(destination.montant) : '',
        commission_pourcentage:
            destination.commission_pourcentage != null
                ? String(destination.commission_pourcentage)
                : '',
        actif: Boolean(destination.actif),
    };
}

function paysOf(villes: VilleSummary[], villeId: string): string {
    return villes.find((ville) => ville.id === villeId)?.pays ?? '';
}

function VilleSideFields({
    side,
    villes,
    villeId,
    paysNom,
    onPaysChange,
    onVilleChange,
    error,
}: {
    side: 'depart' | 'arrivee';
    villes: VilleSummary[];
    villeId: string;
    paysNom: string;
    onPaysChange: (pays: string) => void;
    onVilleChange: (id: string) => void;
    error?: string;
}) {
    const paysNoms = useMemo(
        () => [...new Set(villes.map((ville) => ville.pays))].sort((a, b) => a.localeCompare(b, 'fr')),
        [villes],
    );

    const villesFiltrees = villes.filter(
        (ville) => ville.pays === paysNom && (ville.actif || ville.id === villeId),
    );

    const label = side === 'depart' ? 'Départ' : 'Arrivée';

    return (
        <div className="space-y-3">
            <p className="text-sm font-medium">
                {label} <span className="text-destructive">*</span>
            </p>
            <div className="space-y-1.5">
                <Label htmlFor={`destination-pays-${side}`}>Pays</Label>
                <Select value={paysNom || undefined} onValueChange={onPaysChange}>
                    <SelectTrigger id={`destination-pays-${side}`}>
                        <SelectValue placeholder="Choisir un pays" />
                    </SelectTrigger>
                    <SelectContent>
                        {paysNoms.map((nom) => (
                            <SelectItem key={nom} value={nom}>
                                {nom}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <div className="space-y-1.5">
                <Label htmlFor={`destination-ville-${side}`}>Ville</Label>
                <Select
                    value={villeId || undefined}
                    onValueChange={onVilleChange}
                    disabled={!paysNom}
                >
                    <SelectTrigger id={`destination-ville-${side}`}>
                        <SelectValue placeholder={paysNom ? 'Choisir une ville' : 'Pays d’abord'} />
                    </SelectTrigger>
                    <SelectContent>
                        {villesFiltrees.map((ville) => (
                            <SelectItem key={ville.id} value={ville.id}>
                                {villeLabel(ville)}
                                {!ville.actif ? ' (inactive)' : ''}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {error && <p className="text-xs text-destructive">{error}</p>}
            </div>
        </div>
    );
}

export function DestinationFormDialog({ open, onOpenChange, destination, villes }: Props) {
    const isEdit = Boolean(destination);
    const formId = isEdit ? 'destination-form-edit' : 'destination-form-create';

    const { data, setData, post, patch, processing, errors, reset, clearErrors } =
        useForm<DestinationFormData>(destination ? toFormData(destination) : defaultForm);

    const [paysDepart, setPaysDepart] = useState(
        destination ? paysOf(villes, toFormData(destination).ville_depart_id) : '',
    );
    const [paysArrivee, setPaysArrivee] = useState(
        destination ? paysOf(villes, toFormData(destination).ville_arrivee_id) : '',
    );

    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();
        const next = destination ? toFormData(destination) : defaultForm;
        setData(next);
        setPaysDepart(paysOf(villes, next.ville_depart_id));
        setPaysArrivee(paysOf(villes, next.ville_arrivee_id));
    }, [open, destination, villes, setData, clearErrors]);

    const handleOpenChange = (value: boolean) => {
        if (!value) {
            reset();
            setPaysDepart('');
            setPaysArrivee('');
        }

        onOpenChange(value);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        const options = {
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        };

        if (isEdit && destination) {
            patch(admin.destinations.update(destination.id).url, options);
        } else {
            post(admin.destinations.store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <MapPin className="h-4 w-4 text-primary" />
                        {isEdit ? 'Modifier la destination' : 'Nouvelle destination'}
                    </DialogTitle>
                    <DialogDescription>
                        Choisissez un pays, puis une ville, pour le départ et l&apos;arrivée.
                    </DialogDescription>
                </DialogHeader>

                <form id={formId} onSubmit={submit} className="space-y-4 py-2">
                    {villes.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Aucune ville n&apos;est encore définie.{' '}
                            <Link
                                href={admin.villes.index()}
                                className="font-medium text-primary underline-offset-4 hover:underline"
                            >
                                Créer une ville
                            </Link>{' '}
                            avant de composer un trajet.
                        </p>
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-2">
                            <VilleSideFields
                                side="depart"
                                villes={villes}
                                villeId={data.ville_depart_id}
                                paysNom={paysDepart}
                                onPaysChange={(pays) => {
                                    setPaysDepart(pays);
                                    setData('ville_depart_id', '');
                                }}
                                onVilleChange={(id) => setData('ville_depart_id', id)}
                                error={errors.ville_depart_id}
                            />
                            <VilleSideFields
                                side="arrivee"
                                villes={villes}
                                villeId={data.ville_arrivee_id}
                                paysNom={paysArrivee}
                                onPaysChange={(pays) => {
                                    setPaysArrivee(pays);
                                    setData('ville_arrivee_id', '');
                                }}
                                onVilleChange={(id) => setData('ville_arrivee_id', id)}
                                error={errors.ville_arrivee_id}
                            />
                        </div>
                    )}

                    <div className="flex flex-col gap-3 rounded-lg border p-4">
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="destination-appliquer-configuration"
                                checked={data.appliquer_configuration}
                                onCheckedChange={(v) => {
                                    const checked = v === true;
                                    setData('appliquer_configuration', checked);
                                    if (!checked) {
                                        setData('montant', '');
                                        setData('commission_pourcentage', '');
                                    }
                                }}
                            />
                            <Label
                                htmlFor="destination-appliquer-configuration"
                                className="cursor-pointer font-normal"
                            >
                                Appliquer une configuration tarifaire
                            </Label>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            Force un montant et une commission plateforme pour les offres liées à ce
                            trajet.
                        </p>

                        {data.appliquer_configuration && (
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="destination-montant">
                                        Montant (FCFA) <span className="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="destination-montant"
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        value={data.montant}
                                        onChange={(e) => setData('montant', e.target.value)}
                                        placeholder="Ex : 1500"
                                    />
                                    {errors.montant && (
                                        <p className="text-xs text-destructive">{errors.montant}</p>
                                    )}
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="destination-commission">
                                        Commission (%) <span className="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="destination-commission"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        value={data.commission_pourcentage}
                                        onChange={(e) =>
                                            setData('commission_pourcentage', e.target.value)
                                        }
                                        placeholder="Ex : 10"
                                    />
                                    {errors.commission_pourcentage && (
                                        <p className="text-xs text-destructive">
                                            {errors.commission_pourcentage}
                                        </p>
                                    )}
                                </div>
                            </div>
                        )}

                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="destination-actif"
                                checked={data.actif}
                                onCheckedChange={(v) => setData('actif', v === true)}
                            />
                            <Label htmlFor="destination-actif" className="cursor-pointer font-normal">
                                Destination active (disponible pour les agences)
                            </Label>
                        </div>
                    </div>
                </form>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => handleOpenChange(false)}
                        disabled={processing}
                    >
                        Annuler
                    </Button>
                    <Button type="submit" form={formId} disabled={processing || villes.length === 0}>
                        {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        {isEdit ? 'Enregistrer' : 'Créer'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
