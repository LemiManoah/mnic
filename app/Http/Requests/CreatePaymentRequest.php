<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ClubRole;
use App\Enums\PaymentMethod;
use App\Models\Member;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

final class CreatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Payment::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'member_id' => ['required', 'string', Rule::exists(Member::class, 'id')],
            // Amounts are whole UGX; zero or negative payments are rejected.
            'amount' => ['required', 'integer', 'min:1'],
            'contribution_period_id' => ['required', 'uuid'],
            'withdrawal_fee_amount' => ['nullable', 'integer', 'min:0'],
            'contribution_due_amount' => ['nullable', 'integer', 'min:0'],
            'excess_allocation' => ['nullable', Rule::in(['advance', 'fees', 'split'])],
            'paid_on' => ['required', 'date'],
            'method' => ['required', new Enum(PaymentMethod::class)],
            'external_reference' => ['nullable', 'string', 'max:255', Rule::unique(Payment::class, 'external_reference')],
            'notes' => ['nullable', 'string', 'max:1000'],
            'evidence' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();
            assert($user instanceof User);

            if ($user->hasAnyRole([ClubRole::Treasurer->value, ClubRole::Administrator->value])) {
                return;
            }

            // Everyone else may only record a payment against their own record.
            $member = Member::query()->firstWhere('user_id', $user->id);

            if ($member?->id !== $this->string('member_id')->value()) {
                $validator->errors()->add('member_id', __('You may only record a payment for yourself.'));
            }
        });
    }
}
