<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\OpeningWithdrawalFee;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final readonly class GeneratePaymentReference
{
    public function handle(): string
    {
        return DB::transaction(function (): string {
            /** @var object{last_number: int|numeric-string}|null $counter */
            $counter = DB::table('payment_reference_counters')->where('id', 1)->lockForUpdate()->first();
            throw_if($counter === null, RuntimeException::class, 'Payment reference counter is missing. Run the migrations.');
            $number = (int) $counter->last_number;

            do {
                $reference = sprintf('MNIC-%06d', ++$number);
            } while (Payment::query()->where('reference', $reference)->exists() || OpeningWithdrawalFee::query()->where('reference', $reference)->exists());

            DB::table('payment_reference_counters')->where('id', 1)->update(['last_number' => $number]);

            return $reference;
        });
    }
}
