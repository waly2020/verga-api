import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { toast } from 'sonner';

interface Flash {
    success?: string;
    error?: string;
}

export function useFlashToast(): void {
    const { flash } = usePage<{ flash: Flash }>().props;

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    }, [flash?.success, flash?.error]);
}
