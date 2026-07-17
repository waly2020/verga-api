import { useForm } from '@inertiajs/react';
import { Loader2, Shield } from 'lucide-react';
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
import type { AgenceRoleFormData, AgenceRoleRow } from '@/types';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    agenceRole?: AgenceRoleRow | null;
}

const defaultForm: AgenceRoleFormData = {
    slug: '',
    nom: '',
    description: '',
    actif: true,
};

function toFormData(agenceRole: AgenceRoleRow): AgenceRoleFormData {
    return {
        slug: agenceRole.slug,
        nom: agenceRole.nom,
        description: agenceRole.description ?? '',
        actif: Boolean(agenceRole.actif),
    };
}

export function AgenceRoleFormDialog({ open, onOpenChange, agenceRole }: Props) {
    const isEdit = Boolean(agenceRole);
    const formId = isEdit ? 'agence-role-form-edit' : 'agence-role-form-create';

    const { data, setData, post, patch, processing, errors, reset, clearErrors } =
        useForm<AgenceRoleFormData>(agenceRole ? toFormData(agenceRole) : defaultForm);

    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();
        setData(agenceRole ? toFormData(agenceRole) : defaultForm);
    }, [open, agenceRole, setData, clearErrors]);

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

        if (isEdit && agenceRole) {
            patch(admin.agenceRoles.update(agenceRole.id).url, options);
        } else {
            post(admin.agenceRoles.store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Shield className="h-4 w-4 text-primary" />
                        {isEdit ? 'Modifier le rôle agence' : 'Nouveau rôle agence'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEdit
                            ? 'Mettez à jour le libellé et la description affichés dans le back-office agence.'
                            : 'Créez un rôle que les agences pourront assigner à leurs utilisateurs.'}
                    </DialogDescription>
                </DialogHeader>

                <form id={formId} onSubmit={submit} className="space-y-4 py-2">
                    {!isEdit && (
                        <div className="space-y-1.5">
                            <Label htmlFor="agence-role-slug">
                                Code (slug) <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="agence-role-slug"
                                value={data.slug}
                                onChange={(e) => setData('slug', e.target.value.toLowerCase())}
                                placeholder="Ex : support, logistique..."
                                autoFocus
                            />
                            <p className="text-xs text-muted-foreground">
                                Lettres minuscules, chiffres et tirets uniquement. Non modifiable ensuite.
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
                        <Label htmlFor="agence-role-nom">
                            Nom <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="agence-role-nom"
                            value={data.nom}
                            onChange={(e) => setData('nom', e.target.value)}
                            placeholder="Ex : Support client"
                            autoFocus={isEdit}
                        />
                        {errors.nom && <p className="text-xs text-destructive">{errors.nom}</p>}
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="agence-role-description">
                            Description
                            <span className="ml-1 text-xs font-normal text-muted-foreground">(optionnel)</span>
                        </Label>
                        <textarea
                            id="agence-role-description"
                            value={data.description}
                            onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) =>
                                setData('description', e.target.value)
                            }
                            placeholder="Usage et contexte de ce rôle…"
                            rows={2}
                            className="border-input bg-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[60px] w-full resize-none rounded-md border px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1"
                        />
                        {errors.description && (
                            <p className="text-xs text-destructive">{errors.description}</p>
                        )}
                    </div>

                    <div className="flex items-center gap-2 rounded-lg border p-4">
                        <Checkbox
                            id="agence-role-actif"
                            checked={data.actif}
                            onCheckedChange={(v) => setData('actif', v === true)}
                        />
                        <Label htmlFor="agence-role-actif" className="cursor-pointer font-normal">
                            Rôle actif (assignable par les agences)
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
