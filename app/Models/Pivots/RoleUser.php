<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

class RoleUser extends Pivot
{
    public $incrementing = true;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'user_id',
    ];
}
