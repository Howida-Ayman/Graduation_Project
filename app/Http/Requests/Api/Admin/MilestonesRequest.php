<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MilestonesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $milestoneId = $this->route('id') ?? $this->route('milestone');

        return [
            'project_course_id' => 'required|exists:project_courses,id',
            'previous_milestone_id' => 'nullable|exists:milestones,id',

            'title' => 'required|string|max:255|unique:milestones,title,' . $milestoneId,

            'description' => 'nullable|string',
            'max_score' => 'required|numeric|min:0.01|max:100',

            'start_date' => 'required|date|after_or_equal:today',
            'deadline' => 'required|date|after:start_date',

            'requirements' => 'nullable|array',
            'requirements.*' => 'required|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'project_course_id.required' => 'Project course is required.',
            'project_course_id.exists' => 'Selected project course does not exist.',

            'title.required' => 'Milestone title is required.',
            'title.unique' => 'Milestone title already exists.',

            'max_score.required' => 'Milestone maximum score is required.',
            'max_score.numeric' => 'Milestone maximum score must be a number.',
            'max_score.min' => 'Milestone maximum score must be greater than zero.',

            'start_date.after_or_equal' => 'Start date cannot be before today.',
            'deadline.after' => 'Deadline must be after start date.',

            'requirements.array' => 'Requirements must be sent as an array.',
            'requirements.*.required' => 'Each requirement must have a value.',
            'requirements.*.max' => 'Each requirement must not exceed 500 characters.',
        ];
    }
}