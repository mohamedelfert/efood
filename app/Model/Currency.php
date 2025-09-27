<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'name', 'code', 'symbol', 'exchange_rate', 
        'is_primary', 'is_active', 'decimal_places', 'position'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'exchange_rate' => 'decimal:4'
    ];

    public static function getPrimary()
    {
        return self::where('is_primary', true)->where('is_active', true)->first();
    }

    public static function getActive()
    {
        return self::where('is_active', true)->orderBy('is_primary', 'desc')->get();
    }

    public function formatAmount($amount)
    {
        $formatted = number_format($amount, $this->decimal_places);
        
        if ($this->position === 'before') {
            return $this->symbol . ' ' . $formatted;
        }
        
        return $formatted . ' ' . $this->symbol;
    }

    public function convertFromPrimary($amount)
    {
        return $amount * $this->exchange_rate;
    }

    public function convertToPrimary($amount)
    {
        return $amount / $this->exchange_rate;
    }
}