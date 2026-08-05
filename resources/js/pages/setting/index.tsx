import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import SettingController from '@/actions/App/Http/Controllers/SettingController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/admin/layout';
import AppLayout from '@/layouts/app-layout';
import { index as settingIndex } from '@/routes/setting';
import type { BreadcrumbItem, SettingWithVersions } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Club Settings',
        href: settingIndex(),
    },
];

function SettingCard({ setting }: { setting: SettingWithVersions }) {
    const [showHistory, setShowHistory] = useState(false);

    return (
        <Card>
            <CardHeader>
                <CardTitle>{setting.label}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-6">
                <p className="text-sm text-muted-foreground">
                    Current value:{' '}
                    <span className="font-medium text-foreground">
                        {setting.current?.value ?? 'Not set'}
                    </span>
                    {setting.current && (
                        <>
                            {' '}
                            (effective{' '}
                            {setting.current.effective_from.slice(0, 10)})
                        </>
                    )}
                </p>

                <Form
                    {...SettingController.update.form(setting.id)}
                    className="flex flex-wrap items-end gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor={`value-${setting.id}`}>
                                    New value
                                </Label>
                                <Input
                                    id={`value-${setting.id}`}
                                    name="value"
                                    required
                                />
                                <InputError message={errors.value} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor={`effective_from-${setting.id}`}>
                                    Effective from
                                </Label>
                                <Input
                                    id={`effective_from-${setting.id}`}
                                    type="date"
                                    name="effective_from"
                                    required
                                />
                                <InputError message={errors.effective_from} />
                            </div>

                            <Button type="submit" disabled={processing}>
                                Save new version
                            </Button>
                        </>
                    )}
                </Form>

                <div>
                    <Button
                        variant="link"
                        size="sm"
                        className="px-0"
                        onClick={() => setShowHistory((value) => !value)}
                    >
                        {showHistory ? 'Hide history' : 'Show history'}
                    </Button>

                    {showHistory && (
                        <ul className="mt-2 space-y-1 text-sm text-muted-foreground">
                            {setting.versions.map((version) => (
                                <li key={version.id}>
                                    {version.value} — effective{' '}
                                    {version.effective_from.slice(0, 10)}
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

export default function SettingIndex({
    settings,
}: {
    settings: SettingWithVersions[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Club Settings" />

            <AdminLayout>
                <div className="max-w-2xl space-y-6">
                    <Heading
                        variant="small"
                        title="Club settings"
                        description="Effective-dated configuration. Changing a value never rewrites history."
                    />

                    <div className="space-y-6">
                        {settings.map((setting) => (
                            <SettingCard key={setting.id} setting={setting} />
                        ))}
                    </div>
                </div>
            </AdminLayout>
        </AppLayout>
    );
}
