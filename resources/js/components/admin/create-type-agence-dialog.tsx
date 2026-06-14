import { useForm } from '@inertiajs/react';
import { Loader2, Tag } from 'lucide-react';
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
import admin from '@/routes/admin';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

type FormData = {
    nom: string;
    description: string;
};

export function CreateTypeAgenceDialog({ open, onOpenChange }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm<FormData>({
        nom: '',
        description: '',
    });

    const handleOpenChange = (value: boolean) => {
        if (!value) reset();
        onOpenChange(value);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(admin.typeAgences.store().url, {
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Tag className="h-4 w-4 text-primary" />
                        Nouveau type d'agence
                    </DialogTitle>
                    <DialogDescription>
                        Ajoutez une catégorie pour classifier les agences partenaires.
                    </DialogDescription>
                </DialogHeader>

                <form id="create-type-agence-form" onSubmit={submit} className="space-y-4 py-2">
                    <div className="space-y-1.5">
                        <Label htmlFor="type-nom">
                            Nom <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="type-nom"
                            value={data.nom}
                            onChange={(e) => setData('nom', e.target.value)}
                            placeholder="Ex : Transit maritime, Fret aérien…"
                            autoFocus
                        />
                        {errors.nom && <p className="text-xs text-destructive">{errors.nom}</p>}
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="type-description">
                            Description
                            <span className="ml-1 text-xs font-normal text-muted-foreground">(optionnel)</span>
                        </Label>
                        <textarea
                            id="type-description"
                            value={data.description}
                            onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => setData('description', e.target.value)}
                            placeholder="Décrivez brièvement ce type d'agence…"
                            rows={3}
                            className="border-input bg-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[80px] w-full resize-none rounded-md border px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1"
                        />
                        {errors.description && <p className="text-xs text-destructive">{errors.description}</p>}
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
                    <Button type="submit" form="create-type-agence-form" disabled={processing}>
                        {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        Créer
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
