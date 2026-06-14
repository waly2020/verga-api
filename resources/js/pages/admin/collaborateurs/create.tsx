import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Eye, EyeOff, Loader2, UserPlus } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import admin from '@/routes/admin';

type FormData = {
    name: string;
    email: string;
    role: string;
    password: string;
    password_confirmation: string;
};

export default function CollaborateurCreate() {
    const [showPwd, setShowPwd] = useState(false);
    const [showPwdConfirm, setShowPwdConfirm] = useState(false);

    const { data, setData, post, processing, errors } = useForm<FormData>({
        name: '',
        email: '',
        role: 'collaborateur',
        password: '',
        password_confirmation: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(admin.collaborateurs.store().url);
    };

    return (
        <>
            <Head title="Nouveau collaborateur" />
            <div className="flex flex-1 flex-col gap-6 p-6">

                <div>
                    <Button variant="ghost" size="sm" asChild className="-ml-2 mb-2">
                        <Link href={admin.collaborateurs.index().url}>
                            <ArrowLeft className="mr-1.5 h-4 w-4" />
                            Retour aux collaborateurs
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-semibold tracking-tight">Nouveau collaborateur</h1>
                    <p className="text-sm text-muted-foreground">Créez un compte administrateur ou collaborateur</p>
                </div>

                <Card className="max-w-lg">
                    <CardHeader className="pb-4">
                        <CardTitle className="flex items-center gap-2 text-base">
                            <UserPlus className="h-4 w-4 text-primary" />
                            Informations du compte
                        </CardTitle>
                        <CardDescription>
                            Le collaborateur pourra se connecter immédiatement avec ces identifiants.
                        </CardDescription>
                    </CardHeader>

                    <CardContent>
                        <form onSubmit={submit} className="space-y-5">

                            {/* Nom */}
                            <div className="space-y-1.5">
                                <Label htmlFor="name">Nom complet <span className="text-destructive">*</span></Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="Jean Dupont"
                                    autoComplete="name"
                                    autoFocus
                                />
                                {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                            </div>

                            {/* Email */}
                            <div className="space-y-1.5">
                                <Label htmlFor="email">Adresse email <span className="text-destructive">*</span></Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    placeholder="jean@verga.com"
                                    autoComplete="email"
                                />
                                {errors.email && <p className="text-xs text-destructive">{errors.email}</p>}
                            </div>

                            {/* Rôle */}
                            <div className="space-y-1.5">
                                <Label htmlFor="role">Rôle <span className="text-destructive">*</span></Label>
                                <Select
                                    value={data.role}
                                    onValueChange={(v) => setData('role', v)}
                                >
                                    <SelectTrigger id="role">
                                        <SelectValue placeholder="Choisir un rôle" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="collaborateur">Collaborateur</SelectItem>
                                        <SelectItem value="admin">Administrateur</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.role && <p className="text-xs text-destructive">{errors.role}</p>}
                                <p className="text-xs text-muted-foreground">
                                    {data.role === 'admin'
                                        ? 'Accès complet à toutes les fonctionnalités.'
                                        : 'Accès limité — ne peut pas supprimer d\'autres comptes.'}
                                </p>
                            </div>

                            {/* Mot de passe */}
                            <div className="space-y-1.5">
                                <Label htmlFor="password">Mot de passe <span className="text-destructive">*</span></Label>
                                <div className="relative">
                                    <Input
                                        id="password"
                                        type={showPwd ? 'text' : 'password'}
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
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
                                {errors.password && <p className="text-xs text-destructive">{errors.password}</p>}
                            </div>

                            {/* Confirmation mot de passe */}
                            <div className="space-y-1.5">
                                <Label htmlFor="password_confirmation">Confirmer le mot de passe <span className="text-destructive">*</span></Label>
                                <div className="relative">
                                    <Input
                                        id="password_confirmation"
                                        type={showPwdConfirm ? 'text' : 'password'}
                                        value={data.password_confirmation}
                                        onChange={(e) => setData('password_confirmation', e.target.value)}
                                        placeholder="Répétez le mot de passe"
                                        autoComplete="new-password"
                                        className="pr-10"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPwdConfirm((p) => !p)}
                                        className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                        tabIndex={-1}
                                    >
                                        {showPwdConfirm ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                    </button>
                                </div>
                                {errors.password_confirmation && (
                                    <p className="text-xs text-destructive">{errors.password_confirmation}</p>
                                )}
                            </div>

                            {/* Actions */}
                            <div className="flex items-center gap-3 pt-2">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                    Créer le compte
                                </Button>
                                <Button type="button" variant="outline" asChild>
                                    <Link href={admin.collaborateurs.index().url}>Annuler</Link>
                                </Button>
                            </div>

                        </form>
                    </CardContent>
                </Card>

            </div>
        </>
    );
}

CollaborateurCreate.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Collaborateurs', href: admin.collaborateurs.index().url },
        { title: 'Nouveau collaborateur' },
    ],
};
