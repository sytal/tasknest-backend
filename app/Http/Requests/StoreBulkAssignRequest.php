<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBulkAssignRequest extends FormRequest
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
        $project = $this->route('project');

        return [
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => [
                'integer',
                Rule::exists('tasks', 'id')->where('project_id', $project->id),
            ],
            'assignee_ids' => ['required', 'array', 'min:1'],
            'assignee_ids.*' => ['integer', 'exists:users,id'],
        ];
    }

    /**
     * Ensure every assignee is actually a member of the project.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $project = $this->route('project');
            $assigneeIds = collect($this->input('assignee_ids', []))->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id);

            if ($assigneeIds->isEmpty()) {
                return;
            }

            $memberIds = $project->members()->pluck('users.id');
            $invalid = $assigneeIds->diff($memberIds);

            if ($invalid->isNotEmpty()) {
                $validator->errors()->add(
                    'assignee_ids',
                    'The following users are not members of this project: '.$invalid->implode(', ').'.',
                );
            }
        });
    }
}
