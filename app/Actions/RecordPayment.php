<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContributionPeriodStatus;
use App\Enums\ObligationStatus;
use App\Enums\PaymentStatus;
use App\Models\ContributionPeriod;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\Payment;
use App\Models\PaymentEvidence;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final readonly class RecordPayment
{
    public function __construct(
        private RecordAuditEvent $recordAuditEvent,
        private GetPaymentContributionDue $getPaymentContributionDue,
    ) {
        //
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(
        array $attributes,
        ?Member $actor = null,
        ?UploadedFile $evidence = null,
        ?string $ipAddress = null,
    ): Payment {
        return DB::transaction(function () use ($attributes, $actor, $evidence, $ipAddress): Payment {
            /** @var array{member_id: string, amount: int|numeric-string, contribution_period_id?: string|null, withdrawal_fee_amount?: int|numeric-string|null, contribution_due_amount?: int|numeric-string|null, excess_allocation?: string|null} $allocation */
            $allocation = Validator::make($attributes, [
                'member_id' => ['required', 'string'],
                'amount' => ['required', 'integer', 'min:1'],
                'contribution_period_id' => ['nullable', 'uuid'],
                'withdrawal_fee_amount' => ['nullable', 'integer', 'min:0'],
                'contribution_due_amount' => ['nullable', 'integer', 'min:0'],
                'excess_allocation' => ['nullable', Rule::in(['advance', 'fees', 'split'])],
            ])->validate();

            $member = Member::query()->lockForUpdate()->findOrFail($allocation['member_id']);
            $periodId = $allocation['contribution_period_id'] ?? null;
            $due = $this->getPaymentContributionDue->handle($member)['amount'];

            if ($periodId !== null) {
                $period = ContributionPeriod::query()->lockForUpdate()->find($periodId);
                $obligation = MemberObligation::query()->where('member_id', $member->id)
                    ->where('contribution_period_id', $periodId)->lockForUpdate()->first();

                if ($period === null || $period->status !== ContributionPeriodStatus::Open || $obligation === null || in_array($obligation->status, [ObligationStatus::Waived, ObligationStatus::Cancelled], true)) {
                    throw ValidationException::withMessages(['contribution_period_id' => __('Choose an open period with an eligible obligation for this member.')]);
                }

                $due = $obligation->outstanding();
            }

            $amount = (int) $allocation['amount'];
            $excess = $due > 0 ? max(0, $amount - $due) : 0;
            $choice = $allocation['excess_allocation'] ?? null;
            $fee = (int) ($allocation['withdrawal_fee_amount'] ?? 0);

            if (isset($allocation['contribution_due_amount']) && (int) $allocation['contribution_due_amount'] !== $due) {
                throw ValidationException::withMessages(['contribution_due_amount' => __('The member balance changed. Refresh the page and review the allocation again.')]);
            }

            if ($excess > 0 && $choice === null) {
                throw ValidationException::withMessages(['excess_allocation' => __('Choose how to allocate the excess: advance, withdrawal fees, or a split.')]);
            }

            if ($fee > $excess || ($excess === 0 && in_array($choice, ['fees', 'split'], true))) {
                throw ValidationException::withMessages(['withdrawal_fee_amount' => __('Withdrawal fees can only come from money above the outstanding balance for the selected contribution month.')]);
            }

            if (($choice === 'fees' && $fee !== $excess) || ($choice === 'split' && ($fee <= 0 || $fee >= $excess)) || (! in_array($choice, ['fees', 'split'], true) && $fee !== 0)) {
                throw ValidationException::withMessages(['withdrawal_fee_amount' => __('The fee amount must match your excess allocation choice.')]);
            }

            unset($attributes['excess_allocation']);

            $payment = Payment::query()->create([
                ...$attributes,
                'contribution_period_id' => $periodId,
                'withdrawal_fee_amount' => $fee,
                'contribution_due_amount' => $due,
                'status' => PaymentStatus::Submitted,
                'unapplied_amount' => 0,
                'recorded_by_member_id' => $actor?->id,
            ]);

            if ($evidence instanceof UploadedFile) {
                PaymentEvidence::query()->create([
                    'payment_id' => $payment->id,
                    'path' => (string) $evidence->store('payment-evidence', 'local'),
                    'original_name' => $evidence->getClientOriginalName(),
                    'mime_type' => $evidence->getClientMimeType(),
                    'size' => (int) $evidence->getSize(),
                    'uploaded_by_member_id' => $actor?->id,
                ]);
            }

            $this->recordAuditEvent->handle(
                'payment.recorded',
                $payment,
                $actor,
                null,
                $payment->toArray(),
                $ipAddress,
            );

            return $payment;
        });
    }
}
