import { APP_BRAND_NAME, APP_LOGO_SRC } from '@/lib/brand';
import { cn } from '@/lib/utils';

export default function AppLogoIcon({ className }: { className?: string }) {
    return (
        <img
            src={APP_LOGO_SRC}
            alt={APP_BRAND_NAME}
            className={cn('object-contain', className)}
        />
    );
}
