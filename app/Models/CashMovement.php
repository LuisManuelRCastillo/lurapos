<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashMovement extends Model
{
    protected $fillable = [
        'type', 'concept', 'amount', 'notes', 'branch_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
