import { useForm } from '@inertiajs/react';
import { Loader2, Tag } from 'lucide-react';
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
import type { TypeOffreFormData, TypeOffreRow } from '@/types';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    typeOffre?: TypeOffreRow | null;
}

const defaultForm: TypeOffreFormData = {
    slug: '',
    nom: '',
    description: '',
    unite: '',
    unite_label: '',
    quantite_entier: false,
    quantite_min: '0.001',
    actif: true,
};

export function TypeOffreFormDialog({ open, onOpenChange, typeOffre }: Props) {
    const isEdit = Boolean(typeOffre);

    const { data, setData, post, patch, processing, errors, reset, clearErrors } =
        useForm<TypeOffreFormData>(defaultForm);

    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();

        if (typeOffre) {
            reset({
                slug: typeOffre.slug,
                nom: typeOffre.nom,
                description: typeOffre.description ?? '',
                unite: typeOffre.unite,
                unite_label: typeOffre.unite_label,
                quantite_entier: typeOffre.quantite_entier,
                quantite_min: String(typeOffre.quantite_min),
                actif: typeOffre.actif,
            });
        } else {
            reset(defaultForm);
        }
    }, [open, typeOffre, reset, clearErrors]);

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

        if (isEdit && typeOffre) {
            patch(admin.typesOffres.update(typeOffre.id).url, options);
        } else {
            post(admin.typesOffres.store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Tag className="h-4 w-4 text-primary" />
                        {isEdit ? 'Modifier le type d\'offre' : 'Nouveau type d\'offre'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEdit
                            ? 'Mettez à jour les règles de quantité et le libellé affiché aux agences et clients.'
                            : 'Définissez une nouvelle modalité de tarification pour les offres de transport.'}
                    </DialogDescription>
                </DialogHeader>

                <form id="type-offre-form" onSubmit={submit} className="space-y-4 py-2">
                    {!isEdit && (
                        <div className="space-y-1.5">
                            <Label htmlFor="type-offre-slug">
                                Code (slug) <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="type-offre-slug"
                                value={data.slug}
                                onChange={(e) => setData('slug', e.target.value.toLowerCase())}
                                placeholder="Ex : palette, vrac..."
                                autoFocus
                            />
                            <p className="text-xs text-muted-foreground">
                                Lettres minuscules, chiffres et underscores uniquement. Non modifiable ensuite.
                            </p>
                            {errors.slug && <p className="text-xs text-destructive">{errors.slug}</p>}
                        </div>
                    )}

                    {isEdit && (
                        <div className="space-y-1.5">
                            <Label>Code (slug)</Label>
                            <Input value={data.slug} disabled className="bg-muted" />
                        </div>
                    )}

                    <div className="space-y-1.5">
                        <Label htmlFor="type-offre-nom">
                            Nom <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="type-offre-nom"
                            value={data.nom}
                            onChange={(e) => setData('nom', e.target.value)}
                            placeholder="Ex : Particulier (au kg)"
                            autoFocus={isEdit}
                        />
                        {errors.nom && <p className="text-xs text-destructive">{errors.nom}</p>}
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="type-offre-description">
                            Description
                            <span className="ml-1 text-xs font-normal text-muted-foreground">(optionnel)</span>
                        </Label>
                        <textarea
                            id="type-offre-description"
                            value={data.description}
                            onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) =>
                                setData('description', e.target.value)
                            }
                            placeholder="Usage et contexte de ce type d'offre…"
                            rows={2}
                            className="border-input bg-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[60px] w-full resize-none rounded-md border px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1"
                        />
                        {errors.description && (
                            <p className="text-xs text-destructive">{errors.description}</p>
                        )}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="type-offre-unite">
                                Unité <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="type-offre-unite"
                                value={data.unite}
                                onChange={(e) => setData('unite', e.target.value)}
                                placeholder="Ex : kg, m3, conteneur"
                            />
                            {errors.unite && <p className="text-xs text-destructive">{errors.unite}</p>}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="type-offre-unite-label">
                                Libellé unité <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="type-offre-unite-label"
                                value={data.unite_label}
                                onChange={(e) => setData('unite_label', e.target.value)}
                                placeholder="Ex : au kg, par conteneur"
                            />
                            {errors.unite_label && (
                                <p className="text-xs text-destructive">{errors.unite_label}</p>
                            )}
                        </div>
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="type-offre-quantite-min">
                            Quantité minimale <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="type-offre-quantite-min"
                            type="number"
                            min="0.001"
                            step="any"
                            value={data.quantite_min}
                            onChange={(e) => setData('quantite_min', e.target.value)}
                        />
                        {errors.quantite_min && (
                            <p className="text-xs text-destructive">{errors.quantite_min}</p>
                        )}
                    </div>

                    <div className="flex flex-col gap-3 rounded-lg border p-4">
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="type-offre-quantite-entier"
                                checked={data.quantite_entier}
                                onCheckedChange={(v) => setData('quantite_entier', v === true)}
                            />
                            <Label htmlFor="type-offre-quantite-entier" className="cursor-pointer font-normal">
                                Quantité entière obligatoire
                            </Label>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            À activer pour les conteneurs ou toute offre vendue à l&apos;unité indivisible.
                        </p>

                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="type-offre-actif"
                                checked={data.actif}
                                onCheckedChange={(v) => setData('actif', v === true)}
                            />
                            <Label htmlFor="type-offre-actif" className="cursor-pointer font-normal">
                                Type actif (visible dans les formulaires)
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
                    <Button type="submit" form="type-offre-form" disabled={processing}>
                        {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        {isEdit ? 'Enregistrer' : 'Créer'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
