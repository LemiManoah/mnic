<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class RequestPaymentReversalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $payment = $this->route('payment');

        return $payment instanceof Payment
            && (bool) $this->user()?->can('requestReversal', $payment);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
