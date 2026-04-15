<?php

namespace App\Http\Requests\Api\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'problem_statement' => 'required|string',
            'category' => 'required|string|max:255',
            'solution' => 'required|string',

            'department_id' => [
                'required',
                Rule::exists('departments', 'id')->where(function ($query) {
                    $query->where('is_active', 1);
                }),
            ],

            'project_type_id' => [
                'required',
                Rule::exists('project_types', 'id')->where(function ($query) {
                    $query->where('is_active', 1);
                }),
            ],

            'technologies' => 'nullable|string',

            'leader_user_id' => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role_id', 4);
                }),
            ],

            'attachment' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Project title is required',
            'description.required' => 'Project description is required',
            'problem_statement.required' => 'Problem statement is required',
            'solution.required' => 'Solution is required',
            'category.required' => 'Category is required',

            'department_id.required' => 'Department is required',
            'department_id.exists' => 'Selected department is invalid or inactive',

            'project_type_id.required' => 'Project type is required',
            'project_type_id.exists' => 'Selected project type is invalid or inactive',

            'leader_user_id.required' => 'Leader is required',
            'leader_user_id.exists' => 'Selected leader is invalid',

            'attachment.mimes' => 'Attachment must be a PDF or Word document',
            'attachment.max' => 'Attachment size must not exceed 10 MB',

            'image.image' => 'File must be an image',
            'image.mimes' => 'Image must be jpeg, png, jpg, or gif',
            'image.max' => 'Image size must not exceed 5 MB',
        ];
    }
}