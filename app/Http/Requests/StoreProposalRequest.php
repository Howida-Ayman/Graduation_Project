<?php
// app/Http/Requests/StoreProposalRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

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
            'department_id' => 'required|exists:departments,id',
            'project_type_id' => 'required|exists:project_types,id',
            'technologies' => 'nullable|string',
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
            'project_type_id.required' => 'Project type is required',
            'attachment.mimes' => 'Attachment must be a PDF or Word document',
            'image.image' => 'File must be an image',
        ];
    }
}