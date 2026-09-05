import { Head, Link } from '@inertiajs/react';
import { UserCircle } from 'lucide-react';
import { CommissionConfigCard } from '@/components/admin/commission-config-card';
import type { CommissionConfig } from '@/components/admin/commission-config-card';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';

interface Props {
    config: CommissionConfig;
}

export default function CommissionsAgences({ config }: Props) {
    return (
        <>
            <Head title="Commission agences" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Commission agences</h1>
                        <p className="text-sm text-muted-foreground">
                            Part prélevée sur chaque paiement au profit de VERGA, pour toutes les
                            agences (sauf si une destination impose son propre pourcentage).
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={admin.commissions.clients()} prefetch>
                            <UserCircle className="mr-2 h-4 w-4" />
                            Commission clients
                        </Link>
                    </Button>
                </div>

                <div className="max-w-3xl">
                    <CommissionConfigCard
                        config={config}
                        updateUrl={admin.commissions.update('agence').url}
                    />
                </div>
            </div>
        </>
    );
}

CommissionsAgences.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Commission agences', href: admin.commissions.agences().url },
    ],
};
