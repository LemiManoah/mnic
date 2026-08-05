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

export type ContributionPeriodStatus = 'open' | 'closed';

export type ObligationStatus =
    | 'unpaid'
    | 'partially_paid'
    | 'paid'
    | 'waived'
    | 'cancelled';

export type PaymentStatus = 'submitted' | 'verified' | 'rejected' | 'reversed';

export type PaymentMethod = 'mobile_money' | 'bank_transfer' | 'cash';

export type ContributionPeriod = {
    id: string;
    year: number;
    month: number;
    amount: number;
    due_date: string;
    grace_ends_on: string;
    status: ContributionPeriodStatus;
    obligations_count?: number;
    expected_total?: number | null;
    collected_total?: number | null;
};

export type PeriodObligation = {
    id: string;
    member_name: string;
    member_number: string;
    amount: number;
    amount_paid: number;
    outstanding: number;
    status: ObligationStatus;
};

export type LedgerObligation = {
    id: string;
    period: string;
    amount: number;
    amount_paid: number;
    outstanding: number;
    status: ObligationStatus;
};

export type LedgerPayment = {
    id: string;
    amount: number;
    unapplied_amount: number;
    paid_on: string;
    method: PaymentMethod;
    reference: string;
    status: PaymentStatus;
};

export type PaymentEvidenceFile = {
    id: string;
    original_name: string;
};

export type PaymentRow = {
    id: string;
    member_name: string;
    amount: number;
    unapplied_amount: number;
    paid_on: string;
    method: PaymentMethod;
    reference: string;
    status: PaymentStatus;
    recorded_by: string | null;
    reviewed_by: string | null;
    rejection_reason: string | null;
    can_review: boolean;
    evidence: PaymentEvidenceFile[];
};

export type MemberOption = {
    id: string;
    full_name: string;
    member_number: string;
};

export type MembershipStatusHistory = {
    id: string;
    member_id: string;
    from_status: MemberStatus | null;
    to_status: MemberStatus;
    reason: string;
    effective_date: string;
    resolution_reference: string | null;
    created_at: string;
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
