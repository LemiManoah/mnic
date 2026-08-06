<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\VoteChoice;
use App\Models\Proposal;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class CastVoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $proposal = $this->route('proposal');
        assert($proposal instanceof Proposal);

        return (bool) $this->user()?->can('vote', $proposal);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'choice' => ['required', new Enum(VoteChoice::class)],
            'has_conflict' => ['nullable', 'boolean'],
            'conflict_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
