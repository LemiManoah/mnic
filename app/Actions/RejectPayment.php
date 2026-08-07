<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentStatus;
use App\Models\Member;
use App\Models\Payment;
use App\Notifications\PaymentRejected;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class RejectPayment
{
    public function __construct(
        private RecordAuditEvent $recordAuditEvent,
        private NotifyMembers $notifyMembers,
    ) {
        //
    }

    public function handle(Payment $payment, Member $verifier, string $reason, ?string $ipAddress = null): Payment
    {
        throw_if($payment->recorded_by_member_id === $verifier->id, InvalidArgumentException::class, 'A payment cannot be reviewed by the member who recorded it.');

        throw_if($payment->status !== PaymentStatus::Submitted, InvalidArgumentException::class, 'Only a submitted payment can be rejected.');

        DB::transaction(function () use ($payment, $verifier, $reason, $ipAddress): Payment {
            $before = $payment->toArray();

            $payment->update([
                'status' => PaymentStatus::Rejected,
                'rejection_reason' => $reason,
                'reviewed_by_member_id' => $verifier->id,
                'reviewed_at' => now(),
            ]);

            $this->recordAuditEvent->handle(
                'payment.rejected',
                $payment,
                $verifier,
                $before,
                $payment->toArray(),
                $ipAddress,
            );

            return $payment;
        });

        // A rejection is the one notification a member most needs: their money
        // is not counted and only they can fix it.
        $payment->loadMissing('member.user');

        if ($payment->member !== null) {
            $this->notifyMembers->handle([$payment->member], new PaymentRejected($payment));
        }

        return $payment;
    }
}
