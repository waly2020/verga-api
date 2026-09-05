import AppLogoIcon from '@/components/app-logo-icon';
import { APP_BRAND_NAME } from '@/lib/brand';

export default function AppLogo({ subtitle }: { subtitle?: string }) {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-sidebar-border">
                <AppLogoIcon className="size-8" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold tracking-[0.18em]">
                    {APP_BRAND_NAME}
                </span>
                {subtitle && (
                    <span className="truncate text-xs text-muted-foreground">
                        {subtitle}
                    </span>
                )}
            </div>
        </>
    );
}
