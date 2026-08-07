<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentStatus;
use App\Models\Member;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class RejectPaymentReversal
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    public function handle(Payment $payment, Member $rejecter, ?string $ipAddress = null): Payment
    {
        return DB::transaction(function () use ($payment, $rejecter, $ipAddress): Payment {
            $payment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            throw_if($payment->status !== PaymentStatus::ReversalPending, InvalidArgumentException::class, 'There is no reversal pending on this payment.');

            // Separation of duties: whoever asked for the reversal cannot be the
            // one who decides it. Mirrored in PaymentPolicy::decideReversal so
            // neither route nor administrator bypass can defeat it.
            throw_if(
                $payment->reversal_requested_by_member_id === $rejecter->id,
                InvalidArgumentException::class,
                'The member who requested the reversal cannot decide it.',
            );

            $before = $payment->toArray();

            $payment->update([
                'status' => PaymentStatus::Verified,
                'reversal_requested_by_member_id' => null,
                'reversal_requested_at' => null,
                'reversal_reason' => null,
            ]);

            $this->recordAuditEvent->handle(
                'payment.reversal_rejected',
                $payment,
                $rejecter,
                $before,
                $payment->toArray(),
                $ipAddress,
            );

            return $payment;
        });
    }
}
