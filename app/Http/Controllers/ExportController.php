<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\AuditLog;
use App\Models\ContributionPeriod;
use App\Models\Expense;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\Payment;
use App\Models\Proposal;
use App\Models\User;
use App\Services\CsvExport;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Downloadable records.
 *
 * CSV throughout: it opens in Excel, in Google Sheets and in a text editor, it
 * needs no library, and it is still readable in ten years — which matters more
 * for a club's financial record than a prettier PDF would.
 */
final readonly class ExportController
{
    public function memberStatement(Member $member, #[CurrentUser] User $user, CsvExport $csv): StreamedResponse
    {
        Gate::authorize('view', $member);

        // A member may always take their own statement. Anyone else's needs the
        // permission to edit members, which is the officer boundary already
        // used elsewhere.
        $own = Member::query()->firstWhere('user_id', $user->id)?->id === $member->id;

        abort_unless($own || $user->can('update', $member), 403);

        // Ordered in the database rather than by sorting the collection: it is
        // both faster and free of the "is the relation loaded" question that a
        // sort closure has to answer for every row.
        $rows = $member->obligations()
            ->join(
                'contribution_periods',
                'contribution_periods.id',
                '=',
                'member_obligations.contribution_period_id',
            )
            ->select('member_obligations.*')
            ->orderBy('contribution_periods.year')
            ->orderBy('contribution_periods.month')
            ->with('contributionPeriod')
            ->get()
            ->map(fn (MemberObligation $obligation): array => [
                $obligation->contributionPeriod?->label() ?? '',
                $obligation->amount,
                $obligation->amount_paid,
                $obligation->outstanding(),
                $obligation->status->label(),
            ])
            ->all();

        return $csv->stream(
            sprintf('statement-%s.csv', $member->member_number),
            ['Period', 'Expected (UGX)', 'Paid (UGX)', 'Outstanding (UGX)', 'Status'],
            $rows,
        );
    }

    public function arrears(CsvExport $csv): StreamedResponse
    {
        Gate::authorize('viewAny', Member::class);

        $rows = MemberObligation::overdueQuery()
            ->with(['member', 'contributionPeriod'])
            ->get()
            ->map(fn (MemberObligation $obligation): array => [
                $obligation->member?->member_number,
                $obligation->member?->full_name,
                $obligation->contributionPeriod?->label() ?? '',
                $obligation->contributionPeriod?->grace_ends_on->toDateString() ?? '',
                // Ageing is the number that turns a list into a priority order.
                (int) abs($obligation->contributionPeriod?->grace_ends_on->diffInDays(today()) ?? 0),
                $obligation->outstanding(),
            ])
            ->all();

        return $csv->stream(
            sprintf('arrears-%s.csv', today()->toDateString()),
            ['Member number', 'Member', 'Period', 'Grace ended', 'Days overdue', 'Outstanding (UGX)'],
            $rows,
        );
    }

    public function contributions(ContributionPeriod $contributionPeriod, CsvExport $csv): StreamedResponse
    {
        Gate::authorize('viewAny', Member::class);

        $rows = MemberObligation::query()
            ->where('contribution_period_id', $contributionPeriod->id)
            ->with('member')
            ->get()
            ->map(fn (MemberObligation $obligation): array => [
                $obligation->member?->member_number,
                $obligation->member?->full_name,
                $obligation->amount,
                $obligation->amount_paid,
                $obligation->outstanding(),
                $obligation->status->label(),
            ])
            ->all();

        return $csv->stream(
            sprintf('contributions-%04d-%02d.csv', $contributionPeriod->year, $contributionPeriod->month),
            ['Member number', 'Member', 'Expected (UGX)', 'Paid (UGX)', 'Outstanding (UGX)', 'Status'],
            $rows,
        );
    }

    public function payments(CsvExport $csv): StreamedResponse
    {
        Gate::authorize('viewAny', Payment::class);

        $rows = Payment::query()
            ->with(['member', 'recordedByMember', 'reviewedByMember'])
            ->where('status', PaymentStatus::Verified->value)
            ->oldest('paid_on')
            ->get()
            ->map(fn (Payment $payment): array => [
                $payment->reference,
                $payment->member?->full_name,
                $payment->amount,
                $payment->paid_on->toDateString(),
                $payment->method->value,
                $payment->recordedByMember?->full_name,
                $payment->reviewedByMember?->full_name,
            ])
            ->all();

        return $csv->stream(
            sprintf('verified-payments-%s.csv', today()->toDateString()),
            ['Reference', 'Member', 'Amount (UGX)', 'Paid on', 'Method', 'Recorded by', 'Verified by'],
            $rows,
        );
    }

    public function expenses(CsvExport $csv): StreamedResponse
    {
        Gate::authorize('viewAny', Expense::class);

        $rows = Expense::query()
            ->with(['requestedByMember', 'approvedByMember', 'verifiedByMember'])
            ->oldest('incurred_on')
            ->get()
            ->map(fn (Expense $expense): array => [
                $expense->reference,
                $expense->purpose,
                $expense->payee,
                $expense->category->value,
                $expense->amount,
                $expense->incurred_on->toDateString(),
                $expense->status->value,
                $expense->requestedByMember?->full_name,
                $expense->approvedByMember?->full_name,
                $expense->verifiedByMember?->full_name,
            ])
            ->all();

        return $csv->stream(
            sprintf('expenses-%s.csv', today()->toDateString()),
            [
                'Reference', 'Purpose', 'Payee', 'Category', 'Amount (UGX)',
                'Incurred on', 'Status', 'Requested by', 'Approved by', 'Verified by',
            ],
            $rows,
        );
    }

    public function governance(CsvExport $csv): StreamedResponse
    {
        Gate::authorize('viewAny', Proposal::class);

        $rows = Proposal::query()
            ->withCount('votes')->oldest()
            ->get()
            ->map(fn (Proposal $proposal): array => [
                $proposal->title,
                $proposal->status->value,
                $proposal->opened_at?->toDateString(),
                $proposal->closed_at?->toDateString(),
                $proposal->eligible_voter_count,
                $proposal->votes_count,
                $proposal->quorum_required,
                $proposal->outcome_note,
            ])
            ->all();

        return $csv->stream(
            sprintf('governance-%s.csv', today()->toDateString()),
            [
                'Title', 'Status', 'Opened', 'Closed', 'Eligible voters',
                'Votes cast', 'Quorum required', 'Outcome',
            ],
            $rows,
        );
    }

    public function auditLog(CsvExport $csv): StreamedResponse
    {
        Gate::authorize('viewAny', AuditLog::class);

        // Chunked rather than loaded: this is the one export with no natural
        // ceiling on its size.
        $rows = AuditLog::query()
            ->with('actorMember')->oldest()
            ->lazy()
            ->map(fn (AuditLog $log): array => [
                $log->created_at->toDateTimeString(),
                $log->event,
                $log->auditable_type,
                $log->auditable_id,
                $log->actorMember?->full_name,
                $log->ip_address,
            ]);

        return $csv->stream(
            sprintf('audit-log-%s.csv', today()->toDateString()),
            ['When', 'Event', 'Record type', 'Record', 'Actor', 'IP address'],
            $rows,
        );
    }
}
