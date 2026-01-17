<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemPaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'method_name',
        'slug',
        'driver_name',
        'settings',
        'image',
        'is_active',
        'mode',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
