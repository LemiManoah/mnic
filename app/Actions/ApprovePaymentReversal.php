<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContributionPeriodStatus;
use App\Enums\ObligationStatus;
use App\Enums\PaymentStatus;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The second half of a payment reversal: unwind the allocations and mark the
 * payment reversed. Requesting the reversal is RequestPaymentReversal.
 */
final readonly class ApprovePaymentReversal
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    public function handle(Payment $payment, Member $approver, ?string $ipAddress = null): Payment
    {
        return DB::transaction(function () use ($payment, $approver, $ipAddress): Payment {
            Member::query()->lockForUpdate()->findOrFail($payment->member_id);
            $payment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            throw_if($payment->status !== PaymentStatus::ReversalPending, InvalidArgumentException::class, 'There is no reversal pending on this payment.');

            // Separation of duties: whoever asked for the reversal cannot be the
            // one who approves it. Mirrored in PaymentPolicy::decideReversal so
            // neither route nor administrator bypass can defeat it.
            throw_if(
                $payment->reversal_requested_by_member_id === $approver->id,
                InvalidArgumentException::class,
                'The member who requested the reversal cannot approve it.',
            );

            $before = $payment->toArray();

            $payment->loadMissing('allocations.memberObligation.contributionPeriod');

            foreach ($payment->allocations as $allocation) {
                $obligation = MemberObligation::query()
                    ->lockForUpdate()
                    ->findOrFail($allocation->member_obligation_id);

                $obligation->loadMissing('contributionPeriod');

                throw_if(
                    $obligation->contributionPeriod?->status === ContributionPeriodStatus::Closed,
                    InvalidArgumentException::class,
                    'A payment allocated to a closed contribution period cannot be reversed directly.',
                );

                $amountPaid = max($obligation->amount_paid - $allocation->amount, 0);

                $obligation->update([
                    'amount_paid' => $amountPaid,
                    'status' => match (true) {
                        $amountPaid === 0 => ObligationStatus::Unpaid,
                        $amountPaid >= $obligation->amount => ObligationStatus::Paid,
                        default => ObligationStatus::PartiallyPaid,
                    },
                ]);

                $allocation->delete();
            }

            $payment->update([
                'status' => PaymentStatus::Reversed,
                'unapplied_amount' => 0,
                'reversed_by_member_id' => $approver->id,
                'reversed_at' => now(),
            ]);

            $this->recordAuditEvent->handle(
                'payment.reversed',
                $payment,
                $approver,
                $before,
                $payment->toArray(),
                $ipAddress,
            );

            return $payment;
        });
    }
}
