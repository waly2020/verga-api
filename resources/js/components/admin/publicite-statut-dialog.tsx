import { useForm } from '@inertiajs/react';
import { Loader2, RefreshCw } from 'lucide-react';
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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import admin from '@/routes/admin';

type PubliciteRow = {
    id: string;
    titre: string;
    statut: string;
};

const STATUT_LABELS: Record<string, string> = {
    validée: 'Valider (autoriser le paiement)',
    refusée: 'Refuser',
    retirée: 'Retirer du catalogue',
    publiée: 'Republier',
};

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    publicite: PubliciteRow | null;
    transitions: Record<string, string[]>;
}

export function PubliciteStatutDialog({ open, onOpenChange, publicite, transitions }: Props) {
    const options = publicite ? (transitions[publicite.statut] ?? []) : [];

    const { data, setData, patch, processing, errors, reset, clearErrors } = useForm({
        statut: '',
        motif: '',
    });

    useEffect(() => {
        if (!open || !publicite) {
            return;
        }

        clearErrors();
        setData({
            statut: options[0] ?? '',
            motif: '',
        });
    }, [open, publicite?.id]);

    const handleOpenChange = (value: boolean) => {
        if (!value) {
            reset();
        }

        onOpenChange(value);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!publicite) {
            return;
        }

        patch(admin.publicites.statut(publicite.id).url, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    const needsMotif = data.statut === 'refusée';
    const allowsMotif = data.statut === 'refusée' || data.statut === 'retirée';

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <RefreshCw className="h-4 w-4 text-primary" />
                            Changer le statut
                        </DialogTitle>
                        <DialogDescription>
                            {publicite ? `« ${publicite.titre} » — statut actuel : ${publicite.statut}` : ''}
                        </DialogDescription>
                    </DialogHeader>

                    {options.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Aucune action disponible pour ce statut.
                        </p>
                    ) : (
                        <>
                            <div className="space-y-1.5">
                                <Label htmlFor="publicite-statut">Nouveau statut</Label>
                                <Select
                                    value={data.statut}
                                    onValueChange={(value) => {
                                        setData('statut', value);
                                        if (value !== 'refusée' && value !== 'retirée') {
                                            setData('motif', '');
                                        }
                                    }}
                                >
                                    <SelectTrigger id="publicite-statut">
                                        <SelectValue placeholder="Choisir un statut" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.map((statut) => (
                                            <SelectItem key={statut} value={statut}>
                                                {STATUT_LABELS[statut] ?? statut}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.statut && <p className="text-xs text-destructive">{errors.statut}</p>}
                            </div>

                            {allowsMotif && (
                                <div className="space-y-1.5">
                                    <Label htmlFor="publicite-motif">
                                        Motif {needsMotif && <span className="text-destructive">*</span>}
                                    </Label>
                                    <textarea
                                        id="publicite-motif"
                                        required={needsMotif}
                                        value={data.motif}
                                        onChange={(e) => setData('motif', e.target.value)}
                                        placeholder={
                                            needsMotif
                                                ? 'Indiquez la raison du refus…'
                                                : 'Optionnel — raison du retrait…'
                                        }
                                        className="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    />
                                    {errors.motif && <p className="text-xs text-destructive">{errors.motif}</p>}
                                </div>
                            )}
                        </>
                    )}

                    <DialogFooter>
                        <Button type="submit" disabled={processing || options.length === 0}>
                            {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                            Enregistrer
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
