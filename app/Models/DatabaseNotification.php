<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatabaseNotification extends Model
{
    protected $table = 'notifications';
    
    protected $fillable = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'academic_year_id',
        'read_at',
    ];
    
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';
    
    // العلاقة مع المستخدم
    public function notifiable()
    {
        return $this->morphTo();
    }
}