# CSMF-MRS API

Laravel 12 API for CSMF-MRS. See the [project README](../../README.md) for
setup and [docs/api](../../docs/api/README.md) for the endpoint contracts.

```
php artisan serve --host=csmf-mrs --port=8003   # run
php artisan migrate --seed                      # roles + permission catalog (safe to re-run)
php artisan csmf:create-sysadmin                # first System Administrator (password prompt)
php artisan test                                # Pest (uses db_csmf_mrs_test)
vendor/bin/pint --test                          # PSR-12 lint
```

## Where things live

| Concern | Location |
|---|---|
| Sign-in (Fortify, headless, `/api` prefix) | `config/fortify.php`, `app/Providers/FortifyServiceProvider.php` |
| Permission catalog and Admin defaults | `app/Support/PermissionCatalog.php` |
| Permission checks (`can:users.view`) | `Gate::before` in `app/Providers/AppServiceProvider.php` |
| Lockout / escalation rules | `app/Services/AccountGuard.php` |
| Account use cases | `app/Services/UserAccountService.php` |
| Audit trail | `app/Support/AuditLogger.php`, `app/Observers/AuditObserver.php`, `app/Listeners/AuditAuthenticationEvents.php` |
| Pending account steps | `app/Http/Middleware/EnsurePasswordIsChanged.php`, `EnsureTwoFactorIsEnabled.php` |
