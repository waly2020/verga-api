import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';

export default function PaiementLayout({ children }: { children: React.ReactNode }) {
    return (
        <div className="min-h-svh bg-background">
            <header className="border-b">
                <div className="mx-auto flex max-w-3xl items-center gap-3 px-6 py-4">
                    <Link href={home()} className="flex items-center gap-2 font-semibold">
                        <AppLogoIcon className="size-8 fill-current text-[var(--foreground)] dark:text-white" />
                        <span>VERGA</span>
                    </Link>
                </div>
            </header>
            <main className="mx-auto max-w-3xl px-6 py-8">
                {children}
            </main>
        </div>
    );
}
