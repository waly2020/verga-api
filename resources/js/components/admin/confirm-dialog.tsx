import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

interface ConfirmDialogProps {
    trigger: React.ReactNode;
    title: string;
    description: string;
    confirmLabel?: string;
    variant?: 'destructive' | 'default';
    onConfirm: () => void;
    children?: React.ReactNode;
}

export function ConfirmDialog({
    trigger,
    title,
    description,
    confirmLabel = 'Confirmer',
    variant = 'destructive',
    onConfirm,
    children,
}: ConfirmDialogProps) {
    const [open, setOpen] = useState(false);

    function handleConfirm() {
        onConfirm();
        setOpen(false);
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>
                {children}
                <DialogFooter>
                    <Button variant="outline" onClick={() => setOpen(false)}>
                        Annuler
                    </Button>
                    <Button variant={variant} onClick={handleConfirm}>
                        {confirmLabel}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
