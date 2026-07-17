import { useForm } from '@inertiajs/react';
import { Loader2, Package } from 'lucide-react';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import admin from '@/routes/admin';
import type { AgenceSummary, OffreFormData, OffreRow, TypeOffreApi } from '@/types';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    agences: AgenceSummary[];
    typesOffres: TypeOffreApi[];
    offre?: OffreRow | null;
}

function emptyForm(typesOffres: TypeOffreApi[]): OffreFormData {
    return {
        agence_id: '',
        titre: '',
        type_offre_id: typesOffres[0]?.id ?? '',
        prix: '',
        capacite_illimitee: false,
        capacite_totale: '',
        origine: '',
        destination: '',
        description: '',
        statut: 'active',
    };
}

function toFormData(offre: OffreRow, typesOffres: TypeOffreApi[]): OffreFormData {
    return {
        agence_id: offre.agence_id ?? offre.agence?.id ?? '',
        titre: offre.titre,
        type_offre_id: offre.type_offre_id ?? typesOffres[0]?.id ?? '',
        prix: String(offre.prix),
        capacite_illimitee: Boolean(offre.capacite_illimitee),
        capacite_totale: offre.capacite_totale == null ? '' : String(offre.capacite_totale),
        origine: offre.origine,
        destination: offre.destination,
        description: offre.description ?? '',
        statut: offre.statut,
    };
}

export function OffreFormDialog({ open, onOpenChange, agences, typesOffres, offre }: Props) {
    const isEdit = Boolean(offre);
    const formId = isEdit ? 'offre-form-edit' : 'offre-form-create';

    const { data, setData, post, patch, processing, errors, reset, clearErrors } =
        useForm<OffreFormData>(offre ? toFormData(offre, typesOffres) : emptyForm(typesOffres));

    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();
        setData(offre ? toFormData(offre, typesOffres) : emptyForm(typesOffres));
    }, [open, offre, typesOffres, setData, clearErrors]);

    const selectedType = typesOffres.find((t) => t.id === data.type_offre_id);

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

        if (isEdit && offre) {
            patch(admin.offres.update(offre.id).url, options);
        } else {
            post(admin.offres.store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Package className="h-4 w-4 text-primary" />
                        {isEdit ? 'Modifier l\'offre' : 'Nouvelle offre'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEdit
                            ? 'Mettez à jour les informations de l\'offre de transport.'
                            : 'Créez une offre de transport pour une agence partenaire.'}
                    </DialogDescription>
                </DialogHeader>

                <form id={formId} onSubmit={submit} className="space-y-5 py-2">
                    <div className="space-y-1.5">
                        <Label htmlFor="offre-agence">
                            Agence <span className="text-destructive">*</span>
                        </Label>
                        <Select value={data.agence_id} onValueChange={(v) => setData('agence_id', v)}>
                            <SelectTrigger id="offre-agence">
                                <SelectValue placeholder="Choisir une agence" />
                            </SelectTrigger>
                            <SelectContent>
                                {agences.length === 0 ? (
                                    <SelectItem value="__none" disabled>Aucune agence active</SelectItem>
                                ) : (
                                    agences.map((a) => (
                                        <SelectItem key={a.id} value={a.id}>{a.nom}</SelectItem>
                                    ))
                                )}
                            </SelectContent>
                        </Select>
                        {errors.agence_id && <p className="text-xs text-destructive">{errors.agence_id}</p>}
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="offre-titre">
                            Titre <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="offre-titre"
                            value={data.titre}
                            onChange={(e) => setData('titre', e.target.value)}
                            placeholder="Ex : Transport Libreville → Port-Gentil"
                            autoFocus={!isEdit}
                        />
                        {errors.titre && <p className="text-xs text-destructive">{errors.titre}</p>}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="offre-type">
                                Type <span className="text-destructive">*</span>
                            </Label>
                            <Select
                                value={data.type_offre_id}
                                onValueChange={(v) => setData('type_offre_id', v)}
                            >
                                <SelectTrigger id="offre-type">
                                    <SelectValue placeholder="Choisir un type" />
                                </SelectTrigger>
                                <SelectContent>
                                    {typesOffres.map((t) => (
                                        <SelectItem key={t.id} value={t.id}>{t.nom}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.type_offre_id && (
                                <p className="text-xs text-destructive">
                                    {errors.type_offre_id}
                                </p>
                            )}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="offre-prix">
                                Prix (FCFA) <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="offre-prix"
                                type="number"
                                min="0"
                                step="1"
                                value={data.prix}
                                onChange={(e) => setData('prix', e.target.value)}
                                placeholder="Ex : 5000"
                            />
                            {errors.prix && <p className="text-xs text-destructive">{errors.prix}</p>}
                            {selectedType && (
                                <p className="text-xs text-muted-foreground">
                                    Prix {selectedType.unite_label}
                                </p>
                            )}
                        </div>

                        <div className="space-y-3 sm:col-span-2">
                            <div className="flex items-start gap-2 rounded-lg border p-3">
                                <Checkbox
                                    id="offre-capacite-illimitee"
                                    checked={data.capacite_illimitee}
                                    onCheckedChange={(v) => {
                                        const illimitee = v === true;
                                        setData({
                                            ...data,
                                            capacite_illimitee: illimitee,
                                            capacite_totale: illimitee ? '' : data.capacite_totale,
                                        });
                                    }}
                                />
                                <div className="space-y-1">
                                    <Label htmlFor="offre-capacite-illimitee" className="cursor-pointer font-normal">
                                        Capacité illimitée
                                    </Label>
                                    <p className="text-xs text-muted-foreground">
                                        Pas de plafond de stock : le client peut commander toute quantité
                                        autorisée par le type d&apos;offre.
                                    </p>
                                </div>
                            </div>
                            {errors.capacite_illimitee && (
                                <p className="text-xs text-destructive">{errors.capacite_illimitee}</p>
                            )}

                            {!data.capacite_illimitee && (
                                <div className="space-y-1.5">
                                    <Label htmlFor="offre-capacite">
                                        Capacité totale <span className="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="offre-capacite"
                                        type="number"
                                        min="0.001"
                                        step="any"
                                        value={data.capacite_totale}
                                        onChange={(e) => setData('capacite_totale', e.target.value)}
                                        placeholder={
                                            selectedType
                                                ? `Ex : stock en ${selectedType.unite}`
                                                : 'Ex : 30000 kg, 6 conteneurs...'
                                        }
                                    />
                                    {isEdit && offre && !offre.capacite_illimitee && (
                                        <p className="text-xs text-muted-foreground">
                                            Stock disponible actuel :{' '}
                                            {Number(offre.capacite_disponible).toLocaleString('fr-FR')}
                                            {' '}/ {Number(offre.capacite_totale).toLocaleString('fr-FR')}
                                        </p>
                                    )}
                                    {errors.capacite_totale && (
                                        <p className="text-xs text-destructive">{errors.capacite_totale}</p>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="offre-origine">
                                Origine <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="offre-origine"
                                value={data.origine}
                                onChange={(e) => setData('origine', e.target.value)}
                                placeholder="Ex : Libreville"
                            />
                            {errors.origine && <p className="text-xs text-destructive">{errors.origine}</p>}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="offre-destination">
                                Destination <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="offre-destination"
                                value={data.destination}
                                onChange={(e) => setData('destination', e.target.value)}
                                placeholder="Ex : Port-Gentil"
                            />
                            {errors.destination && <p className="text-xs text-destructive">{errors.destination}</p>}
                        </div>
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="offre-description">
                            Description
                            <span className="ml-1 text-xs font-normal text-muted-foreground">(optionnel)</span>
                        </Label>
                        <textarea
                            id="offre-description"
                            value={data.description}
                            onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) =>
                                setData('description', e.target.value)
                            }
                            placeholder="Informations complémentaires sur l'offre…"
                            rows={3}
                            className="border-input bg-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[80px] w-full resize-none rounded-md border px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1"
                        />
                        {errors.description && <p className="text-xs text-destructive">{errors.description}</p>}
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="offre-statut">Statut</Label>
                        <Select value={data.statut} onValueChange={(v) => setData('statut', v)}>
                            <SelectTrigger id="offre-statut" className="w-48">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="active">Active</SelectItem>
                                <SelectItem value="inactive">Inactive</SelectItem>
                                {isEdit && <SelectItem value="archivée">Archivée</SelectItem>}
                            </SelectContent>
                        </Select>
                        {errors.statut && <p className="text-xs text-destructive">{errors.statut}</p>}
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
                        {isEdit ? 'Enregistrer' : 'Créer l\'offre'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
