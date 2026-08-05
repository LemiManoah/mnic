<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentStatus;
use App\Models\Member;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class RejectPayment
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    public function handle(Payment $payment, Member $verifier, string $reason, ?string $ipAddress = null): Payment
    {
        if ($payment->recorded_by_member_id === $verifier->id) {
            throw new InvalidArgumentException('A payment cannot be reviewed by the member who recorded it.');
        }

        if ($payment->status !== PaymentStatus::Submitted) {
            throw new InvalidArgumentException('Only a submitted payment can be rejected.');
        }

        return DB::transaction(function () use ($payment, $verifier, $reason, $ipAddress): Payment {
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
    }
}
