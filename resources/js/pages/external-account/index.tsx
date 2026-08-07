import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import ExternalAccountController from '@/actions/App/Http/Controllers/ExternalAccountController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import ListFilters from '@/components/list-filters';
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
import { index as externalAccountIndex } from '@/routes/external-account';
import type { BreadcrumbItem, ExternalAccount, Option } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'External accounts', href: externalAccountIndex() },
];

function AddAccountDialog({ typeOptions }: { typeOptions: Option[] }) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>Add account</Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Add external account</DialogTitle>
                    <DialogDescription>
                        Record only a masked identifier — the club never stores
                        a full account number.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...ExternalAccountController.store.form()}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    resetOnSuccess
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input id="name" name="name" required />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="type">Type</Label>
                                <select
                                    id="type"
                                    name="type"
                                    required
                                    defaultValue=""
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                                >
                                    <option value="" disabled>
                                        Select a type
                                    </option>
                                    {typeOptions.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.type} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="institution">Institution</Label>
                                <Input id="institution" name="institution" />
                                <InputError message={errors.institution} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="masked_identifier">
                                    Masked identifier
                                </Label>
                                <Input
                                    id="masked_identifier"
                                    name="masked_identifier"
                                    required
                                    placeholder="****4321"
                                />
                                <InputError
                                    message={errors.masked_identifier}
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
                                    Add account
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function ExternalAccountIndex({
    accounts,
    canCreate,
    typeOptions,
    filters,
}: {
    accounts: ExternalAccount[];
    canCreate: boolean;
    typeOptions: Option[];
    filters: { search: string | null; type: string | null };
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="External accounts" />

            <AdminLayout>
                <div className="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="External accounts"
                        description="Approved channels the club receives and pays money through"
                    />

                    {canCreate && (
                        <AddAccountDialog typeOptions={typeOptions} />
                    )}
                </div>

                <ListFilters
                    url={externalAccountIndex().url}
                    search={filters.search}
                    placeholder="Search account name or institution…"
                    filters={[
                        {
                            name: 'type',
                            label: 'Type',
                            value: filters.type,
                            options: typeOptions,
                        },
                    ]}
                />

                <div className="overflow-x-auto rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Institution</TableHead>
                                <TableHead>Identifier</TableHead>
                                <TableHead>Active</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {accounts.map((account) => (
                                <TableRow key={account.id}>
                                    <TableCell className="font-medium">
                                        {account.name}
                                    </TableCell>
                                    <TableCell>{account.type}</TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {account.institution ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {account.masked_identifier}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={
                                                account.is_active
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {account.is_active
                                                ? 'active'
                                                : 'inactive'}
                                        </Badge>
                                    </TableCell>
                                </TableRow>
                            ))}

                            {accounts.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="py-6 text-center text-muted-foreground"
                                    >
                                        No external accounts recorded.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
            </AdminLayout>
        </AppLayout>
    );
}
