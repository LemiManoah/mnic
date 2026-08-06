import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import SystemRoleController from '@/actions/App/Http/Controllers/SystemRoleController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { index as systemRoleIndex } from '@/routes/system-role';
import type { BreadcrumbItem, Option } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'System roles', href: systemRoleIndex() },
];

type SystemRole = {
    id: number;
    name: string;
    users_count: number;
    permissions: string[];
    is_protected: boolean;
    can_delete: boolean;
};

type PermissionGroups = Record<string, Option[]>;

function PermissionPicker({
    groups,
    selected,
}: {
    groups: PermissionGroups;
    selected: string[];
}) {
    return (
        <div className="max-h-72 space-y-4 overflow-y-auto rounded-md border p-3">
            {Object.entries(groups).map(([group, permissions]) => (
                <div key={group} className="space-y-2">
                    <p className="text-sm font-medium">{group}</p>
                    {permissions.map((permission) => (
                        <label
                            key={permission.value}
                            className="flex items-start gap-2 text-sm"
                        >
                            <Checkbox
                                name="permissions[]"
                                value={permission.value}
                                defaultChecked={selected.includes(
                                    permission.value,
                                )}
                            />
                            <span className="text-muted-foreground">
                                {permission.label}
                            </span>
                        </label>
                    ))}
                </div>
            ))}
        </div>
    );
}

function CreateRoleDialog({ groups }: { groups: PermissionGroups }) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>New role</Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>New system role</DialogTitle>
                    <DialogDescription>
                        A role is a bundle of permissions. Maker-checker rules
                        are not permissions and cannot be granted here.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...SystemRoleController.store.form()}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    resetOnSuccess
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    placeholder="deputy-treasurer"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label>Permissions</Label>
                                <PermissionPicker
                                    groups={groups}
                                    selected={[]}
                                />
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
                                    Create role
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function EditRoleDialog({
    role,
    groups,
}: {
    role: SystemRole;
    groups: PermissionGroups;
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm" variant="ghost">
                    Edit
                </Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Edit {role.name}</DialogTitle>
                    <DialogDescription>
                        {role.is_protected
                            ? 'The administrator role cannot be renamed and always holds every permission.'
                            : 'Changes take effect immediately for everyone holding this role.'}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...SystemRoleController.update.form(role.id)}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor={`name-${role.id}`}>Name</Label>
                                <Input
                                    id={`name-${role.id}`}
                                    name="name"
                                    required
                                    defaultValue={role.name}
                                    readOnly={role.is_protected}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label>Permissions</Label>
                                <PermissionPicker
                                    groups={groups}
                                    selected={role.permissions}
                                />
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
                                    Save role
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function SystemRoleIndex({
    roles,
    permissionGroups,
}: {
    roles: SystemRole[];
    permissionGroups: PermissionGroups;
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="System roles" />

            <AdminLayout>
                <div className="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="System roles"
                        description="What an account may do in the software — distinct from the club offices members are elected to"
                    />

                    <CreateRoleDialog groups={permissionGroups} />
                </div>

                <div className="overflow-x-auto rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Role</TableHead>
                                <TableHead>Accounts</TableHead>
                                <TableHead>Permissions</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {roles.map((role) => (
                                <TableRow key={role.id}>
                                    <TableCell className="font-medium">
                                        {role.name}
                                        {role.is_protected && (
                                            <Badge
                                                variant="secondary"
                                                className="ml-2"
                                            >
                                                protected
                                            </Badge>
                                        )}
                                    </TableCell>
                                    <TableCell>{role.users_count}</TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {role.permissions.length === 0
                                            ? 'None'
                                            : `${role.permissions.length} granted`}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            <EditRoleDialog
                                                role={role}
                                                groups={permissionGroups}
                                            />

                                            {role.can_delete &&
                                                role.users_count === 0 && (
                                                    <Form
                                                        {...SystemRoleController.destroy.form(
                                                            role.id,
                                                        )}
                                                        options={{
                                                            preserveScroll: true,
                                                        }}
                                                    >
                                                        {({ processing }) => (
                                                            <Button
                                                                type="submit"
                                                                size="sm"
                                                                variant="outline"
                                                                disabled={
                                                                    processing
                                                                }
                                                            >
                                                                Delete
                                                            </Button>
                                                        )}
                                                    </Form>
                                                )}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                <p className="text-sm text-muted-foreground">
                    Looking for the club&apos;s elected offices? Those live on
                    the <Link href="/members" className="underline">Members</Link>{' '}
                    screen.
                </p>
            </AdminLayout>
        </AppLayout>
    );
}
