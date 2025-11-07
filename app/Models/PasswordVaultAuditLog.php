<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordVaultAuditLog extends Model
{
    const UPDATED_AT = null; // Only track created_at

    protected $fillable = [
        'password_vault_id',
        'user_id',
        'action',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the password vault entry
     */
    public function passwordVault(): BelongsTo
    {
        return $this->belongsTo(PasswordVault::class);
    }

    /**
     * Get the user who performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log an audit action
     */
    public static function logAction(
        PasswordVault $vault,
        string $action,
        ?array $metadata = null
    ): void {
        self::create([
            'password_vault_id' => $vault->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
        ]);
    }
}
