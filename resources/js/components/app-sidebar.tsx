import { Link, usePage } from '@inertiajs/react';
import {
    IconCalendarEvent,
    IconCash,
    IconChecklist,
    IconDashboard,
    IconFileDescription,
    IconGavel,
    IconHistory,
    IconInnerShadowTop,
    IconKey,
    IconReceipt,
    IconReportMoney,
    IconRosette,
    IconScale,
    IconSettings,
    IconUserShield,
    IconUsers,
} from '@tabler/icons-react';
import type * as React from 'react';
import { NavDocuments } from '@/components/nav-documents';
import { NavMain } from '@/components/nav-main';
import { NavSecondary } from '@/components/nav-secondary';
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
import { dashboard } from '@/routes';
import { index as actionItemIndex } from '@/routes/action-item';
import { index as auditLogIndex } from '@/routes/audit-log';
import { index as contributionPeriodIndex } from '@/routes/contribution-period';
import { index as expenseIndex } from '@/routes/expense';
import { index as externalAccountIndex } from '@/routes/external-account';
import { index as meetingIndex } from '@/routes/meeting';
import { index as memberIndex } from '@/routes/member';
import { index as monthlyReportIndex } from '@/routes/monthly-report';
import { index as paymentIndex } from '@/routes/payment';
import { index as positionIndex } from '@/routes/position';
import { index as proposalIndex } from '@/routes/proposal';
import { index as reconciliationIndex } from '@/routes/reconciliation';
import { index as settingIndex } from '@/routes/setting';
import { index as systemRoleIndex } from '@/routes/system-role';
import { index as userManagementIndex } from '@/routes/user-management';

export function AppSidebar({ ...props }: React.ComponentProps<typeof Sidebar>) {
    const { auth } = usePage().props;

    const isAdministrator = auth.roles.includes('administrator');
    const isSecretary = auth.roles.includes('secretary');
    const isTreasurer = auth.roles.includes('treasurer');
    const isFinancialVerifier = auth.roles.includes('financial-verifier');

    // Day-to-day club work, in the order a member is likely to need it.
    const navMain = [
        { title: 'Dashboard', url: dashboard().url, icon: IconDashboard },
        { title: 'Members', url: memberIndex().url, icon: IconUsers },
        {
            title: 'Contribution periods',
            url: contributionPeriodIndex().url,
            icon: IconCalendarEvent,
        },
        { title: 'Payments', url: paymentIndex().url, icon: IconCash },
        { title: 'Expenses', url: expenseIndex().url, icon: IconReceipt },
        {
            title: 'Reconciliation',
            url: reconciliationIndex().url,
            icon: IconScale,
        },
    ];

    // Governance and the records it produces.
    const documents = [
        { name: 'Meetings', url: meetingIndex().url, icon: IconCalendarEvent },
        { name: 'Proposals', url: proposalIndex().url, icon: IconGavel },
        { name: 'Positions', url: positionIndex().url, icon: IconRosette },
        { name: 'Actions', url: actionItemIndex().url, icon: IconChecklist },
        {
            name: 'Monthly reports',
            url: monthlyReportIndex().url,
            icon: IconReportMoney,
        },
    ];

    // Administration, pinned to the bottom and gated by role.
    const navSecondary = [
        ...(isAdministrator
            ? [
                  {
                      title: 'Club settings',
                      url: settingIndex().url,
                      icon: IconSettings,
                  },
                  {
                      title: 'System roles',
                      url: systemRoleIndex().url,
                      icon: IconUserShield,
                  },
                  {
                      title: 'Login accounts',
                      url: userManagementIndex().url,
                      icon: IconKey,
                  },
              ]
            : []),
        ...(isAdministrator || isTreasurer || isFinancialVerifier
            ? [
                  {
                      title: 'External accounts',
                      url: externalAccountIndex().url,
                      icon: IconFileDescription,
                  },
              ]
            : []),
        ...(isAdministrator || isSecretary
            ? [
                  {
                      title: 'Audit log',
                      url: auditLogIndex().url,
                      icon: IconHistory,
                  },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="offcanvas" {...props}>
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            asChild
                            className="data-[slot=sidebar-menu-button]:p-1.5!"
                        >
                            <Link href={dashboard()} prefetch>
                                <IconInnerShadowTop className="size-5!" />
                                <span className="text-base font-semibold">
                                    Musuwa Nation
                                </span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={navMain} />
                <NavDocuments items={documents} />
                {navSecondary.length > 0 && (
                    <NavSecondary items={navSecondary} className="mt-auto" />
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavUser
                    user={{
                        name: auth.user.name,
                        email: auth.user.email,
                        avatar: auth.user.avatar ?? '',
                    }}
                />
            </SidebarFooter>
        </Sidebar>
    );
}
