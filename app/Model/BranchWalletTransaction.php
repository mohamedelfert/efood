<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class BranchWalletTransaction extends Model
{
    protected $fillable = [
        'branch_id',
        'transaction_id',
        'reference',
        'transaction_type',
        'debit',
        'credit',
        'balance',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'debit' => 'float',
        'credit' => 'float',
        'balance' => 'float',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
