<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ClubPosition;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\Proposal;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class CreateProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Proposal::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'meeting_id' => ['nullable', 'string', Rule::exists(Meeting::class, 'id')],
            'election_position' => ['nullable', new Enum(ClubPosition::class), 'required_with:election_member_id'],
            'election_member_id' => ['nullable', 'string', Rule::exists(Member::class, 'id'), 'required_with:election_position'],
        ];
    }
}
