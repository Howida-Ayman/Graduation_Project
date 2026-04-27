<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
public function toArray(Request $request): array
{
    $roleCode = strtolower($this->role?->code);
    
    if ($roleCode === 'student' && !$this->relationLoaded('studentProfile')) {
        $this->load('studentProfile.department');
    }
    
    if (in_array($roleCode, ['doctor', 'ta']) && !$this->relationLoaded('staffprofile')) {
        $this->load('staffprofile.department');
    }

    // 👇 نوحد الديبارتمنت
    $departmentId = null;
    $departmentName = null;

    if ($roleCode === 'student') {
        $departmentId = $this->studentProfile?->department_id;
        $departmentName = $this->studentProfile?->department?->name;
    }

    if (in_array($roleCode, ['doctor', 'ta'])) {
        $departmentId = $this->staffprofile?->department_id;
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
        'profile_image_url' => $this->profile_image_url ? asset($this->profile_image_url) : null,

        // 👇 نفس الاسم لكل الناس
        'department_id' => $departmentId,
        'department_name' => $departmentName,

        // يظهر بس للطالب
        'gpa' => $this->when($roleCode === 'student', $this->studentProfile?->gpa),
    ];
}
}