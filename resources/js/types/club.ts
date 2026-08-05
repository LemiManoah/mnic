export type MemberStatus =
    | 'prospective'
    | 'active'
    | 'suspended'
    | 'exited'
    | 'removed';

export type ClubRole =
    | 'member'
    | 'interim-chairperson'
    | 'secretary'
    | 'treasurer'
    | 'financial-verifier'
    | 'administrator';

export type SettingValueType = 'integer' | 'decimal' | 'text' | 'date';

export type Member = {
    id: string;
    user_id: string | null;
    referred_by_member_id: string | null;
    member_number: string;
    full_name: string;
    phone: string;
    emergency_contact: string | null;
    joined_at: string;
    status: MemberStatus;
    created_at: string;
    updated_at: string;
};

export type Option = {
    value: string;
    label: string;
};

export type SettingVersion = {
    id: string;
    setting_id: string;
    value: string;
    effective_from: string;
    created_by_member_id: string | null;
    created_at: string;
    updated_at: string;
};

export type SettingWithVersions = {
    id: string;
    key: string;
    label: string;
    type: SettingValueType;
    current: SettingVersion | null;
    versions: SettingVersion[];
};

export type AuditLog = {
    id: string;
    actor_member_id: string | null;
    actor_member: Member | null;
    event: string;
    auditable_type: string;
    auditable_id: string;
    before: Record<string, unknown> | null;
    after: Record<string, unknown> | null;
    ip_address: string | null;
    created_at: string;
};
