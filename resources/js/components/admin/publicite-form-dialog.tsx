import { useForm } from '@inertiajs/react';
import { Loader2, Megaphone } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
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
import type { AgenceSummary, ClientSummary } from '@/types';

type Proprietaire = 'verga' | 'agence' | 'client';

type OffreOption = {
    id: string;
    titre: string;
    agence_id: string;
};

type FormData = {
    proprietaire: Proprietaire;
    agence_id: string;
    client_id: string;
    offre_id: string;
    titre: string;
    description: string;
    lien: string;
    date_debut: string;
    date_fin: string;
    image: File | null;
};

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    agences: AgenceSummary[];
    clients: ClientSummary[];
    offres: OffreOption[];
}

const emptyForm = (): FormData => ({
    proprietaire: 'verga',
    agence_id: '',
    client_id: '',
    offre_id: '',
    titre: '',
    description: '',
    lien: '',
    date_debut: '',
    date_fin: '',
    image: null,
});

export function PubliciteFormDialog({ open, onOpenChange, agences, clients, offres }: Props) {
    const { data, setData, post, processing, errors, reset, clearErrors, progress } = useForm<FormData>(emptyForm());
    const [preview, setPreview] = useState<string | null>(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();
        setData(emptyForm());
        setPreview(null);
    }, [open]);

    useEffect(() => {
        if (!data.image) {
            setPreview(null);

            return;
        }

        const url = URL.createObjectURL(data.image);
        setPreview(url);

        return () => URL.revokeObjectURL(url);
    }, [data.image]);

    const offresAgence = useMemo(
        () => offres.filter((offre) => offre.agence_id === data.agence_id),
        [offres, data.agence_id],
    );

    const handleOpenChange = (value: boolean) => {
        if (!value) {
            reset();
            setPreview(null);
        }

        onOpenChange(value);
    };

    const setProprietaire = (value: Proprietaire) => {
        setData({
            ...data,
            proprietaire: value,
            agence_id: '',
            client_id: '',
            offre_id: '',
        });
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        post(admin.publicites.store().url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setPreview(null);
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Megaphone className="h-4 w-4 text-primary" />
                        Nouvelle publicité
                    </DialogTitle>
                    <DialogDescription>
                        La publicité est publiée immédiatement, sans validation ni paiement.
                    </DialogDescription>
                </DialogHeader>

                <form id="publicite-form-create" onSubmit={submit} className="space-y-4 py-2">
                    <div className="space-y-1.5">
                        <Label htmlFor="publicite-proprietaire">Annonceur</Label>
                        <Select value={data.proprietaire} onValueChange={(v) => setProprietaire(v as Proprietaire)}>
                            <SelectTrigger id="publicite-proprietaire">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="verga">VERGA (interne)</SelectItem>
                                <SelectItem value="agence">Agence</SelectItem>
                                <SelectItem value="client">Client</SelectItem>
                            </SelectContent>
                        </Select>
                        {errors.proprietaire && <p className="text-xs text-destructive">{errors.proprietaire}</p>}
                    </div>

                    {data.proprietaire === 'agence' && (
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="publicite-agence">
                                    Agence <span className="text-destructive">*</span>
                                </Label>
                                <Select
                                    value={data.agence_id}
                                    onValueChange={(v) => {
                                        setData('agence_id', v);
                                        setData('offre_id', '');
                                    }}
                                >
                                    <SelectTrigger id="publicite-agence">
                                        <SelectValue placeholder="Choisir une agence" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {agences.length === 0 ? (
                                            <SelectItem value="__none" disabled>
                                                Aucune agence active
                                            </SelectItem>
                                        ) : (
                                            agences.map((agence) => (
                                                <SelectItem key={agence.id} value={agence.id}>
                                                    {agence.nom}
                                                </SelectItem>
                                            ))
                                        )}
                                    </SelectContent>
                                </Select>
                                {errors.agence_id && <p className="text-xs text-destructive">{errors.agence_id}</p>}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="publicite-offre">Offre (optionnel)</Label>
                                <Select
                                    value={data.offre_id || '__none'}
                                    onValueChange={(v) => setData('offre_id', v === '__none' ? '' : v)}
                                    disabled={!data.agence_id}
                                >
                                    <SelectTrigger id="publicite-offre">
                                        <SelectValue placeholder="Aucune" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="__none">Aucune</SelectItem>
                                        {offresAgence.map((offre) => (
                                            <SelectItem key={offre.id} value={offre.id}>
                                                {offre.titre}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.offre_id && <p className="text-xs text-destructive">{errors.offre_id}</p>}
                            </div>
                        </div>
                    )}

                    {data.proprietaire === 'client' && (
                        <div className="space-y-1.5">
                            <Label htmlFor="publicite-client">
                                Client <span className="text-destructive">*</span>
                            </Label>
                            <Select value={data.client_id} onValueChange={(v) => setData('client_id', v)}>
                                <SelectTrigger id="publicite-client">
                                    <SelectValue placeholder="Choisir un client" />
                                </SelectTrigger>
                                <SelectContent>
                                    {clients.length === 0 ? (
                                        <SelectItem value="__none" disabled>
                                            Aucun client actif
                                        </SelectItem>
                                    ) : (
                                        clients.map((client) => (
                                            <SelectItem key={client.id} value={client.id}>
                                                {client.prenom} {client.nom}
                                            </SelectItem>
                                        ))
                                    )}
                                </SelectContent>
                            </Select>
                            {errors.client_id && <p className="text-xs text-destructive">{errors.client_id}</p>}
                        </div>
                    )}

                    <div className="space-y-1.5">
                        <Label htmlFor="publicite-titre">
                            Titre <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="publicite-titre"
                            value={data.titre}
                            onChange={(e) => setData('titre', e.target.value)}
                            placeholder="Promo groupage août"
                            autoFocus
                        />
                        {errors.titre && <p className="text-xs text-destructive">{errors.titre}</p>}
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="publicite-description">Description</Label>
                        <textarea
                            id="publicite-description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            className="min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                        {errors.description && <p className="text-xs text-destructive">{errors.description}</p>}
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="publicite-lien">Lien</Label>
                        <Input
                            id="publicite-lien"
                            type="url"
                            value={data.lien}
                            onChange={(e) => setData('lien', e.target.value)}
                            placeholder="https://"
                        />
                        {errors.lien && <p className="text-xs text-destructive">{errors.lien}</p>}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="publicite-debut">
                                Date de début <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="publicite-debut"
                                type="date"
                                value={data.date_debut}
                                onChange={(e) => setData('date_debut', e.target.value)}
                            />
                            {errors.date_debut && <p className="text-xs text-destructive">{errors.date_debut}</p>}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="publicite-fin">
                                Date de fin <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="publicite-fin"
                                type="date"
                                value={data.date_fin}
                                onChange={(e) => setData('date_fin', e.target.value)}
                            />
                            {errors.date_fin && <p className="text-xs text-destructive">{errors.date_fin}</p>}
                        </div>
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="publicite-image">
                            Image <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="publicite-image"
                            type="file"
                            accept="image/*"
                            onChange={(e) => setData('image', e.target.files?.[0] ?? null)}
                        />
                        {preview && (
                            <img src={preview} alt="Aperçu" className="mt-2 h-24 w-full rounded-lg border object-cover" />
                        )}
                        {progress && (
                            <p className="text-xs text-muted-foreground">Envoi {progress.percentage} %</p>
                        )}
                        {errors.image && <p className="text-xs text-destructive">{errors.image}</p>}
                    </div>
                </form>

                <DialogFooter>
                    <Button type="submit" form="publicite-form-create" disabled={processing}>
                        {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        Publier
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
