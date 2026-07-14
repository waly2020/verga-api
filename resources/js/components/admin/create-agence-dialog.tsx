import { useForm } from '@inertiajs/react';
import { Eye, EyeOff, Loader2, Plus, Trash2 } from 'lucide-react';
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

type DocumentRow = {
    fichier: File | null;
    type_document: string;
};

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
    logo: File | null;
    documents: DocumentRow[];
};

const emptyForm = (): FormData => ({
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
    logo: null,
    documents: [],
});

export function CreateAgenceDialog({ open, onOpenChange, typesAgences }: Props) {
    const [showPwd, setShowPwd] = useState(false);
    const [logoPreview, setLogoPreview] = useState<string | null>(null);

    const { data, setData, post, processing, errors, reset, clearErrors, transform } = useForm<FormData>(emptyForm());

    const handleOpenChange = (value: boolean) => {
        if (!value) {
            reset();
            clearErrors();
            setLogoPreview(null);
            setShowPwd(false);
        }

        onOpenChange(value);
    };

    const setLogo = (file: File | null) => {
        setData('logo', file);
        setLogoPreview((prev) => {
            if (prev) {
                URL.revokeObjectURL(prev);
            }

            return file ? URL.createObjectURL(file) : null;
        });
    };

    const addDocument = () => {
        setData('documents', [...data.documents, { fichier: null, type_document: '' }]);
    };

    const updateDocument = (index: number, patch: Partial<DocumentRow>) => {
        setData(
            'documents',
            data.documents.map((doc, i) => (i === index ? { ...doc, ...patch } : doc)),
        );
    };

    const removeDocument = (index: number) => {
        setData(
            'documents',
            data.documents.filter((_, i) => i !== index),
        );
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        transform((formData) => ({
            ...formData,
            documents: formData.documents.filter(
                (doc) => doc.fichier && doc.type_document.trim() !== '',
            ),
        }));

        post(admin.agences.store().url, {
            forceFormData: true,
            onSuccess: () => {
                reset();
                clearErrors();
                setLogoPreview(null);
                onOpenChange(false);
            },
        });
    };

    const field = (
        id: keyof Omit<FormData, 'logo' | 'documents'>,
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
                        Renseignez les informations de l'agence, le logo, les documents et le compte gérant.
                    </DialogDescription>
                </DialogHeader>

                <form id="create-agence-form" onSubmit={submit} className="space-y-6 py-2">

                    <div className="space-y-4">
                        <h3 className="text-sm font-semibold text-foreground">Informations de l'agence</h3>

                        <div className="grid gap-4 sm:grid-cols-2">
                            {field('nom', 'Nom de l\'agence', { placeholder: 'Agence Transit Express' })}
                            {field('email', 'Email agence', { type: 'email', placeholder: 'contact@agence.com' })}
                            {field('telephone', 'Téléphone', { placeholder: '+241 01 23 45 67' })}

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

                    <div className="relative">
                        <div className="absolute inset-0 flex items-center">
                            <span className="w-full border-t border-border" />
                        </div>
                        <div className="relative flex justify-center">
                            <span className="bg-background px-3 text-xs text-muted-foreground">
                                Logo & documents
                            </span>
                        </div>
                    </div>

                    <div className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="logo">
                                Logo
                                <span className="ml-1 text-xs font-normal text-muted-foreground">(optionnel)</span>
                            </Label>
                            <Input
                                id="logo"
                                type="file"
                                accept="image/*"
                                onChange={(e) => setLogo(e.target.files?.[0] ?? null)}
                            />
                            {logoPreview && (
                                <img
                                    src={logoPreview}
                                    alt="Aperçu logo"
                                    className="mt-2 h-16 w-16 rounded-lg border object-cover"
                                />
                            )}
                            {errors.logo && <p className="text-xs text-destructive">{errors.logo}</p>}
                        </div>

                        <div className="space-y-3">
                            <div className="flex items-center justify-between gap-2">
                                <Label>
                                    Documents
                                    <span className="ml-1 text-xs font-normal text-muted-foreground">(optionnel)</span>
                                </Label>
                                <Button type="button" variant="outline" size="sm" onClick={addDocument}>
                                    <Plus className="mr-1.5 h-3.5 w-3.5" />
                                    Ajouter
                                </Button>
                            </div>

                            {data.documents.length === 0 && (
                                <p className="text-xs text-muted-foreground">
                                    Pièce d'identité, registre de commerce, etc.
                                </p>
                            )}

                            {data.documents.map((doc, index) => (
                                <div key={index} className="grid gap-3 rounded-lg border p-3 sm:grid-cols-[1fr_1.2fr_auto]">
                                    <div className="space-y-1.5">
                                        <Label htmlFor={`doc-type-${index}`}>Type</Label>
                                        <Input
                                            id={`doc-type-${index}`}
                                            value={doc.type_document}
                                            onChange={(e) => updateDocument(index, { type_document: e.target.value })}
                                            placeholder="piece_identite"
                                        />
                                        {errors[`documents.${index}.type_document`] && (
                                            <p className="text-xs text-destructive">
                                                {errors[`documents.${index}.type_document`]}
                                            </p>
                                        )}
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label htmlFor={`doc-file-${index}`}>Fichier</Label>
                                        <Input
                                            id={`doc-file-${index}`}
                                            type="file"
                                            accept="image/*,.pdf,application/pdf"
                                            onChange={(e) => updateDocument(index, { fichier: e.target.files?.[0] ?? null })}
                                        />
                                        {errors[`documents.${index}.fichier`] && (
                                            <p className="text-xs text-destructive">
                                                {errors[`documents.${index}.fichier`]}
                                            </p>
                                        )}
                                    </div>
                                    <div className="flex items-end">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => removeDocument(index)}
                                            aria-label="Retirer le document"
                                        >
                                            <Trash2 className="h-4 w-4 text-destructive" />
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

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

                    <div className="space-y-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            {field('gerant_name', 'Nom complet', { placeholder: 'Jean Mbaye' })}
                            {field('gerant_email', 'Email de connexion', { type: 'email', placeholder: 'gerant@agence.com' })}

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
