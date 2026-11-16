<?php

namespace App\Http\Requests;

use App\Models\Label;
use App\Support\Limits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isManagement();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:'.Limits::PROJECT_DESCRIPTION_MAX],
            'due_date' => ['nullable', 'date'],
            'color' => ['nullable', 'string', Rule::in(Label::PALETTE)],
            'members' => ['sometimes', 'array'],
            'members.*.user_id' => ['required_with:members', 'integer', 'exists:users,id'],
            'members.*.project_role' => ['required_with:members', 'in:lead,member'],
        ];
    }
}
