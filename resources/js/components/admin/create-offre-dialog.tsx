import { useForm } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
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
import type { AgenceSummary, CreateOffreFormData } from '@/types';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    agences: AgenceSummary[];
}

type FormData = CreateOffreFormData;

const TYPE_OPTIONS = [
    { value: 'particulier',  label: 'Au kg (particulier)' },
    { value: 'metre_cube',   label: 'Au m³ (mètre cube)' },
    { value: 'conteneur',    label: 'Par conteneur' },
];

export function CreateOffreDialog({ open, onOpenChange, agences }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm<FormData>({
        agence_id:   '',
        titre:       '',
        type:        'particulier',
        prix:        '',
        capacite_totale: '',
        origine:     '',
        destination: '',
        description: '',
        statut:      'active',
    });

    const handleOpenChange = (value: boolean) => {
        if (!value) {
reset();
}

        onOpenChange(value);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(admin.offres.store().url, {
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Nouvelle offre</DialogTitle>
                    <DialogDescription>
                        Créez une offre de transport pour une agence partenaire.
                    </DialogDescription>
                </DialogHeader>

                <form id="create-offre-form" onSubmit={submit} className="space-y-5 py-2">

                    {/* Agence */}
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

                    {/* Titre */}
                    <div className="space-y-1.5">
                        <Label htmlFor="offre-titre">
                            Titre <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="offre-titre"
                            value={data.titre}
                            onChange={(e) => setData('titre', e.target.value)}
                            placeholder="Ex : Transport Libreville → Port-Gentil"
                            autoFocus
                        />
                        {errors.titre && <p className="text-xs text-destructive">{errors.titre}</p>}
                    </div>

                    {/* Type + Prix */}
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="offre-type">
                                Type <span className="text-destructive">*</span>
                            </Label>
                            <Select value={data.type} onValueChange={(v) => setData('type', v)}>
                                <SelectTrigger id="offre-type">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {TYPE_OPTIONS.map((t) => (
                                        <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.type && <p className="text-xs text-destructive">{errors.type}</p>}
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
                            <p className="text-xs text-muted-foreground">
                                {data.type === 'particulier' && 'Prix par kg'}
                                {data.type === 'metre_cube'  && 'Prix par m³'}
                                {data.type === 'conteneur'   && 'Prix par conteneur'}
                            </p>
                        </div>

                        <div className="space-y-1.5 sm:col-span-2">
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
                                placeholder="Ex : 30000 kg, 6 conteneurs..."
                            />
                            {errors.capacite_totale && (
                                <p className="text-xs text-destructive">{errors.capacite_totale}</p>
                            )}
                        </div>
                    </div>

                    {/* Trajet */}
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

                    {/* Description */}
                    <div className="space-y-1.5">
                        <Label htmlFor="offre-description">
                            Description
                            <span className="ml-1 text-xs font-normal text-muted-foreground">(optionnel)</span>
                        </Label>
                        <textarea
                            id="offre-description"
                            value={data.description}
                            onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => setData('description', e.target.value)}
                            placeholder="Informations complémentaires sur l'offre…"
                            rows={3}
                            className="border-input bg-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[80px] w-full resize-none rounded-md border px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1"
                        />
                        {errors.description && <p className="text-xs text-destructive">{errors.description}</p>}
                    </div>

                    {/* Statut */}
                    <div className="space-y-1.5">
                        <Label htmlFor="offre-statut">Statut</Label>
                        <Select value={data.statut} onValueChange={(v) => setData('statut', v)}>
                            <SelectTrigger id="offre-statut" className="w-48">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="active">Active</SelectItem>
                                <SelectItem value="inactive">Inactive</SelectItem>
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
                    <Button type="submit" form="create-offre-form" disabled={processing}>
                        {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        Créer l'offre
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
