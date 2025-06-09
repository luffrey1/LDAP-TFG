<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessAttempt extends Model
{
    protected $fillable = [
        'username',
        'nombre',
        'hostname',
        'ip',
        'created_at'
    ];

    public $timestamps = false;
} 