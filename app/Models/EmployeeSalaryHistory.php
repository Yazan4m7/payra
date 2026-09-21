<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalaryHistory extends Model
{
    protected $fillable = ['employee_id', 'amount', 'effective_from', 'reason', 'created_by'];

    protected $casts = ['amount' => 'decimal:3', 'effective_from' => 'date'];

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
