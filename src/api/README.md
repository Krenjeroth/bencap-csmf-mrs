# CSMF-MRS API

Laravel 12 API for CSMF-MRS. See the [project README](../../README.md) for
setup and the [Master Playbook](../../docs/CSMF-MRS-Playbook.html) Tab 03
for the API contracts.

```
php artisan serve --host=csmf-mrs --port=8003   # run
php artisan test                                # Pest (uses db_csmf_mrs_test)
vendor/bin/pint --test                          # PSR-12 lint
```

## Endpoints so far

| Method | Path | Purpose |
|---|---|---|
| GET | `/up` | Framework liveness check |
| GET | `/api/v1/health` | API and database status: `{"status":"ok"\|"degraded","app","database","time"}`; 503 when the database is unreachable |
