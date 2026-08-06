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

final readonly class ReversePayment
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    public function handle(Payment $payment, Member $reverser, string $reason, ?string $ipAddress = null): Payment
    {
        return DB::transaction(function () use ($payment, $reverser, $reason, $ipAddress): Payment {
            $payment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            throw_if($payment->status !== PaymentStatus::Verified, InvalidArgumentException::class, 'Only a verified payment can be reversed.');

            $before = $payment->toArray();

            $payment->loadMissing('allocations.memberObligation.contributionPeriod');

            foreach ($payment->allocations as $allocation) {
                $obligation = MemberObligation::query()
                    ->lockForUpdate()
                    ->findOrFail($allocation->member_obligation_id);

                $obligation->loadMissing('contributionPeriod');

                throw_if(
                    $obligation->contributionPeriod->status === ContributionPeriodStatus::Closed,
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
                'reversed_by_member_id' => $reverser->id,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
            ]);

            $this->recordAuditEvent->handle(
                'payment.reversed',
                $payment,
                $reverser,
                $before,
                $payment->toArray(),
                $ipAddress,
            );

            return $payment;
        });
    }
}
