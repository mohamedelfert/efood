<?php

namespace App\Model;

use App\User;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'transaction_id',
        'reference',
        'admin_bonus',
        'transaction_type',
        'debit',
        'credit',
        'balance',
        'gateway',
        'status',
        'metadata',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'credit' => 'float',
        'debit' => 'float',
        'balance' => 'float',
        'admin_bonus' => 'array',
        'metadata' => 'array',
        'reference' => 'string',
        'gateway' => 'string',
        'status' => 'string',
        'created_at' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeAttribute(): string
    {
        return $this->credit > 0 ? 'credit' : 'debit';
    }

    public function getAmountAttribute(): float
    {
        return $this->credit > 0 ? $this->credit : $this->debit;
    }

    public function isCredit(): bool
    {
        return $this->credit > 0;
    }
}