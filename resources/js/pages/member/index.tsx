import { Head, Link, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import PaginationLinks from '@/components/pagination-links';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin/layout';
import AppLayout from '@/layouts/app-layout';
import {
    create as createMember,
    edit as editMember,
    index as memberIndex,
} from '@/routes/member';
import type { BreadcrumbItem, Member, Paginated } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Members',
        href: memberIndex(),
    },
];

const STATUS_VARIANT: Record<
    Member['status'],
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    prospective: 'outline',
    active: 'default',
    suspended: 'secondary',
    exited: 'secondary',
    removed: 'destructive',
};

export default function MemberIndex({
    members,
}: {
    members: Paginated<Member>;
}) {
    const { auth } = usePage().props;
    const canCreate =
        auth.roles.includes('secretary') ||
        auth.roles.includes('administrator');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Members" />

            <AdminLayout>
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <Heading
                            variant="small"
                            title="Members"
                            description="The club's member roster"
                        />

                        {canCreate && (
                            <Button asChild>
                                <Link href={createMember()}>Add member</Link>
                            </Button>
                        )}
                    </div>

                    <div className="overflow-x-auto rounded-md border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-2 font-medium">
                                        Member #
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Name
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Phone
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Joined
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Status
                                    </th>
                                    <th className="px-4 py-2" />
                                </tr>
                            </thead>
                            <tbody>
                                {members.data.map((member) => (
                                    <tr key={member.id} className="border-t">
                                        <td className="px-4 py-2">
                                            {member.member_number}
                                        </td>
                                        <td className="px-4 py-2">
                                            {member.full_name}
                                        </td>
                                        <td className="px-4 py-2 text-muted-foreground">
                                            {member.phone}
                                        </td>
                                        <td className="px-4 py-2 text-muted-foreground">
                                            {member.joined_at.slice(0, 10)}
                                        </td>
                                        <td className="px-4 py-2">
                                            <Badge
                                                variant={
                                                    STATUS_VARIANT[
                                                        member.status
                                                    ]
                                                }
                                            >
                                                {member.status}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-2 text-right">
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                asChild
                                            >
                                                <Link
                                                    href={editMember(member.id)}
                                                >
                                                    View
                                                </Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))}

                                {members.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="px-4 py-6 text-center text-muted-foreground"
                                        >
                                            No members yet.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    <PaginationLinks links={members.links} />
                </div>
            </AdminLayout>
        </AppLayout>
    );
}
