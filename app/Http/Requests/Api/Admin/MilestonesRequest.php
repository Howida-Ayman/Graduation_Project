<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MilestonesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'previous_milestone_id' => 'nullable|exists:milestones,id',
            'title'=>'required|string|unique:milestones,title',
            'description'=>'nullable|string',
            'start_date'=>'required|date|after_or_equal:today',
            'deadline'=>'required|date|after:start_date',
            'requirements' => 'nullable|array',
            'requirements.*' => 'string|max:500' // كل متطلب على حدة
        ];
    }
    public function messages(): array
    {
        return [
            'deadline.after' => 'Deadline must be after start date',
            'requirements.*.max' => 'Each requirement must not exceed 500 characters'
        ];
    }
}
