<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ObligationStatus;
use App\Enums\PaymentStatus;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Notifications\PaymentVerified;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class VerifyPayment
{
    public function __construct(
        private RecordAuditEvent $recordAuditEvent,
        private NotifyMembers $notifyMembers,
    ) {
        //
    }

    public function handle(Payment $payment, Member $verifier, ?string $ipAddress = null): Payment
    {
        // Maker-checker: enforced here as well as in the policy, so the rule
        // holds for any caller (job, command, MCP request) and not only HTTP.
        throw_if($payment->recorded_by_member_id === $verifier->id, InvalidArgumentException::class, 'A payment cannot be verified by the member who recorded it.');

        throw_if($payment->status !== PaymentStatus::Submitted, InvalidArgumentException::class, 'Only a submitted payment can be verified.');

        DB::transaction(function () use ($payment, $verifier, $ipAddress): Payment {
            $before = $payment->toArray();
            $remaining = $payment->amount;

            $obligations = MemberObligation::query()
                ->with('contributionPeriod')
                ->where('member_id', $payment->member_id)
                ->whereIn('status', [
                    ObligationStatus::Unpaid->value,
                    ObligationStatus::PartiallyPaid->value,
                ])
                ->get()
                // Oldest period first, so payments always settle arrears before
                // they run ahead into future obligations.
                ->sortBy(fn (MemberObligation $obligation): string => sprintf(
                    '%04d-%02d',
                    $obligation->contributionPeriod->year ?? 0,
                    $obligation->contributionPeriod->month ?? 0,
                ));

            foreach ($obligations as $obligation) {
                if ($remaining <= 0) {
                    break;
                }

                $applied = min($remaining, $obligation->outstanding());
                $amountPaid = $obligation->amount_paid + $applied;

                PaymentAllocation::query()->create([
                    'payment_id' => $payment->id,
                    'member_obligation_id' => $obligation->id,
                    'amount' => $applied,
                ]);

                $obligation->update([
                    'amount_paid' => $amountPaid,
                    'status' => $amountPaid >= $obligation->amount
                        ? ObligationStatus::Paid
                        : ObligationStatus::PartiallyPaid,
                ]);

                $remaining -= $applied;
            }

            $payment->update([
                'status' => PaymentStatus::Verified,
                // Anything left over is held as an unapplied advance against
                // future contribution periods.
                'unapplied_amount' => $remaining,
                'reviewed_by_member_id' => $verifier->id,
                'reviewed_at' => now(),
            ]);

            $this->recordAuditEvent->handle(
                'payment.verified',
                $payment,
                $verifier,
                $before,
                $payment->toArray(),
                $ipAddress,
            );

            return $payment;
        });

        // Told after the transaction commits, never inside it — a rollback must
        // not leave a member holding an email about a payment that was not
        // actually verified.
        $payment->loadMissing('member.user');

        if ($payment->member !== null) {
            $this->notifyMembers->handle([$payment->member], new PaymentVerified($payment));
        }

        return $payment;
    }
}
