import { useForm } from '@inertiajs/react';
import { Loader2, MapPin } from 'lucide-react';
import { useEffect } from 'react';
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
import admin from '@/routes/admin';
import type { DestinationFormData, DestinationRow } from '@/types';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    destination?: DestinationRow | null;
}

const defaultForm: DestinationFormData = {
    depart: '',
    arrivee: '',
    appliquer_configuration: false,
    montant: '',
    commission_pourcentage: '',
    actif: true,
};

function toFormData(destination: DestinationRow): DestinationFormData {
    return {
        depart: destination.depart,
        arrivee: destination.arrivee,
        appliquer_configuration: Boolean(destination.appliquer_configuration),
        montant: destination.montant != null ? String(destination.montant) : '',
        commission_pourcentage:
            destination.commission_pourcentage != null
                ? String(destination.commission_pourcentage)
                : '',
        actif: Boolean(destination.actif),
    };
}

export function DestinationFormDialog({ open, onOpenChange, destination }: Props) {
    const isEdit = Boolean(destination);
    const formId = isEdit ? 'destination-form-edit' : 'destination-form-create';

    const { data, setData, post, patch, processing, errors, reset, clearErrors } =
        useForm<DestinationFormData>(destination ? toFormData(destination) : defaultForm);

    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();
        setData(destination ? toFormData(destination) : defaultForm);
    }, [open, destination, setData, clearErrors]);

    const handleOpenChange = (value: boolean) => {
        if (!value) {
            reset();
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
                        {isEdit
                            ? 'Mettez à jour le trajet et, le cas échéant, la configuration tarifaire forcée.'
                            : 'Définissez un trajet départ → arrivée, avec une config tarifaire optionnelle.'}
                    </DialogDescription>
                </DialogHeader>

                <form id={formId} onSubmit={submit} className="space-y-4 py-2">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="destination-depart">
                                Départ <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="destination-depart"
                                value={data.depart}
                                onChange={(e) => setData('depart', e.target.value)}
                                placeholder="Ex : Abidjan"
                                autoFocus
                            />
                            {errors.depart && <p className="text-xs text-destructive">{errors.depart}</p>}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="destination-arrivee">
                                Arrivée <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="destination-arrivee"
                                value={data.arrivee}
                                onChange={(e) => setData('arrivee', e.target.value)}
                                placeholder="Ex : Paris"
                            />
                            {errors.arrivee && (
                                <p className="text-xs text-destructive">{errors.arrivee}</p>
                            )}
                        </div>
                    </div>

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
                            Force un montant et une commission plateforme pour les offres liées à ce trajet.
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
                    <Button type="submit" form={formId} disabled={processing}>
                        {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        {isEdit ? 'Enregistrer' : 'Créer'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
