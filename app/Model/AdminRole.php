<?php

namespace App\Model;

use App\Model\Admin;
use App\Model\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminRole extends Model
{
    protected $table = 'admin_roles';

    // protected $fillable = ['name', 'branch_id', 'module_access', 'status'];
    protected $guarded = [];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function admins(): HasMany
    {
        return $this->hasMany(Admin::class, 'admin_role_id');
    }
}