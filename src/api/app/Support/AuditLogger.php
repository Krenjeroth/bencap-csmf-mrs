<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Writes audit entries with who, what, where (IP, user agent, URL).
 * Secrets are always stripped from old/new values.
 */
class AuditLogger
{
    /** Attributes never written to the audit log. */
    public const REDACTED = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public function __construct(private readonly Request $request) {}

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    public function record(string $event, ?Model $subject = null, ?array $old = null, ?array $new = null, ?string $userId = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => $userId ?? Auth::id(),
            'event' => $event,
            'auditable_type' => $subject ? class_basename($subject) : null,
            'auditable_id' => $subject ? (string) $subject->getKey() : null,
            'old_values' => $this->clean($old),
            'new_values' => $this->clean($new),
            'ip_address' => $this->request->ip(),
            'user_agent' => Str::limit((string) $this->request->userAgent(), 509),
            'url' => Str::limit($this->request->fullUrl(), 497),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $values
     * @return array<string, mixed>|null
     */
    private function clean(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $values = Arr::except($values, [...self::REDACTED, 'updated_at', 'created_at']);

        return $values === [] ? null : $values;
    }
}
