<?php

namespace App\Traits;

use App\Config\Constants;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait UserActionsTrait
{
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function createdAt()
    {
        return $this->created_at->format(Constants::DateTimeFormat);
    }

    public function updatedAt()
    {
        return $this->updated_at->format(Constants::DateTimeFormat);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
