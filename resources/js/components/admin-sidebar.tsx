import { Link } from '@inertiajs/react';
import {
    Archive,
    Banknote,
    Building2,
    CreditCard,
    LayoutGrid,
    Globe,
    MapPin,
    Megaphone,
    Mail,
    MessageSquare,
    MessageSquareWarning,
    Package,
    Percent,
    Shield,
    ShoppingCart,
    Tag,
    UserCircle,
    UserRoundCog,
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
    {
        title: 'Agences',
        href: admin.agences.index(),
        icon: Building2,
        children: [
            { title: 'Agences', href: admin.agences.index(), icon: Building2 },
            { title: 'Rôles agence', href: admin.agenceRoles.index(), icon: Shield },
            {
                title: 'Utilisateurs agence',
                href: admin.agenceUsers.index(),
                icon: UserRoundCog,
            },
        ],
    },
    { title: 'Clients', href: admin.clients.index(), icon: UserCircle },
    { title: 'Offres', href: admin.offres.index(), icon: Package },
    { title: "Types d'offre", href: admin.typesOffres.index(), icon: Tag },
    { title: 'Villes', href: admin.villes.index(), icon: Globe },
    { title: 'Destinations', href: admin.destinations.index(), icon: MapPin },
    { title: 'Publicités', href: admin.publicites.index(), icon: Megaphone },
    { title: 'Commandes', href: admin.commandes.index(), icon: ShoppingCart },
    { title: 'Colis', href: admin.colis.index(), icon: Archive },
    { title: 'Paiements', href: admin.paiements.index(), icon: CreditCard },
    {
        title: 'Commissions',
        href: admin.commissions.clients(),
        icon: Percent,
        children: [
            { title: 'Clients', href: admin.commissions.clients(), icon: UserCircle },
            { title: 'Agences', href: admin.commissions.agences(), icon: Building2 },
        ],
    },
    { title: 'Reversements', href: admin.reversements.index(), icon: Banknote },
    { title: 'Réclamations', href: admin.reclamations.index(), icon: MessageSquareWarning },
    {
        title: 'Notifications',
        href: admin.notifications.masse.index(),
        icon: MessageSquare,
        children: [
            {
                title: 'Envoi en masse',
                href: admin.notifications.masse.index(),
                icon: Mail,
            },
            {
                title: 'Envoi ciblé',
                href: admin.notifications.cible.index(),
                icon: UserCircle,
            },
        ],
    },
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
                                <AppLogo subtitle="Administration" />
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
