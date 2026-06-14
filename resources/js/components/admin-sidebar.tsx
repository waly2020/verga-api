import { Link } from '@inertiajs/react';
import {
    Archive,
    Banknote,
    Building2,
    CreditCard,
    LayoutGrid,
    MessageSquareWarning,
    Package,
    ShoppingCart,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import admin from '@/routes/admin';
import type { NavItem } from '@/types';

const adminNavItems: NavItem[] = [
    { title: 'Tableau de bord', href: admin.dashboard(), icon: LayoutGrid },
    { title: 'Agences', href: admin.agences.index(), icon: Building2 },
    { title: 'Offres', href: admin.offres.index(), icon: Package },
    { title: 'Commandes', href: admin.commandes.index(), icon: ShoppingCart },
    { title: 'Colis', href: admin.colis.index(), icon: Archive },
    { title: 'Paiements', href: admin.paiements.index(), icon: CreditCard },
    { title: 'Reversements', href: admin.reversements.index(), icon: Banknote },
    { title: 'Réclamations', href: admin.reclamations.index(), icon: MessageSquareWarning },
    { title: 'Collaborateurs', href: admin.collaborateurs.index(), icon: Users },
];

export function AdminSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={admin.dashboard().url} prefetch>
                                <AppLogo />
                                <div className="flex flex-col">
                                    <span className="truncate text-sm font-semibold">VERGA</span>
                                    <span className="truncate text-xs text-muted-foreground">Administration</span>
                                </div>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={adminNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
