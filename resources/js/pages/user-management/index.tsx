import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import UserManagementController from '@/actions/App/Http/Controllers/UserManagementController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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
import { index as userManagementIndex } from '@/routes/user-management';
import type { BreadcrumbItem, MemberStatus } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Login accounts', href: userManagementIndex() },
];

type MemberAccount = {
    id: string;
    member_number: string;
    full_name: string;
    status: MemberStatus;
    email: string | null;
    role: string | null;
    has_login: boolean;
};

function CreateLoginDialog({
    members,
    roles,
}: {
    members: MemberAccount[];
    roles: string[];
}) {
    const [open, setOpen] = useState(false);
    const withoutLogin = members.filter((member) => !member.has_login);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button disabled={withoutLogin.length === 0}>
                    Create login
                </Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Create login</DialogTitle>
                    <DialogDescription>
                        A temporary password is generated and shown once, for
                        you to pass on. It is never recoverable afterwards.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...UserManagementController.store.form()}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    resetOnSuccess
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="member_id">Member</Label>
                                <select
                                    id="member_id"
                                    name="member_id"
                                    required
                                    defaultValue=""
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                                >
                                    <option value="" disabled>
                                        Select a member
                                    </option>
                                    {withoutLogin.map((member) => (
                                        <option
                                            key={member.id}
                                            value={member.id}
                                        >
                                            {member.member_number} —{' '}
                                            {member.full_name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.member_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    required
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="role">System role</Label>
                                <select
                                    id="role"
                                    name="role"
                                    required
                                    defaultValue="member"
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                                >
                                    {roles.map((role) => (
                                        <option key={role} value={role}>
                                            {role}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.role} />
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
                                    Create login
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function UserManagementIndex({
    members,
    roles,
}: {
    members: MemberAccount[];
    roles: string[];
}) {
    const withoutLogin = members.filter((member) => !member.has_login).length;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Login accounts" />

            <AdminLayout>
                <div className="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="Login accounts"
                        description={
                            withoutLogin === 0
                                ? 'Every member can sign in'
                                : `${withoutLogin} member(s) cannot sign in yet`
                        }
                    />

                    <CreateLoginDialog members={members} roles={roles} />
                </div>

                <div className="overflow-x-auto rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Member #</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>System role</TableHead>
                                <TableHead>Login</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {members.map((member) => (
                                <TableRow key={member.id}>
                                    <TableCell>
                                        {member.member_number}
                                    </TableCell>
                                    <TableCell>{member.full_name}</TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {member.email ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        {member.role ? (
                                            <Badge variant="secondary">
                                                {member.role}
                                            </Badge>
                                        ) : (
                                            <span className="text-muted-foreground">
                                                —
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={
                                                member.has_login
                                                    ? 'default'
                                                    : 'destructive'
                                            }
                                        >
                                            {member.has_login
                                                ? 'active'
                                                : 'none'}
                                        </Badge>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </AdminLayout>
        </AppLayout>
    );
}
