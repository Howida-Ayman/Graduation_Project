<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RuleItem extends Model
{
    protected $table='rule_items';
    protected $fillable = [
        'section',
        'rules'
    ];
}
