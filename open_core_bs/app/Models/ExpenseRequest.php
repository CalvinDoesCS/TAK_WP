<?php

namespace App\Models;

use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class ExpenseRequest extends Model implements AuditableContract
{
    use Auditable, SoftDeletes, UserActionsTrait;

    protected $table = 'expense_requests';

    protected $fillable = [
        'user_id',
        'for_date',
        'expense_type_id',
        'document_url',
        'remarks',
        'amount',
        'approved_amount',
        'status',
        'approved_at',
        'approved_by_id',
        'rejected_at',
        'rejected_by_id',
        'admin_remarks',
        'processed_in_payroll',
        'payroll_record_id',
        'payroll_processed_at',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'for_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'created_at' => 'datetime',
        'payroll_processed_at' => 'datetime',
        'amount' => 'float',
        'approved_amount' => 'float',
        'processed_in_payroll' => 'boolean',
    ];

    public function expenseType(): BelongsTo
    {
        return $this->belongsTo(ExpenseType::class, 'expense_type_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExpenseRequestItem::class);
    }

    public function payrollRecord(): BelongsTo
    {
        return $this->belongsTo(\Modules\Payroll\App\Models\PayrollRecord::class, 'payroll_record_id');
    }
}
