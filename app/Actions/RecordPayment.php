<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentStatus;
use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentEvidence;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final readonly class RecordPayment
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
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
            $payment = Payment::query()->create([
                ...$attributes,
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
