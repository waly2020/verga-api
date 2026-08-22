import { Head, useForm } from '@inertiajs/react';
import { Building2, Loader2, Mail, UserCircle } from 'lucide-react';
import { useMemo } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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

type RecipientType = 'client' | 'agence' | 'email';

type RecipientOption = {
    id: string;
    label: string;
    email: string;
};

type FormData = {
    recipient_type: RecipientType;
    client_id: string;
    agence_id: string;
    email: string;
    subject: string;
    message: string;
    action_label: string;
    action_url: string;
};

type Props = {
    clients: RecipientOption[];
    agences: RecipientOption[];
};

const textareaClassName =
    'border-input bg-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[80px] w-full resize-y rounded-md border px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1';

export default function TargetedMailIndex({ clients, agences }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm<FormData>({
        recipient_type: 'client',
        client_id: clients[0]?.id ?? '',
        agence_id: agences[0]?.id ?? '',
        email: '',
        subject: '',
        message: '',
        action_label: '',
        action_url: '',
    });

    const resolvedEmail = useMemo(() => {
        if (data.recipient_type === 'client') {
            return clients.find((client) => client.id === data.client_id)?.email ?? null;
        }

        if (data.recipient_type === 'agence') {
            return agences.find((agence) => agence.id === data.agence_id)?.email ?? null;
        }

        return data.email || null;
    }, [agences, clients, data.agence_id, data.client_id, data.email, data.recipient_type]);

    const submitMail = (e: React.FormEvent) => {
        e.preventDefault();
        post(admin.notifications.cible.send().url, {
            preserveScroll: true,
            onSuccess: () => {
                reset('subject', 'message', 'action_label', 'action_url', 'email');
            },
        });
    };

    return (
        <>
            <Head title="Envoi ciblé" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Envoi ciblé</h1>
                    <p className="text-sm text-muted-foreground">
                        Envoyez un e-mail à une seule personne : client, gérant d&apos;agence ou
                        adresse personnalisée.
                    </p>
                </div>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
                    <Card className="max-w-3xl">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Mail className="h-4 w-4 text-primary" />
                                Message individuel
                            </CardTitle>
                            <CardDescription>
                                L&apos;e-mail est mis en file d&apos;attente après validation.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submitMail} className="space-y-6">
                                <div className="space-y-1.5">
                                    <Label htmlFor="recipient_type">Destinataire</Label>
                                    <Select
                                        value={data.recipient_type}
                                        onValueChange={(value) =>
                                            setData('recipient_type', value as RecipientType)
                                        }
                                    >
                                        <SelectTrigger id="recipient_type">
                                            <SelectValue placeholder="Choisir un type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="client">
                                                <span className="flex items-center gap-2">
                                                    <UserCircle className="h-4 w-4" />
                                                    Client
                                                </span>
                                            </SelectItem>
                                            <SelectItem value="agence">
                                                <span className="flex items-center gap-2">
                                                    <Building2 className="h-4 w-4" />
                                                    Agence (gérant)
                                                </span>
                                            </SelectItem>
                                            <SelectItem value="email">
                                                <span className="flex items-center gap-2">
                                                    <Mail className="h-4 w-4" />
                                                    Adresse e-mail
                                                </span>
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.recipient_type && (
                                        <p className="text-xs text-destructive">{errors.recipient_type}</p>
                                    )}
                                </div>

                                {data.recipient_type === 'client' && (
                                    <div className="space-y-1.5">
                                        <Label htmlFor="client_id">
                                            Client <span className="text-destructive">*</span>
                                        </Label>
                                        <Select
                                            value={data.client_id}
                                            onValueChange={(value) => setData('client_id', value)}
                                        >
                                            <SelectTrigger id="client_id">
                                                <SelectValue placeholder="Sélectionner un client" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {clients.map((client) => (
                                                    <SelectItem key={client.id} value={client.id}>
                                                        {client.label} — {client.email}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.client_id && (
                                            <p className="text-xs text-destructive">{errors.client_id}</p>
                                        )}
                                    </div>
                                )}

                                {data.recipient_type === 'agence' && (
                                    <div className="space-y-1.5">
                                        <Label htmlFor="agence_id">
                                            Agence <span className="text-destructive">*</span>
                                        </Label>
                                        <Select
                                            value={data.agence_id}
                                            onValueChange={(value) => setData('agence_id', value)}
                                        >
                                            <SelectTrigger id="agence_id">
                                                <SelectValue placeholder="Sélectionner une agence" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {agences.map((agence) => (
                                                    <SelectItem key={agence.id} value={agence.id}>
                                                        {agence.label} — {agence.email}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.agence_id && (
                                            <p className="text-xs text-destructive">{errors.agence_id}</p>
                                        )}
                                    </div>
                                )}

                                {data.recipient_type === 'email' && (
                                    <div className="space-y-1.5">
                                        <Label htmlFor="email">
                                            Adresse e-mail <span className="text-destructive">*</span>
                                        </Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            placeholder="destinataire@example.com"
                                        />
                                        {errors.email && (
                                            <p className="text-xs text-destructive">{errors.email}</p>
                                        )}
                                    </div>
                                )}

                                <div className="space-y-1.5">
                                    <Label htmlFor="subject">
                                        Objet <span className="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="subject"
                                        value={data.subject}
                                        onChange={(e) => setData('subject', e.target.value)}
                                        placeholder="Objet du message"
                                    />
                                    {errors.subject && (
                                        <p className="text-xs text-destructive">{errors.subject}</p>
                                    )}
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="message">
                                        Message <span className="text-destructive">*</span>
                                    </Label>
                                    <textarea
                                        id="message"
                                        value={data.message}
                                        onChange={(e) => setData('message', e.target.value)}
                                        placeholder={'Bonjour,\n\n...'}
                                        rows={8}
                                        className={textareaClassName}
                                    />
                                    {errors.message && (
                                        <p className="text-xs text-destructive">{errors.message}</p>
                                    )}
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-1.5">
                                        <Label htmlFor="action_label">Libellé du bouton (optionnel)</Label>
                                        <Input
                                            id="action_label"
                                            value={data.action_label}
                                            onChange={(e) => setData('action_label', e.target.value)}
                                            placeholder="En savoir plus"
                                        />
                                        {errors.action_label && (
                                            <p className="text-xs text-destructive">{errors.action_label}</p>
                                        )}
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label htmlFor="action_url">URL du bouton (optionnel)</Label>
                                        <Input
                                            id="action_url"
                                            type="url"
                                            value={data.action_url}
                                            onChange={(e) => setData('action_url', e.target.value)}
                                            placeholder="https://verga.com/..."
                                        />
                                        {errors.action_url && (
                                            <p className="text-xs text-destructive">{errors.action_url}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="flex justify-end">
                                    <Button type="submit" disabled={processing}>
                                        {processing ? (
                                            <>
                                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                                Envoi en cours...
                                            </>
                                        ) : (
                                            <>
                                                <Mail className="mr-2 h-4 w-4" />
                                                Envoyer l&apos;e-mail
                                            </>
                                        )}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm">Destinataire</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            {resolvedEmail ? (
                                <>
                                    <p className="text-muted-foreground">Adresse utilisée :</p>
                                    <p className="font-medium break-all">{resolvedEmail}</p>
                                </>
                            ) : (
                                <p className="text-muted-foreground">
                                    Aucune adresse e-mail disponible pour cette sélection.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

TargetedMailIndex.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Notifications', href: admin.notifications.masse.index().url },
        { title: 'Envoi ciblé' },
    ],
};
