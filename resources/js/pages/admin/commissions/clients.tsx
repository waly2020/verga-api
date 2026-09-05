import { Head, Link } from '@inertiajs/react';
import { Building2 } from 'lucide-react';
import { CommissionConfigCard } from '@/components/admin/commission-config-card';
import type { CommissionConfig } from '@/components/admin/commission-config-card';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';

interface Props {
    config: CommissionConfig;
}

export default function CommissionsClients({ config }: Props) {
    return (
        <>
            <Head title="Commission clients" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Commission clients</h1>
                        <p className="text-sm text-muted-foreground">
                            Frais VERGA ajoutés au sous-total de chaque versement. Une grille tarifaire
                            peut remplacer le taux unique.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={admin.commissions.agences()} prefetch>
                            <Building2 className="mr-2 h-4 w-4" />
                            Commission agences
                        </Link>
                    </Button>
                </div>

                <div className="max-w-3xl">
                    <CommissionConfigCard
                        config={config}
                        updateUrl={admin.commissions.update('client').url}
                    />
                </div>
            </div>
        </>
    );
}

CommissionsClients.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Commission clients', href: admin.commissions.clients().url },
    ],
};
