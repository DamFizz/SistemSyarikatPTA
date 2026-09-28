<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

#[Fillable(['user_id', 'action', 'module', 'description', 'old_value', 'new_value', 'ip_address'])]
#[WithoutTimestamps]
class AuditLog extends Model
{
    protected function casts(): array
    {
        return [
            'old_value' => 'array',
            'new_value' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an audit trail entry for the currently authenticated user.
     */
    public static function record(string $action, string $module, ?string $description = null, ?array $old = null, ?array $new = null): self
    {
        return static::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'old_value' => $old,
            'new_value' => $new,
            'ip_address' => Request::ip(),
        ]);
    }
}
