<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The fixed catalogue of things a system role can grant.
 *
 * Permissions stay in code because policies must reference something stable;
 * roles are data and may be created, renamed or deleted by an administrator.
 *
 * Only privileged abilities appear here. Anything every authenticated member
 * may do — reading the roster, viewing periods, submitting their own payment —
 * is granted in the policy without a permission check.
 *
 * Note what is deliberately absent: none of the maker-checker rules. "You may
 * not verify a payment you recorded" is a row-level rule enforced inside the
 * Action, and no permission grant may switch it off.
 */
enum Permission: string
{
    // Members...
    case MembersCreate = 'members.create';
    case MembersUpdate = 'members.update';
    case MembersChangeStatus = 'members.change_status';
    case MembersAssignRole = 'members.assign_role';

    // Club configuration and audit...
    case SettingsUpdate = 'settings.update';
    case AuditView = 'audit.view';

    // Contributions...
    case ContributionPeriodsCreate = 'contribution_periods.create';
    case PaymentsReview = 'payments.review';
    case PaymentsViewEvidence = 'payments.view_evidence';

    // Governance...
    case MeetingsCreate = 'meetings.create';
    case MeetingsManageMinutes = 'meetings.manage_minutes';
    case ProposalsCreate = 'proposals.create';
    case ProposalsManageVoting = 'proposals.manage_voting';
    case ActionItemsCreate = 'action_items.create';
    case ActionItemsUpdateAny = 'action_items.update_any';

    // Expenditure...
    case ExpensesCreate = 'expenses.create';
    case ExpensesApprove = 'expenses.approve';
    case ExpensesPay = 'expenses.pay';
    case ExpensesVerify = 'expenses.verify';

    // Reconciliation...
    case ReconciliationsCreate = 'reconciliations.create';
    case ReconciliationsUpdate = 'reconciliations.update';
    case ReconciliationsConfirm = 'reconciliations.confirm';
    case ReconciliationsLock = 'reconciliations.lock';

    // External accounts...
    case ExternalAccountsView = 'external_accounts.view';
    case ExternalAccountsCreate = 'external_accounts.create';

    public function label(): string
    {
        return match ($this) {
            self::MembersCreate => 'Add members',
            self::MembersUpdate => 'Edit member profiles',
            self::MembersChangeStatus => 'Change member status',
            self::MembersAssignRole => 'Assign system roles',
            self::SettingsUpdate => 'Change club settings',
            self::AuditView => 'Read the audit log',
            self::ContributionPeriodsCreate => 'Open contribution periods',
            self::PaymentsReview => 'Verify or reject payments',
            self::PaymentsViewEvidence => 'View any payment evidence',
            self::MeetingsCreate => 'Schedule meetings',
            self::MeetingsManageMinutes => 'Record attendance and minutes',
            self::ProposalsCreate => 'Raise proposals',
            self::ProposalsManageVoting => 'Open and close voting',
            self::ActionItemsCreate => 'Assign actions',
            self::ActionItemsUpdateAny => "Update anyone's action",
            self::ExpensesCreate => 'Request expenses',
            self::ExpensesApprove => 'Approve or reject expenses',
            self::ExpensesPay => 'Record expense payments',
            self::ExpensesVerify => 'Verify expenses',
            self::ReconciliationsCreate => 'Start reconciliations',
            self::ReconciliationsUpdate => 'Prepare and submit reconciliations',
            self::ReconciliationsConfirm => 'Confirm reconciliations',
            self::ReconciliationsLock => 'Lock the month',
            self::ExternalAccountsView => 'View external accounts',
            self::ExternalAccountsCreate => 'Add external accounts',
        };
    }

    /**
     * Broad area, used to group permissions on the role management screens.
     */
    public function group(): string
    {
        return str($this->value)->before('.')->replace('_', ' ')->title()->value();
    }
}
