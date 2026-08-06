import { Form, Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import MemberController from '@/actions/App/Http/Controllers/MemberController';
import Heading from '@/components/heading';
import ListFilters from '@/components/list-filters';
import InputError from '@/components/input-error';
import PaginationLinks from '@/components/pagination-links';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AdminLayout from '@/layouts/admin/layout';
import AppLayout from '@/layouts/app-layout';
import { index as memberIndex, show as showMember } from '@/routes/member';
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

function AddMemberDialog() {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>Add member</Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Add member</DialogTitle>
                    <DialogDescription>
                        Onboard a new prospective member. They join with
                        prospective status until admission is approved.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...MemberController.store.form()}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    resetOnSuccess
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="member_number">
                                    Member number
                                </Label>
                                <Input
                                    id="member_number"
                                    name="member_number"
                                    required
                                    placeholder="MN-0001"
                                />
                                <InputError message={errors.member_number} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="full_name">Full name</Label>
                                <Input
                                    id="full_name"
                                    name="full_name"
                                    required
                                    autoComplete="name"
                                />
                                <InputError message={errors.full_name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone">Phone</Label>
                                <Input
                                    id="phone"
                                    name="phone"
                                    required
                                    autoComplete="tel"
                                />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="emergency_contact">
                                    Emergency contact
                                </Label>
                                <Input
                                    id="emergency_contact"
                                    name="emergency_contact"
                                />
                                <InputError
                                    message={errors.emergency_contact}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="joined_at">Joined date</Label>
                                <Input
                                    id="joined_at"
                                    type="date"
                                    name="joined_at"
                                    required
                                />
                                <InputError message={errors.joined_at} />
                            </div>

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    Create member
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function MemberIndex({
    members,
    filters,
    statusOptions,
}: {
    members: Paginated<Member>;
    filters: { search: string | null; status: string | null };
    statusOptions: Option[];
}) {
    const { auth } = usePage().props;
    const canCreate =
        auth.roles.includes('secretary') ||
        auth.roles.includes('administrator');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Members" />

            <AdminLayout>
                <div className="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="Members"
                        description="The club's member roster"
                    />

                    {canCreate && <AddMemberDialog />}
                </div>

                <ListFilters
                    url={memberIndex().url}
                    search={filters.search}
                    placeholder="Search name, member number or phone…"
                    filters={[
                        {
                            name: 'status',
                            label: 'Status',
                            value: filters.status,
                            options: statusOptions,
                        },
                    ]}
                />

                <div className="overflow-x-auto rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Member #</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead>Phone</TableHead>
                                <TableHead>Joined</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {members.data.map((member) => (
                                <TableRow key={member.id}>
                                    <TableCell>
                                        {member.member_number}
                                    </TableCell>
                                    <TableCell>{member.full_name}</TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {member.phone}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {member.joined_at.slice(0, 10)}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={
                                                STATUS_VARIANT[member.status]
                                            }
                                        >
                                            {member.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            asChild
                                        >
                                            <Link href={showMember(member.id)}>
                                                View
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}

                            {members.data.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="py-6 text-center text-muted-foreground"
                                    >
                                        No members yet.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <PaginationLinks links={members.links} />
            </AdminLayout>
        </AppLayout>
    );
}
