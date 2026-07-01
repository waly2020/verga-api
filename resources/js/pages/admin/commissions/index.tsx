import { Head } from '@inertiajs/react';
import { Percent } from 'lucide-react';
import {
    CommissionConfigCard
    
} from '@/components/admin/commission-config-card';
import type {CommissionConfig} from '@/components/admin/commission-config-card';
import admin from '@/routes/admin';

interface Props {
    client: CommissionConfig;
    agence: CommissionConfig;
}

export default function CommissionsIndex({ client, agence }: Props) {
    return (
        <>
            <Head title="Commissions" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Commissions</h1>
                    <p className="text-sm text-muted-foreground">
                        Configurez les commissions globales appliquées automatiquement lors de chaque paiement
                        validé.
                    </p>
                </div>

                <div className="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100">
                    <Percent className="mt-0.5 h-4 w-4 shrink-0" />
                    <p>
                        Deux commissions distinctes peuvent s&apos;appliquer sur un même paiement : une côté{' '}
                        <strong>client</strong> et une côté <strong>agence</strong>. La configuration se fait
                        uniquement depuis ce back-office.
                    </p>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <CommissionConfigCard
                        config={client}
                        updateUrl={admin.commissions.update('client').url}
                    />
                    <CommissionConfigCard
                        config={agence}
                        updateUrl={admin.commissions.update('agence').url}
                    />
                </div>
            </div>
        </>
    );
}

CommissionsIndex.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard().url },
        { title: 'Commissions' },
    ],
};
