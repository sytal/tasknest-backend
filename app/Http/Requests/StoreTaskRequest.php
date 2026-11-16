<?php

namespace App\Http\Requests;

use App\Support\Limits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
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
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:'.Limits::TITLE_MAX],
            'description' => ['nullable', 'string', 'max:'.Limits::TASK_DESCRIPTION_MAX],
            'status' => ['sometimes', Rule::in(['todo', 'in_progress', 'done'])],
            'priority' => ['sometimes', Rule::in(['low', 'medium', 'high'])],
            'start_date' => ['nullable', 'date'],
            'due_date' => array_filter(['nullable', 'date', $this->filled('start_date') ? 'after_or_equal:start_date' : null]),
            'estimate_hours' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'label_ids' => ['sometimes', 'array'],
            'label_ids.*' => ['integer', 'exists:labels,id'],
            'assignee_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
