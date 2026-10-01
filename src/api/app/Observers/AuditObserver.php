<?php

namespace App\Observers;

use App\Support\AuditLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * Records created / updated / deleted / restored for observed models.
 * Registered in AppServiceProvider for User, Role and Permission.
 */
class AuditObserver
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function created(Model $model): void
    {
        $this->audit->record('created', $model, null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        unset($changes['updated_at']);

        // Login bookkeeping, passwords and two-factor secrets are recorded as
        // named events by AuditAuthenticationEvents instead (no secrets logged).
        unset($changes['last_login_at'], $changes['last_login_ip'], $changes['two_factor_confirmed_at']);
        $changes = array_diff_key($changes, array_flip(AuditLogger::REDACTED));

        if ($changes === []) {
            return;
        }

        $old = array_intersect_key($model->getOriginal(), $changes);
        $this->audit->record('updated', $model, $old, $changes);
    }

    public function deleted(Model $model): void
    {
        $this->audit->record('deleted', $model, $model->getOriginal(), null);
    }

    public function restored(Model $model): void
    {
        $this->audit->record('restored', $model, null, $model->getAttributes());
    }
}
