import { Head, useForm } from '@inertiajs/react';
import { Building2, Loader2, Mail, MessageSquare, Smartphone, UserCircle } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
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

type Audience = 'clients' | 'agences' | 'manual';

type FormData = {
    audience: Audience;
    subject: string;
    message: string;
    emails: string;
    action_label: string;
    action_url: string;
};

type Props = {
    stats: {
        clients: number;
        agences: number;
    };
};

const textareaClassName =
    'border-input bg-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[80px] w-full resize-y rounded-md border px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1';

export default function MassMailIndex({ stats }: Props) {
    const [channel, setChannel] = useState<'mail' | 'sms'>('mail');

    const { data, setData, post, processing, errors, reset } = useForm<FormData>({
        audience: 'clients',
        subject: '',
        message: '',
        emails: '',
        action_label: '',
        action_url: '',
    });

    const submitMail = (e: React.FormEvent) => {
        e.preventDefault();
        post(admin.notifications.masse.send().url, {
            preserveScroll: true,
            onSuccess: () => {
                reset('subject', 'message', 'emails', 'action_label', 'action_url');
            },
        });
    };

    const audienceHint = {
        clients: `${stats.clients} client(s) actif(s) avec e-mail`,
        agences: `${stats.agences} gérant(s) d'agence avec e-mail`,
        manual: 'Saisissez une ou plusieurs adresses séparées par des virgules ou des retours à la ligne',
    }[data.audience];

    return (
        <>
            <Head title="Envoi en masse" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Envoi en masse</h1>
                    <p className="text-sm text-muted-foreground">
                        Diffusez un e-mail à tous les clients, toutes les agences ou à une liste
                        d&apos;adresses personnalisées.
                    </p>
                </div>

                <div className="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        variant={channel === 'mail' ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => setChannel('mail')}
                    >
                        <Mail className="mr-2 h-4 w-4" />
                        E-mail
                    </Button>
                    <Button type="button" variant="outline" size="sm" disabled className="opacity-70">
                        <Smartphone className="mr-2 h-4 w-4" />
                        SMS
                        <Badge variant="secondary" className="ml-2 text-[10px]">
                            Bientôt
                        </Badge>
                    </Button>
                </div>

                {channel === 'mail' ? (
                    <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <MessageSquare className="h-4 w-4 text-primary" />
                                    Campagne e-mail
                                </CardTitle>
                                <CardDescription>
                                    Les envois sont mis en file d&apos;attente pour ne pas bloquer
                                    l&apos;interface.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={submitMail} className="space-y-6">
                                    <div className="space-y-1.5">
                                        <Label htmlFor="audience">Audience</Label>
                                        <Select
                                            value={data.audience}
                                            onValueChange={(value) => setData('audience', value as Audience)}
                                        >
                                            <SelectTrigger id="audience">
                                                <SelectValue placeholder="Choisir une audience" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="clients">
                                                    <span className="flex items-center gap-2">
                                                        <UserCircle className="h-4 w-4" />
                                                        Tous les clients ({stats.clients})
                                                    </span>
                                                </SelectItem>
                                                <SelectItem value="agences">
                                                    <span className="flex items-center gap-2">
                                                        <Building2 className="h-4 w-4" />
                                                        Toutes les agences ({stats.agences})
                                                    </span>
                                                </SelectItem>
                                                <SelectItem value="manual">
                                                    <span className="flex items-center gap-2">
                                                        <Mail className="h-4 w-4" />
                                                        Adresses manuelles
                                                    </span>
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {errors.audience && (
                                            <p className="text-xs text-destructive">{errors.audience}</p>
                                        )}
                                        <p className="text-xs text-muted-foreground">{audienceHint}</p>
                                    </div>

                                    {data.audience === 'manual' && (
                                        <div className="space-y-1.5">
                                            <Label htmlFor="emails">
                                                Adresses e-mail{' '}
                                                <span className="text-destructive">*</span>
                                            </Label>
                                            <textarea
                                                id="emails"
                                                value={data.emails}
                                                onChange={(e) => setData('emails', e.target.value)}
                                                placeholder={'client@example.com\nagence@example.com'}
                                                rows={4}
                                                className={textareaClassName}
                                            />
                                            {errors.emails && (
                                                <p className="text-xs text-destructive">{errors.emails}</p>
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
                                            placeholder="Information importante VERGA"
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
                                            placeholder={'Bonjour,\n\nNous vous informons que...'}
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
                                                    Envoyer la campagne
                                                </>
                                            )}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        <div className="space-y-4">
                            <Card>
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-sm">Récapitulatif</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3 text-sm">
                                    <div className="flex items-center justify-between">
                                        <span className="text-muted-foreground">Clients</span>
                                        <span className="font-medium">{stats.clients}</span>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span className="text-muted-foreground">Agences</span>
                                        <span className="font-medium">{stats.agences}</span>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                ) : (
                    <Card className="border-dashed">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Smartphone className="h-4 w-4" />
                                SMS de masse
                            </CardTitle>
                            <CardDescription>
                                Fonctionnalité prévue — intégration opérateur SMS à venir.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="text-sm text-muted-foreground">
                            <p>
                                Vous pourrez bientôt envoyer des SMS aux clients et agences depuis
                                cette page.
                            </p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

MassMailIndex.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Notifications', href: admin.notifications.masse.index().url },
        { title: 'Envoi en masse' },
    ],
};
