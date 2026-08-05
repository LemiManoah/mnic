<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentEvidence;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class PaymentEvidenceController
{
    /**
     * Evidence lives on the private disk and is only ever streamed through
     * this authorised endpoint, never served as a public URL.
     */
    public function show(Payment $payment, PaymentEvidence $evidence): StreamedResponse
    {
        Gate::authorize('viewEvidence', $payment);

        return Storage::disk('local')->download($evidence->path, $evidence->original_name);
    }
}
