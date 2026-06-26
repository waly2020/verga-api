import { useForm } from '@inertiajs/react';
import { Eye, EyeOff, Loader2 } from 'lucide-react';
import { useState } from 'react';
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

type TypeAgence = { id: string; nom: string };

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    typesAgences: TypeAgence[];
}

type FormData = {
    nom: string;
    email: string;
    telephone: string;
    type_agence_id: string;
    ville: string;
    adresse: string;
    pays: string;
    gerant_name: string;
    gerant_email: string;
    gerant_password: string;
    gerant_password_confirmation: string;
};

export function CreateAgenceDialog({ open, onOpenChange, typesAgences }: Props) {
    const [showPwd, setShowPwd] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm<FormData>({
        nom: '',
        email: '',
        telephone: '',
        type_agence_id: '',
        ville: '',
        adresse: '',
        pays: 'Gabon',
        gerant_name: '',
        gerant_email: '',
        gerant_password: '',
        gerant_password_confirmation: '',
    });

    const handleOpenChange = (value: boolean) => {
        if (!value) {
reset();
}

        onOpenChange(value);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(admin.agences.store().url, {
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    const field = (
        id: keyof FormData,
        label: string,
        opts?: { type?: string; placeholder?: string; required?: boolean }
    ) => (
        <div className="space-y-1.5">
            <Label htmlFor={id}>
                {label}
                {opts?.required !== false && <span className="ml-0.5 text-destructive">*</span>}
            </Label>
            <Input
                id={id}
                type={opts?.type ?? 'text'}
                value={data[id]}
                onChange={(e) => setData(id, e.target.value)}
                placeholder={opts?.placeholder}
            />
            {errors[id] && <p className="text-xs text-destructive">{errors[id]}</p>}
        </div>
    );

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Créer une agence</DialogTitle>
                    <DialogDescription>
                        Renseignez les informations de l'agence et du compte gérant.
                    </DialogDescription>
                </DialogHeader>

                <form id="create-agence-form" onSubmit={submit} className="space-y-6 py-2">

                    {/* ── Section agence ─────────────────────────────── */}
                    <div className="space-y-4">
                        <h3 className="text-sm font-semibold text-foreground">Informations de l'agence</h3>

                        <div className="grid gap-4 sm:grid-cols-2">
                            {field('nom', 'Nom de l\'agence', { placeholder: 'Agence Transit Express' })}
                            {field('email', 'Email agence', { type: 'email', placeholder: 'contact@agence.com' })}
                            {field('telephone', 'Téléphone', { placeholder: '+241 01 23 45 67' })}

                            {/* Type d'agence */}
                            <div className="space-y-1.5">
                                <Label htmlFor="type_agence_id">
                                    Type d'agence
                                    <span className="ml-1 text-xs font-normal text-muted-foreground">(optionnel)</span>
                                </Label>
                                <Select
                                    value={data.type_agence_id}
                                    onValueChange={(v) => setData('type_agence_id', v === '__none' ? '' : v)}
                                >
                                    <SelectTrigger id="type_agence_id">
                                        <SelectValue placeholder="Choisir un type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="__none">— Aucun —</SelectItem>
                                        {typesAgences.map((t) => (
                                            <SelectItem key={t.id} value={t.id}>{t.nom}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.type_agence_id && (
                                    <p className="text-xs text-destructive">{errors.type_agence_id}</p>
                                )}
                            </div>

                            {field('ville', 'Ville', { placeholder: 'Libreville', required: false })}
                            {field('adresse', 'Adresse', { placeholder: 'Rue du Commerce, Quartier Louis', required: false })}

                            {/* Pays */}
                            <div className="space-y-1.5">
                                <Label htmlFor="pays">Pays</Label>
                                <Input
                                    id="pays"
                                    value={data.pays}
                                    onChange={(e) => setData('pays', e.target.value)}
                                    placeholder="Gabon"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Séparateur */}
                    <div className="relative">
                        <div className="absolute inset-0 flex items-center">
                            <span className="w-full border-t border-border" />
                        </div>
                        <div className="relative flex justify-center">
                            <span className="bg-background px-3 text-xs text-muted-foreground">
                                Compte gérant
                            </span>
                        </div>
                    </div>

                    {/* ── Section gérant ─────────────────────────────── */}
                    <div className="space-y-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            {field('gerant_name', 'Nom complet', { placeholder: 'Jean Mbaye' })}
                            {field('gerant_email', 'Email de connexion', { type: 'email', placeholder: 'gerant@agence.com' })}

                            {/* Mot de passe */}
                            <div className="space-y-1.5">
                                <Label htmlFor="gerant_password">
                                    Mot de passe <span className="text-destructive">*</span>
                                </Label>
                                <div className="relative">
                                    <Input
                                        id="gerant_password"
                                        type={showPwd ? 'text' : 'password'}
                                        value={data.gerant_password}
                                        onChange={(e) => setData('gerant_password', e.target.value)}
                                        placeholder="Minimum 8 caractères"
                                        autoComplete="new-password"
                                        className="pr-10"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPwd((p) => !p)}
                                        className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                        tabIndex={-1}
                                    >
                                        {showPwd ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                    </button>
                                </div>
                                {errors.gerant_password && (
                                    <p className="text-xs text-destructive">{errors.gerant_password}</p>
                                )}
                            </div>

                            {/* Confirmation */}
                            <div className="space-y-1.5">
                                <Label htmlFor="gerant_password_confirmation">
                                    Confirmer le mot de passe <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="gerant_password_confirmation"
                                    type={showPwd ? 'text' : 'password'}
                                    value={data.gerant_password_confirmation}
                                    onChange={(e) => setData('gerant_password_confirmation', e.target.value)}
                                    placeholder="Répétez le mot de passe"
                                    autoComplete="new-password"
                                />
                                {errors.gerant_password_confirmation && (
                                    <p className="text-xs text-destructive">{errors.gerant_password_confirmation}</p>
                                )}
                            </div>
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
                    <Button type="submit" form="create-agence-form" disabled={processing}>
                        {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        Créer l'agence
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
