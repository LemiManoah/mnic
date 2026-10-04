<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\AuditLog;
use App\Models\ContributionPeriod;
use App\Models\Expense;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\OpeningWithdrawalFee;
use App\Models\Payment;
use App\Models\Proposal;
use App\Models\User;
use App\Services\MonthlyReportData;
use App\Services\PdfExport;
use App\Services\TabularReport;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Lang;
use Symfony\Component\HttpFoundation\Response;

/**
 * Downloadable records.
 *
 * Every tabular export serves both formats off one set of rows: append
 * `?format=pdf` for a document, omit it for a spreadsheet. PDF is the better
 * thing to hand somebody; CSV is the better thing to sort and filter, so both
 * stay rather than one replacing the other.
 *
 * The statement, monthly report and receipt are PDF-only, because they are
 * documents rather than lists — see PdfExport and the views under
 * `resources/views/pdf`.
 */
final readonly class ExportController
{
    public function memberStatement(
        Request $request,
        Member $member,
        #[CurrentUser] User $user,
        TabularReport $report,
    ): Response {
        $this->authorizeStatement($member, $user);

        $rows = $this->obligationRows($member)
            ->map(fn (MemberObligation $obligation): array => [
                $obligation->contributionPeriod?->label() ?? '',
                $obligation->amount,
                $obligation->amount_paid,
                $obligation->outstanding(),
                $obligation->status->label(),
            ])
            ->values()
            ->all();

        return $report->render(
            $this->format($request),
            sprintf('statement-%s', $member->member_number),
            sprintf('Statement — %s', $member->full_name),
            sprintf('Member %s', $member->member_number),
            ['Period', 'Expected (UGX)', 'Paid (UGX)', 'Outstanding (UGX)', 'Status'],
            $rows,
            [1, 2, 3],
        );
    }

    /**
     * The statement as a designed document rather than a table — totals, the
     * member's standing and any unapplied advance.
     */
    public function memberStatementPdf(Member $member, #[CurrentUser] User $user, PdfExport $pdf): Response
    {
        $this->authorizeStatement($member, $user);

        $records = $this->obligationRows($member);

        return $pdf->download(
            'pdf.member-statement',
            sprintf('statement-%s.pdf', $member->member_number),
            [
                'member' => $member,
                'payments' => $member->payments()->where('status', PaymentStatus::Verified->value)->oldest('paid_on')->get(),
                'obligations' => $records->map(fn (MemberObligation $obligation): array => [
                    'period' => $obligation->contributionPeriod?->label() ?? '',
                    'amount' => $obligation->amount,
                    'amount_paid' => $obligation->amount_paid,
                    'outstanding' => $obligation->outstanding(),
                    'status' => $obligation->status->label(),
                ]),
                'totals' => [
                    'expected' => $records->sum(fn (MemberObligation $obligation): int => $obligation->amount),
                    'paid' => $records->sum(fn (MemberObligation $obligation): int => $obligation->amount_paid),
                    'outstanding' => $records->sum(fn (MemberObligation $obligation): int => $obligation->outstanding()),
                ],
                // Overpayment held against future months. It belongs to no one
                // obligation, so it would be invisible without this.
                'advance' => (int) $member->payments()
                    ->where('status', PaymentStatus::Verified->value)
                    ->sum('unapplied_amount'),
            ],
        );
    }

    public function monthlyReportPdf(
        ContributionPeriod $contributionPeriod,
        MonthlyReportData $data,
        PdfExport $pdf,
    ): Response {
        Gate::authorize('viewAny', Member::class);

        return $pdf->download(
            'pdf.monthly-report',
            sprintf('monthly-report-%04d-%02d.pdf', $contributionPeriod->year, $contributionPeriod->month),
            $data->for($contributionPeriod),
        );
    }

    public function arrears(Request $request, TabularReport $report): Response
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
            ->values()
            ->all();

        return $report->render(
            $this->format($request),
            sprintf('arrears-%s', today()->toDateString()),
            'Arrears ageing',
            sprintf('As at %s', today()->toFormattedDateString()),
            ['Member number', 'Member', 'Period', 'Grace ended', 'Days overdue', 'Outstanding (UGX)'],
            $rows,
            [4, 5],
        );
    }

    public function contributions(
        Request $request,
        ContributionPeriod $contributionPeriod,
        TabularReport $report,
    ): Response {
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
            ->values()
            ->all();

        return $report->render(
            $this->format($request),
            sprintf('contributions-%04d-%02d', $contributionPeriod->year, $contributionPeriod->month),
            'Contribution collection',
            $contributionPeriod->label(),
            ['Member number', 'Member', 'Expected (UGX)', 'Paid (UGX)', 'Outstanding (UGX)', 'Status'],
            $rows,
            [2, 3, 4],
        );
    }

    public function payments(Request $request, TabularReport $report): Response
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
                $payment->contributionAmount(),
                $payment->withdrawal_fee_amount,
                $payment->unapplied_amount,
                $payment->paid_on->toDateString(),
                $payment->method->label(),
                $payment->recordedByMember?->full_name,
                $payment->reviewedByMember?->full_name,
            ])
            ->values()
            ->all();

        $rows = array_merge($rows, OpeningWithdrawalFee::query()->oldest('paid_on')->get()
            ->map(fn (OpeningWithdrawalFee $receipt): array => [
                $receipt->reference,
                Lang::string('Unallocated opening withdrawal fee'),
                $receipt->amount,
                0,
                $receipt->amount,
                0,
                $receipt->paid_on->toDateString(),
                Lang::string('Mobile Money'),
                null,
                null,
            ])->all());

        return $report->render(
            $this->format($request),
            sprintf('verified-payments-%s', today()->toDateString()),
            'Verified receipts',
            sprintf('All verified payments as at %s', today()->toFormattedDateString()),
            ['Reference', 'Member', 'Received (UGX)', 'Contributions / advance (UGX)', 'Withdrawal fees (UGX)', 'Unapplied credit (UGX)', 'Paid on', 'Method', 'Recorded by', 'Verified by'],
            $rows,
            [2, 3, 4, 5],
        );
    }

    public function expenses(Request $request, TabularReport $report): Response
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
                $expense->category->label(),
                $expense->amount,
                $expense->incurred_on->toDateString(),
                $expense->status->value,
                $expense->requestedByMember?->full_name,
                $expense->approvedByMember?->full_name,
                $expense->verifiedByMember?->full_name,
            ])
            ->values()
            ->all();

        return $report->render(
            $this->format($request),
            sprintf('expenses-%s', today()->toDateString()),
            'Expenses',
            sprintf('All expenses as at %s', today()->toFormattedDateString()),
            [
                'Reference', 'Purpose', 'Payee', 'Category', 'Amount (UGX)',
                'Incurred on', 'Status', 'Requested by', 'Approved by', 'Verified by',
            ],
            $rows,
            [4],
        );
    }

    public function governance(Request $request, TabularReport $report): Response
    {
        Gate::authorize('viewAny', Proposal::class);

        $rows = Proposal::query()
            ->withCount('votes')
            ->oldest()
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
            ->values()
            ->all();

        return $report->render(
            $this->format($request),
            sprintf('governance-%s', today()->toDateString()),
            'Governance record',
            sprintf('Proposals and outcomes as at %s', today()->toFormattedDateString()),
            [
                'Title', 'Status', 'Opened', 'Closed', 'Eligible voters',
                'Votes cast', 'Quorum required', 'Outcome',
            ],
            $rows,
            [4, 5, 6],
        );
    }

    /**
     * The audit log, most recent first.
     *
     * PDF is capped at the last 500 entries. The log has no ceiling and dompdf
     * builds the whole document in memory, so an unbounded PDF would eventually
     * exhaust it — and nobody reads a thousand-page audit trail anyway. The CSV
     * has no cap, and is the right format for a real investigation.
     */
    public function auditLog(Request $request, TabularReport $report): Response
    {
        Gate::authorize('viewAny', AuditLog::class);

        $format = $this->format($request);

        $query = AuditLog::query()->with('actorMember')->latest();

        if ($format === 'pdf') {
            $query->limit(500);
        }

        $rows = $query->get()
            ->map(fn (AuditLog $log): array => [
                $log->created_at->toDateTimeString(),
                $log->event,
                class_basename($log->auditable_type),
                $log->actorMember?->full_name,
                $log->ip_address,
            ])
            ->values()
            ->all();

        return $report->render(
            $format,
            sprintf('audit-log-%s', today()->toDateString()),
            'Audit log',
            $format === 'pdf'
                ? sprintf('Most recent 500 entries as at %s', today()->toFormattedDateString())
                : sprintf('All entries as at %s', today()->toFormattedDateString()),
            ['When', 'Event', 'Record type', 'Actor', 'IP address'],
            $rows,
        );
    }

    private function format(Request $request): string
    {
        return $request->string('format')->value() === 'pdf' ? 'pdf' : 'csv';
    }

    /**
     * A member may always take their own statement. Anyone else's needs the
     * permission to edit members, which is the officer boundary used elsewhere.
     */
    private function authorizeStatement(Member $member, User $user): void
    {
        Gate::authorize('view', $member);

        $own = Member::query()->firstWhere('user_id', $user->id)?->id === $member->id;

        abort_unless($own || $user->can('update', $member), 403);
    }

    /**
     * Ordered in the database rather than by sorting the collection: faster,
     * and free of the "is the relation loaded" question a sort closure has to
     * answer for every row.
     *
     * @return Collection<int, MemberObligation>
     */
    private function obligationRows(Member $member): Collection
    {
        return $member->obligations()
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
            ->get();
    }
}
