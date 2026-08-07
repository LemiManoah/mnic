<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Member;
use App\Models\PositionPoll;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class NominatePositionPollCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $poll = $this->route('poll');

        return $poll instanceof PositionPoll
            && (bool) $this->user()?->can('nominate', $poll);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'member_id' => ['required', 'string', Rule::exists(Member::class, 'id')],
            'manifesto' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
