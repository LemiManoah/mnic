<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentStatus;
use App\Models\Member;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class RequestPaymentReversal
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    public function handle(Payment $payment, Member $requester, string $reason, ?string $ipAddress = null): Payment
    {
        return DB::transaction(function () use ($payment, $requester, $reason, $ipAddress): Payment {
            $payment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            throw_if($payment->status !== PaymentStatus::Verified, InvalidArgumentException::class, 'Only a verified payment can be put up for reversal.');

            $before = $payment->toArray();

            // The money is not unwound yet — that waits for a second officer.
            // The payment stays out of the "verified" bucket in the meantime so
            // it is obvious something is pending against it.
            $payment->update([
                'status' => PaymentStatus::ReversalPending,
                'reversal_requested_by_member_id' => $requester->id,
                'reversal_requested_at' => now(),
                'reversal_reason' => $reason,
            ]);

            $this->recordAuditEvent->handle(
                'payment.reversal_requested',
                $payment,
                $requester,
                $before,
                $payment->toArray(),
                $ipAddress,
            );

            return $payment;
        });
    }
}
