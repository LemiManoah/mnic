import { Form, Head } from '@inertiajs/react';
import { Download } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin/layout';
import AppLayout from '@/layouts/app-layout';
import { index as clubProfileIndex, download as downloadClubProfile, update as updateClubProfile } from '@/routes/club-profile';
import type { BreadcrumbItem } from '@/types';

type ProfileContent = Record<string, Record<string, string>>;

const groups = [
    {
        key: 'cover',
        title: 'Cover',
        fields: [
            { key: 'club_name', label: 'Club name', rows: 1 },
            { key: 'tagline', label: 'Tagline', rows: 2 },
            { key: 'established', label: 'Established', rows: 1 },
            { key: 'motto', label: 'Motto', rows: 1 },
        ],
    },
    {
        key: 'identity',
        title: 'Our identity',
        fields: [
            { key: 'heading', label: 'Section heading', rows: 2 },
            { key: 'body', label: 'Who we are', rows: 7 },
            { key: 'principles', label: 'Identity principles', rows: 4, hint: 'One item per line, formatted as Title|Description.' },
        ],
    },
    {
        key: 'purpose',
        title: 'Vision and mission',
        fields: [
            { key: 'heading', label: 'Section heading', rows: 2 },
            { key: 'vision', label: 'Vision', rows: 4 },
            { key: 'mission', label: 'Mission', rows: 4 },
            { key: 'promise', label: 'Our promise', rows: 5, hint: 'One statement per line.' },
        ],
    },
    {
        key: 'pillars',
        title: 'Strategic pillars',
        fields: [
            { key: 'heading', label: 'Section heading', rows: 2 },
            { key: 'items', label: 'Pillars', rows: 7, hint: 'One item per line, formatted as Title|Description.' },
        ],
    },
    {
        key: 'operating_model',
        title: 'How we work',
        fields: [
            { key: 'heading', label: 'Section heading', rows: 2 },
            { key: 'intro', label: 'Introduction', rows: 3 },
            { key: 'steps', label: 'Operating steps', rows: 8, hint: 'One step per line, formatted as Title|Description.' },
        ],
    },
    {
        key: 'investment',
        title: 'Investment philosophy',
        fields: [
            { key: 'heading', label: 'Section heading', rows: 2 },
            { key: 'intro', label: 'Introduction', rows: 4 },
            { key: 'look_for', label: 'What we look for', rows: 6, hint: 'One item per line.' },
            { key: 'avoid', label: 'What we avoid', rows: 6, hint: 'One item per line.' },
            { key: 'priority', label: 'Current investment priority', rows: 4 },
        ],
    },
    {
        key: 'governance',
        title: 'Governance',
        fields: [
            { key: 'heading', label: 'Section heading', rows: 2 },
            { key: 'intro', label: 'Introduction', rows: 3 },
            { key: 'principles', label: 'Governance principles', rows: 7, hint: 'One item per line, formatted as Title|Description.' },
            { key: 'accountability', label: 'Accountability in practice', rows: 6, hint: 'One item per line.' },
        ],
    },
    {
        key: 'membership',
        title: 'Membership and future',
        fields: [
            { key: 'heading', label: 'Section heading', rows: 2 },
            { key: 'requirements', label: 'Membership requirements', rows: 6, hint: 'One item per line.' },
            { key: 'ambitions', label: 'Long-term ambition', rows: 6, hint: 'One item per line.' },
            { key: 'enquiries', label: 'Enquiries', rows: 2 },
            { key: 'closing', label: 'Closing motto', rows: 1 },
        ],
    },
] as const;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Club profile', href: clubProfileIndex() },
];

function ProfileSections({
    profile,
    canUpdate,
    errors,
}: {
    profile: ProfileContent;
    canUpdate: boolean;
    errors: Record<string, string>;
}) {
    return (
        <div className="space-y-5">
            {groups.map((group) => (
                <section key={group.key} className="rounded-lg border p-4 sm:p-6">
                    <h2 className="mb-4 text-lg font-semibold">{group.title}</h2>
                    <div className="grid gap-5 md:grid-cols-2">
                        {group.fields.map((field) => {
                            const value = profile[group.key]?.[field.key] ?? '';
                            const name = `profile[${group.key}][${field.key}]`;
                            const errorKey = `profile.${group.key}.${field.key}`;
                            const id = `${group.key}-${field.key}`;

                            return (
                                <div key={field.key} className="min-w-0 space-y-2">
                                    <label htmlFor={id} className="text-sm font-medium">{field.label}</label>
                                    <Textarea
                                        id={id}
                                        name={name}
                                        defaultValue={value}
                                        rows={field.rows}
                                        readOnly={!canUpdate}
                                        className="resize-y read-only:border-transparent read-only:bg-muted/40 read-only:focus-visible:ring-0"
                                        required
                                    />
                                    {'hint' in field && <p className="text-xs text-muted-foreground">{field.hint}</p>}
                                    <InputError message={errors[errorKey]} />
                                </div>
                            );
                        })}
                    </div>
                </section>
            ))}
        </div>
    );
}

export default function ClubProfileIndex({
    profile,
    canUpdate,
}: {
    profile: ProfileContent;
    canUpdate: boolean;
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Club profile" />
            <AdminLayout>
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">{profile.cover?.club_name}</h1>
                        <p className="text-sm text-muted-foreground">Club profile and downloadable overview</p>
                    </div>
                    <div className="flex gap-2">
                        <Button asChild variant="outline">
                            <a href={downloadClubProfile().url} download="mnic-club-profile.pdf">
                                <Download />
                                Download PDF
                            </a>
                        </Button>
                    </div>
                </div>

                <Form
                    key={JSON.stringify(profile)}
                    {...updateClubProfile.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-5"
                >
                    {({ processing, errors, recentlySuccessful }) => (
                        <>
                            <ProfileSections profile={profile} canUpdate={canUpdate} errors={errors} />
                            {canUpdate && (
                                <div className="flex items-center gap-3">
                                    <Button type="submit" disabled={processing}>
                                        {processing ? 'Saving…' : 'Save club profile'}
                                    </Button>
                                    {recentlySuccessful && <p role="status" className="text-sm text-muted-foreground">Profile saved.</p>}
                                </div>
                            )}
                        </>
                    )}
                </Form>
            </AdminLayout>
        </AppLayout>
    );
}
