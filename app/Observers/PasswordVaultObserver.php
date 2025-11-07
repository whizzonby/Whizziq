<?php

namespace App\Observers;

use App\Models\PasswordVault;
use App\Models\PasswordVaultAuditLog;

class PasswordVaultObserver
{
    /**
     * Handle the PasswordVault "created" event.
     */
    public function created(PasswordVault $passwordVault): void
    {
        PasswordVaultAuditLog::logAction($passwordVault, 'created', [
            'title' => $passwordVault->title,
            'category' => $passwordVault->category,
        ]);
    }

    /**
     * Handle the PasswordVault "updated" event.
     */
    public function updated(PasswordVault $passwordVault): void
    {
        $changes = [];

        if ($passwordVault->isDirty('title')) {
            $changes['title'] = [
                'old' => $passwordVault->getOriginal('title'),
                'new' => $passwordVault->title,
            ];
        }

        if ($passwordVault->isDirty('encrypted_password')) {
            $changes['password'] = 'changed';
        }

        if ($passwordVault->isDirty('category')) {
            $changes['category'] = [
                'old' => $passwordVault->getOriginal('category'),
                'new' => $passwordVault->category,
            ];
        }

        PasswordVaultAuditLog::logAction($passwordVault, 'updated', [
            'changes' => $changes,
        ]);
    }

    /**
     * Handle the PasswordVault "deleted" event.
     */
    public function deleted(PasswordVault $passwordVault): void
    {
        PasswordVaultAuditLog::logAction($passwordVault, 'deleted', [
            'title' => $passwordVault->title,
            'soft_deleted' => true,
        ]);
    }

    /**
     * Handle the PasswordVault "restored" event.
     */
    public function restored(PasswordVault $passwordVault): void
    {
        PasswordVaultAuditLog::logAction($passwordVault, 'restored', [
            'title' => $passwordVault->title,
        ]);
    }

    /**
     * Handle the PasswordVault "force deleted" event.
     */
    public function forceDeleted(PasswordVault $passwordVault): void
    {
        // Note: This log won't be saved since the record is being force deleted
        // It will cascade delete the audit logs as well
    }
}
