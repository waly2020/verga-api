import { useForm } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { useMemo } from 'react';
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

export type AgenceReversementOption = {
    id: string;
    nom: string;
    montant_solde: number;
    montant_en_attente: number;
    montant_disponible: number;
};

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    agences: AgenceReversementOption[];
}

type FormData = {
    agence_id: string;
    montant: string;
    periode: string;
};

const fmtFcfa = (n: number) => `${n.toLocaleString('fr-FR')} FCFA`;

const currentPeriode = () => {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
};

export function CreateReversementDialog({ open, onOpenChange, agences }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm<FormData>({
        agence_id: '',
        montant: '',
        periode: currentPeriode(),
    });

    const selectedAgence = useMemo(
        () => agences.find((agence) => agence.id === data.agence_id) ?? null,
        [agences, data.agence_id],
    );

    const montantSaisi = Number(data.montant);
    const montantDepasseSolde =
        selectedAgence !== null &&
        data.montant !== '' &&
        !Number.isNaN(montantSaisi) &&
        montantSaisi > selectedAgence.montant_disponible;

    const handleOpenChange = (value: boolean) => {
        if (!value) {
            reset();
            setData('periode', currentPeriode());
        }

        onOpenChange(value);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        post(admin.reversements.store().url, {
            onSuccess: () => {
                reset();
                setData('periode', currentPeriode());
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Nouveau reversement</DialogTitle>
                    <DialogDescription>
                        Enregistrez un reversement en attente. Le solde de l'agence sera impacté uniquement après validation.
                    </DialogDescription>
                </DialogHeader>

                <form id="create-reversement-form" onSubmit={submit} className="space-y-4 py-2">
                    <div className="space-y-1.5">
                        <Label htmlFor="agence_id">
                            Agence <span className="text-destructive">*</span>
                        </Label>
                        <Select
                            value={data.agence_id}
                            onValueChange={(value) => setData('agence_id', value)}
                        >
                            <SelectTrigger id="agence_id">
                                <SelectValue placeholder="Choisir une agence" />
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

                    {selectedAgence && (
                        <div className="rounded-lg border bg-muted/40 p-4 text-sm">
                            <p className="font-medium">Situation financière</p>
                            <div className="mt-2 space-y-1 text-muted-foreground">
                                <p>Solde courant : <span className="font-mono font-medium text-foreground">{fmtFcfa(selectedAgence.montant_solde)}</span></p>
                                {selectedAgence.montant_en_attente > 0 && (
                                    <p>En attente de validation : <span className="font-mono font-medium text-foreground">{fmtFcfa(selectedAgence.montant_en_attente)}</span></p>
                                )}
                                <p className="text-foreground">
                                    Solde disponible :{' '}
                                    <span className="font-mono font-semibold text-primary">
                                        {fmtFcfa(selectedAgence.montant_disponible)}
                                    </span>
                                </p>
                            </div>
                        </div>
                    )}

                    <div className="space-y-1.5">
                        <Label htmlFor="montant">
                            Montant <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="montant"
                            type="number"
                            min="1"
                            step="1"
                            value={data.montant}
                            onChange={(e) => setData('montant', e.target.value)}
                            placeholder="Ex. 30000"
                        />
                        {montantDepasseSolde && (
                            <p className="text-xs text-destructive">
                                Le montant dépasse le solde disponible ({fmtFcfa(selectedAgence!.montant_disponible)}).
                            </p>
                        )}
                        {errors.montant && (
                            <p className="text-xs text-destructive">{errors.montant}</p>
                        )}
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="periode">
                            Période <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="periode"
                            type="month"
                            value={data.periode}
                            onChange={(e) => setData('periode', e.target.value)}
                        />
                        {errors.periode && (
                            <p className="text-xs text-destructive">{errors.periode}</p>
                        )}
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
                    <Button
                        type="submit"
                        form="create-reversement-form"
                        disabled={
                            processing ||
                            !data.agence_id ||
                            !data.montant ||
                            montantDepasseSolde
                        }
                    >
                        {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        Enregistrer
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
