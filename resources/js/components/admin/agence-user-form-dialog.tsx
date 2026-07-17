import { useForm } from '@inertiajs/react';
import { Loader2, UserRoundCog } from 'lucide-react';
import { useEffect } from 'react';
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
import type {
    AgenceRoleApi,
    AgenceSummary,
    AgenceUserFormData,
    AgenceUserRow,
} from '@/types';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    user?: AgenceUserRow | null;
    agences: AgenceSummary[];
    roles: AgenceRoleApi[];
}

const emptyForm: AgenceUserFormData = {
    agence_id: '',
    agence_role_id: '',
    name: '',
    email: '',
    telephone: '',
    password: '',
    password_confirmation: '',
    statut: 'actif',
};

function toFormData(user: AgenceUserRow): AgenceUserFormData {
    return {
        agence_id: user.agence_id,
        agence_role_id: user.agence_role_id,
        name: user.name,
        email: user.email,
        telephone: user.telephone ?? '',
        password: '',
        password_confirmation: '',
        statut: user.statut,
    };
}

export function AgenceUserFormDialog({
    open,
    onOpenChange,
    user,
    agences,
    roles,
}: Props) {
    const isEdit = Boolean(user);
    const formId = isEdit ? 'agence-user-edit' : 'agence-user-create';
    const { data, setData, post, patch, processing, errors, reset, clearErrors } =
        useForm<AgenceUserFormData>(user ? toFormData(user) : emptyForm);

    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();
        setData(user ? toFormData(user) : emptyForm);
    }, [open, user, clearErrors, setData]);

    const close = (value: boolean) => {
        if (!value) {
            reset();
            clearErrors();
        }

        onOpenChange(value);
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        const options = {
            onSuccess: () => close(false),
        };

        if (user) {
            patch(admin.agenceUsers.update(user.id).url, options);
        } else {
            post(admin.agenceUsers.store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={close}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <UserRoundCog className="h-4 w-4 text-primary" />
                        {isEdit ? 'Modifier l’utilisateur agence' : 'Nouvel utilisateur agence'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEdit
                            ? 'Modifiez les informations, le rôle ou le statut du collaborateur.'
                            : 'Créez un collaborateur et rattachez-le à une agence et à un rôle.'}
                    </DialogDescription>
                </DialogHeader>

                <form id={formId} onSubmit={submit} className="grid gap-4 py-2 sm:grid-cols-2">
                    <div className="space-y-1.5 sm:col-span-2">
                        <Label htmlFor="agence-user-agence">Agence</Label>
                        <Select
                            value={data.agence_id}
                            onValueChange={(value) => setData('agence_id', value)}
                            disabled={isEdit}
                        >
                            <SelectTrigger id="agence-user-agence">
                                <SelectValue placeholder="Sélectionner une agence" />
                            </SelectTrigger>
                            <SelectContent>
                                {agences.map((agence) => (
                                    <SelectItem key={agence.id} value={agence.id}>
                                        {agence.nom}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.agence_id && (
                            <p className="text-xs text-destructive">{errors.agence_id}</p>
                        )}
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="agence-user-name">Nom</Label>
                        <Input
                            id="agence-user-name"
                            value={data.name}
                            onChange={(event) => setData('name', event.target.value)}
                        />
                        {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="agence-user-email">Email</Label>
                        <Input
                            id="agence-user-email"
                            type="email"
                            value={data.email}
                            onChange={(event) => setData('email', event.target.value)}
                        />
                        {errors.email && <p className="text-xs text-destructive">{errors.email}</p>}
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="agence-user-telephone">Téléphone</Label>
                        <Input
                            id="agence-user-telephone"
                            value={data.telephone}
                            onChange={(event) => setData('telephone', event.target.value)}
                        />
                        {errors.telephone && (
                            <p className="text-xs text-destructive">{errors.telephone}</p>
                        )}
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="agence-user-role">Rôle</Label>
                        <Select
                            value={data.agence_role_id}
                            onValueChange={(value) => setData('agence_role_id', value)}
                        >
                            <SelectTrigger id="agence-user-role">
                                <SelectValue placeholder="Sélectionner un rôle" />
                            </SelectTrigger>
                            <SelectContent>
                                {roles.map((role) => (
                                    <SelectItem key={role.id} value={role.id}>
                                        {role.nom}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.agence_role_id && (
                            <p className="text-xs text-destructive">{errors.agence_role_id}</p>
                        )}
                    </div>

                    {!isEdit && (
                        <>
                            <div className="space-y-1.5">
                                <Label htmlFor="agence-user-password">Mot de passe</Label>
                                <Input
                                    id="agence-user-password"
                                    type="password"
                                    value={data.password}
                                    onChange={(event) => setData('password', event.target.value)}
                                />
                                {errors.password && (
                                    <p className="text-xs text-destructive">{errors.password}</p>
                                )}
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="agence-user-password-confirmation">
                                    Confirmation
                                </Label>
                                <Input
                                    id="agence-user-password-confirmation"
                                    type="password"
                                    value={data.password_confirmation}
                                    onChange={(event) =>
                                        setData('password_confirmation', event.target.value)
                                    }
                                />
                            </div>
                        </>
                    )}

                    <div className="space-y-1.5 sm:col-span-2">
                        <Label htmlFor="agence-user-statut">Statut</Label>
                        <Select
                            value={data.statut}
                            onValueChange={(value: 'actif' | 'suspendu') =>
                                setData('statut', value)
                            }
                        >
                            <SelectTrigger id="agence-user-statut">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="actif">Actif</SelectItem>
                                <SelectItem value="suspendu">Suspendu</SelectItem>
                            </SelectContent>
                        </Select>
                        {errors.statut && (
                            <p className="text-xs text-destructive">{errors.statut}</p>
                        )}
                    </div>
                </form>

                <DialogFooter>
                    <Button type="button" variant="outline" onClick={() => close(false)}>
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
