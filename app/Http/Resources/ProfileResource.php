<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $roleCode = $this->role?->code;

        $departmentId = null;
        $departmentName = null;
        $gpa = null;

        // Student
        if ($roleCode === 'student') {
            $departmentId = $this->studentProfile?->department?->id;
            $departmentName = $this->studentProfile?->department?->name;
            $gpa = $this->studentProfile?->gpa;
        }

        // Doctor / TA
        if (in_array($roleCode, ['doctor', 'TA', 'ta'])) {
            $departmentId = $this->staffprofile?->department?->id;
            $departmentName = $this->staffprofile?->department?->name;
        }

        return [
            'id' => $this->id,
            'role_id' => $this->role_id,
            'role_code' => $roleCode,

            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'track_name' => $this->track_name,
            'profile_image_url' => $this->profile_image_url,

            // 🔥 المهمين
            'department_id' => $departmentId,
            'department_name' => $departmentName,

            // Student only
            'gpa' => $gpa,
        ];
    }
}