import { useForm } from '@inertiajs/react';
import { Globe, Loader2 } from 'lucide-react';
import { useEffect, useState } from 'react';
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
import type { VilleFormData, VilleRow } from '@/types';

const NOUVEAU_PAYS = '__nouveau__';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    ville?: VilleRow | null;
    paysExistants: string[];
}

const defaultForm: VilleFormData = {
    pays: '',
    ville: '',
    code: '',
    actif: true,
};

function toFormData(ville: VilleRow): VilleFormData {
    return {
        pays: ville.pays,
        ville: ville.ville,
        code: ville.code,
        actif: Boolean(ville.actif),
    };
}

export function VilleFormDialog({ open, onOpenChange, ville, paysExistants }: Props) {
    const isEdit = Boolean(ville);
    const formId = isEdit ? 'ville-form-edit' : 'ville-form-create';

    const { data, setData, post, patch, processing, errors, reset, clearErrors } =
        useForm<VilleFormData>(ville ? toFormData(ville) : defaultForm);

    const [paysMode, setPaysMode] = useState<string>(
        ville && paysExistants.includes(ville.pays) ? ville.pays : ville ? NOUVEAU_PAYS : '',
    );

    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();
        setData(ville ? toFormData(ville) : defaultForm);
        setPaysMode(
            ville && paysExistants.includes(ville.pays) ? ville.pays : ville ? NOUVEAU_PAYS : '',
        );
    }, [open, ville, paysExistants, setData, clearErrors]);

    const handleOpenChange = (value: boolean) => {
        if (!value) {
            reset();
            setPaysMode('');
        }

        onOpenChange(value);
    };

    const handlePaysMode = (value: string) => {
        setPaysMode(value);
        setData('pays', value === NOUVEAU_PAYS ? '' : value);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        const options = {
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        };

        if (isEdit && ville) {
            patch(admin.villes.update(ville.id).url, options);
        } else {
            post(admin.villes.store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Globe className="h-4 w-4 text-primary" />
                        {isEdit ? 'Modifier la ville' : 'Nouvelle ville'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEdit
                            ? 'Mettez à jour le pays, la ville et le code.'
                            : 'Choisissez un pays déjà créé, ou ajoutez-en un nouveau, puis saisissez la ville et le code.'}
                    </DialogDescription>
                </DialogHeader>

                <form id={formId} onSubmit={submit} className="space-y-4 py-2">
                    <div className="space-y-1.5">
                        <Label htmlFor="ville-pays-mode">
                            Pays <span className="text-destructive">*</span>
                        </Label>
                        <Select value={paysMode || undefined} onValueChange={handlePaysMode}>
                            <SelectTrigger id="ville-pays-mode">
                                <SelectValue placeholder="Choisir un pays" />
                            </SelectTrigger>
                            <SelectContent>
                                {paysExistants.map((nom) => (
                                    <SelectItem key={nom} value={nom}>
                                        {nom}
                                    </SelectItem>
                                ))}
                                <SelectItem value={NOUVEAU_PAYS}>Autre / nouveau pays</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    {paysMode === NOUVEAU_PAYS && (
                        <div className="space-y-1.5">
                            <Label htmlFor="ville-pays">
                                Nom du nouveau pays <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="ville-pays"
                                value={data.pays}
                                onChange={(e) => setData('pays', e.target.value)}
                                placeholder="Ex : Gabon"
                                autoFocus
                            />
                        </div>
                    )}
                    {errors.pays && <p className="text-xs text-destructive">{errors.pays}</p>}

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="ville-ville">
                                Ville <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="ville-ville"
                                value={data.ville}
                                onChange={(e) => setData('ville', e.target.value)}
                                placeholder="Ex : Libreville"
                            />
                            {errors.ville && <p className="text-xs text-destructive">{errors.ville}</p>}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="ville-code">
                                Code <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="ville-code"
                                value={data.code}
                                onChange={(e) => setData('code', e.target.value.toUpperCase())}
                                placeholder="Ex : LBV"
                            />
                            <p className="text-xs text-muted-foreground">
                                Lettres majuscules, chiffres, tirets et underscores.
                            </p>
                            {errors.code && <p className="text-xs text-destructive">{errors.code}</p>}
                        </div>
                    </div>

                    <div className="flex items-center gap-2 rounded-lg border p-4">
                        <Checkbox
                            id="ville-actif"
                            checked={data.actif}
                            onCheckedChange={(v) => setData('actif', v === true)}
                        />
                        <Label htmlFor="ville-actif" className="cursor-pointer font-normal">
                            Ville active (sélectionnable pour les destinations)
                        </Label>
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
