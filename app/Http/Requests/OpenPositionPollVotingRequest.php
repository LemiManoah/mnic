<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\PositionPoll;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class OpenPositionPollVotingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $poll = $this->route('poll');

        return $poll instanceof PositionPoll
            && (bool) $this->user()?->can('openVoting', $poll);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'closes_at' => ['required', 'date', 'after:now'],
        ];
    }
}
