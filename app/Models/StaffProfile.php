<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use PhpParser\Builder\Function_;

class StaffProfile extends Model
{
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'int';
    protected $fillable = [
        'user_id',
        'department_id',
    ];
    public Function user()
    {
        return $this->belongsTo(User::class);
    }
    public function department()
{
    return $this->belongsTo(Department::class);
}
}
