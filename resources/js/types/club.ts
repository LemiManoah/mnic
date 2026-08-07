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

export type ClubPosition =
    | 'chairperson'
    | 'vice-chairperson'
    | 'general-secretary'
    | 'assistant-general-secretary'
    | 'treasurer'
    | 'assistant-treasurer'
    | 'mobilizer'
    | 'assistant-mobilizer'
    | 'chief-whip'
    | 'assistant-chief-whip';

export type Member = {
    id: string;
    user_id: string | null;
    referred_by_member_id: string | null;
    member_number: string;
    full_name: string;
    position: ClubPosition | null;
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

export type PaymentStatus =
    | 'submitted'
    | 'verified'
    | 'rejected'
    | 'reversal_pending'
    | 'reversed';

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
    adjustment_reason: string | null;
    can_adjust: boolean;
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
    can_request_reversal: boolean;
    can_decide_reversal: boolean;
    reversal_reason: string | null;
    reversal_requested_by: string | null;
    evidence: PaymentEvidenceFile[];
};

export type MemberOption = {
    id: string;
    full_name: string;
    member_number: string;
};

export type MeetingStatus =
    | 'scheduled'
    | 'completed'
    | 'confirmed'
    | 'cancelled';

export type AttendanceStatus = 'present' | 'apologies' | 'absent';

export type ProposalStatus =
    | 'draft'
    | 'open'
    | 'passed'
    | 'rejected'
    | 'withdrawn';

export type VoteChoice = 'for' | 'against' | 'abstain';

export type ActionItemStatus =
    | 'open'
    | 'in_progress'
    | 'blocked'
    | 'completed'
    | 'cancelled';

export type Meeting = {
    id: string;
    reference: string;
    title: string;
    scheduled_for: string;
    location: string | null;
    agenda: string | null;
    status: MeetingStatus;
    cancellation_reason: string | null;
    present_count?: number;
};

export type MeetingOption = {
    id: string;
    reference: string;
    title: string;
};

export type AttendanceRow = {
    member_id: string;
    member_name: string;
    status: AttendanceStatus;
};

export type MinuteSummary = {
    id: string;
    version: number;
    body: string;
    confirmed_at: string | null;
    /** Set when this version supersedes an earlier confirmed one. */
    correction_reason: string | null;
};

export type MinuteVersion = {
    id: string;
    version: number;
    confirmed_at: string | null;
    correction_reason: string | null;
};

export type Proposal = {
    id: string;
    meeting_id: string | null;
    title: string;
    description: string;
    status: ProposalStatus;
    opened_at: string | null;
    closes_at: string | null;
    closed_at: string | null;
    eligible_voter_count: number;
    quorum_required: number;
    approval_percent: number;
    outcome_note: string | null;
    withdrawal_reason: string | null;
    votes_count?: number;
};

export type VoteRow = {
    id: string;
    member_name: string;
    choice: VoteChoice;
    has_conflict: boolean;
    conflict_note: string | null;
};

export type VoteTally = {
    for: number;
    against: number;
    abstain: number;
};

export type ActionItemRow = {
    id: string;
    title: string;
    description: string | null;
    owner: string | null;
    meeting: string | null;
    due_on: string | null;
    status: ActionItemStatus;
    can_update: boolean;
};

export type MeetingProposalSummary = {
    id: string;
    title: string;
    status: ProposalStatus;
};

export type MeetingActionSummary = {
    id: string;
    title: string;
    owner: string | null;
    due_on: string | null;
    status: ActionItemStatus;
};

export type ExpenseStatus =
    | 'submitted'
    | 'approved'
    | 'rejected'
    | 'paid'
    | 'verified';

export type ExternalAccountType = 'bank' | 'mobile_money' | 'cash';

export type ReconciliationStatus =
    | 'draft'
    | 'submitted'
    | 'confirmed'
    | 'rejected'
    | 'locked';

export type ExternalAccount = {
    id: string;
    name: string;
    type: ExternalAccountType;
    institution: string | null;
    masked_identifier: string;
    is_active: boolean;
};

export type ExternalAccountOption = {
    id: string;
    name: string;
    masked_identifier: string;
};

export type PeriodOption = {
    id: string;
    label: string;
};

export type ExpenseRow = {
    id: string;
    reference: string;
    purpose: string;
    category: string;
    payee: string;
    amount: number;
    incurred_on: string;
    status: ExpenseStatus;
    requested_by: string | null;
    approved_by: string | null;
    verified_by: string | null;
    rejection_reason: string | null;
    can_approve: boolean;
    can_pay: boolean;
    can_verify: boolean;
};

export type ReconciliationRow = {
    id: string;
    period: string;
    account: string | null;
    opening_balance: number;
    statement_closing_balance: number;
    expected_closing_balance: number;
    difference: number;
    status: ReconciliationStatus;
    prepared_by: string | null;
    items: ReconciliationItemRow[];
    can_submit: boolean;
    can_confirm: boolean;
    can_lock: boolean;
};

export type ReconciliationItemRow = {
    id: string;
    description: string;
    amount: number;
    is_resolved: boolean;
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

export type PositionPollStatus = 'draft' | 'open' | 'decided' | 'failed';

export type PositionPoll = {
    id: string;
    position: ClubPosition;
    title: string;
    description: string | null;
    status: PositionPollStatus;
    opened_at: string | null;
    closes_at: string | null;
    closed_at: string | null;
    eligible_voter_count: number;
    quorum_required: number;
    outcome_note: string | null;
    candidates_count?: number;
    votes_count?: number;
};

/** The shape PositionPollController@show sends, already formatted for display. */
export type PositionPollDetail = {
    id: string;
    position: string;
    title: string;
    description: string | null;
    status: PositionPollStatus;
    opened_at: string | null;
    closes_at: string | null;
    closed_at: string | null;
    eligible_voter_count: number;
    quorum_required: number;
    outcome_note: string | null;
    winner_name: string | null;
};

export type PositionPollCandidate = {
    id: string;
    member_name: string;
    manifesto: string | null;
    /** Null while voting is open, so the running count stays hidden. */
    votes: number | null;
};

export type NotificationRow = {
    id: string;
    subject: string;
    body: string;
    url: string | null;
    read_at: string | null;
    created_at: string | null;
};

export type AdjustmentStatus = 'pending' | 'approved' | 'rejected';

export type PeriodAdjustmentRow = {
    id: string;
    period: string;
    /** Signed: negative corrects an overstatement. */
    amount: number;
    reason: string;
    status: AdjustmentStatus;
    requested_by: string | null;
    reviewed_by: string | null;
    rejection_reason: string | null;
    can_review: boolean;
};
