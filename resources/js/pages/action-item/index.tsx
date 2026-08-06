import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import ActionItemController from '@/actions/App/Http/Controllers/ActionItemController';
import Heading from '@/components/heading';
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
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin/layout';
import AppLayout from '@/layouts/app-layout';
import { index as actionItemIndex } from '@/routes/action-item';
import type {
    ActionItemRow,
    BreadcrumbItem,
    MeetingOption,
    MemberOption,
    Option,
    Paginated,
} from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Actions', href: actionItemIndex() },
];

function AssignActionDialog({
    members,
    meetings,
}: {
    members: MemberOption[];
    meetings: MeetingOption[];
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>Assign action</Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Assign action</DialogTitle>
                    <DialogDescription>
                        Follow-up work with an owner and a deadline.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...ActionItemController.store.form()}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    resetOnSuccess
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="title">Title</Label>
                                <Input id="title" name="title" required />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    name="description"
                                    rows={3}
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="owner_member_id">Owner</Label>
                                <select
                                    id="owner_member_id"
                                    name="owner_member_id"
                                    defaultValue=""
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                                >
                                    <option value="">Unassigned</option>
                                    {members.map((member) => (
                                        <option
                                            key={member.id}
                                            value={member.id}
                                        >
                                            {member.full_name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.owner_member_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="meeting_id">
                                    Linked meeting (optional)
                                </Label>
                                <select
                                    id="meeting_id"
                                    name="meeting_id"
                                    defaultValue=""
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                                >
                                    <option value="">None</option>
                                    {meetings.map((meeting) => (
                                        <option
                                            key={meeting.id}
                                            value={meeting.id}
                                        >
                                            {meeting.reference}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.meeting_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="due_on">Due date</Label>
                                <Input id="due_on" name="due_on" type="date" />
                                <InputError message={errors.due_on} />
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
                                    Assign
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function ActionItemIndex({
    actionItems,
    canCreate,
    members,
    meetings,
    statusOptions,
}: {
    actionItems: Paginated<ActionItemRow>;
    canCreate: boolean;
    members: MemberOption[];
    meetings: MeetingOption[];
    statusOptions: Option[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Actions" />

            <AdminLayout>
                <div className="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="Actions"
                        description="Follow-up work agreed in meetings"
                    />

                    {canCreate && (
                        <AssignActionDialog
                            members={members}
                            meetings={meetings}
                        />
                    )}
                </div>

                <div className="overflow-x-auto rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Action</TableHead>
                                <TableHead>Owner</TableHead>
                                <TableHead>Meeting</TableHead>
                                <TableHead>Due</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {actionItems.data.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell className="font-medium">
                                        {item.title}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {item.owner ?? 'Unassigned'}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {item.meeting ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {item.due_on ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">
                                            {item.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {item.can_update && (
                                            <Form
                                                {...ActionItemController.update.form(
                                                    item.id,
                                                )}
                                                options={{
                                                    preserveScroll: true,
                                                }}
                                                className="flex justify-end gap-2"
                                            >
                                                {({ processing }) => (
                                                    <>
                                                        <select
                                                            name="status"
                                                            defaultValue={
                                                                item.status
                                                            }
                                                            className="flex h-8 rounded-md border border-input bg-transparent px-2 text-sm shadow-xs outline-none"
                                                        >
                                                            {statusOptions.map(
                                                                (option) => (
                                                                    <option
                                                                        key={
                                                                            option.value
                                                                        }
                                                                        value={
                                                                            option.value
                                                                        }
                                                                    >
                                                                        {
                                                                            option.label
                                                                        }
                                                                    </option>
                                                                ),
                                                            )}
                                                        </select>
                                                        <Button
                                                            type="submit"
                                                            size="sm"
                                                            variant="outline"
                                                            disabled={
                                                                processing
                                                            }
                                                        >
                                                            Update
                                                        </Button>
                                                    </>
                                                )}
                                            </Form>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}

                            {actionItems.data.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="py-6 text-center text-muted-foreground"
                                    >
                                        No actions assigned yet.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <PaginationLinks links={actionItems.links} />
            </AdminLayout>
        </AppLayout>
    );
}
