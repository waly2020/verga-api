import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { APP_BRAND_NAME } from '@/lib/brand';
import { home } from '@/routes';

export default function PaiementLayout({ children }: { children: React.ReactNode }) {
    return (
        <div className="min-h-svh bg-muted">
            <header className="border-b bg-background">
                <div className="mx-auto flex max-w-3xl items-center gap-3 px-6 py-4">
                    <Link href={home()} className="flex items-center gap-3 font-semibold">
                        <AppLogoIcon className="h-10 w-auto" />
                        <span className="sr-only">{APP_BRAND_NAME}</span>
                    </Link>
                </div>
            </header>
            <main className="mx-auto max-w-3xl px-6 py-8">
                {children}
            </main>
        </div>
    );
}
