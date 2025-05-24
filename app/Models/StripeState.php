<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StripeState extends Model
{
    protected $fillable = ['state', 'expires_at'];
    
    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
