<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Actions\ChangeMemberStatus;
use App\Enums\MemberStatus;
use App\Models\Member;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

final class ChangeMemberStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $member = $this->route('member');
        assert($member instanceof Member);

        return (bool) $this->user()?->can('changeStatus', $member);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'to_status' => ['required', new Enum(MemberStatus::class)],
            'reason' => ['required', 'string', 'max:1000'],
            'effective_date' => ['required', 'date'],
            'resolution_reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $toStatus = MemberStatus::tryFrom($this->string('to_status')->value());

            if ($toStatus === null) {
                return;
            }

            $member = $this->route('member');
            assert($member instanceof Member);

            if (! ChangeMemberStatus::isTransitionAllowed($member->status, $toStatus)) {
                $validator->errors()->add('to_status', __('This status transition is not allowed.'));
            }
        });
    }
}
