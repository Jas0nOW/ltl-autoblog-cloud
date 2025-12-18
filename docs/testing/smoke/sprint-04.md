# Smoke Tests — Core API + Limits (Sprint 04)

> **Canonical Reference**: This is the primary smoke test guide for Phase 1 functionality. Other sprint files are either archived or feature-specific.

## Prerequisites
- Plugin activated and configured
- At least 1 test user with settings + app password configured
- Make Token set in admin panel (Portal Settings)
- HTTPS enabled (can use `--insecure` for self-signed during testing)

## Phase 0/1 Core Tests (Mandatory)

### Setup Verification
- [ ] Plugin activated: Navigate to Plugins, confirm "LTL AutoBlog Cloud" status
- [ ] Test user exists with WP connection + RSS configured
- [ ] Make Token set in admin: Settings → LTL AutoBlog Cloud → Make Token not empty
- [ ] Debug log enabled: `define('WP_DEBUG_LOG', true);` in wp-config.php

### Monthly Limits (Phase 0)
- [ ] posts_this_month=0 → `/make/tenants` returns `skip=false`, `remaining=limit`
- [ ] posts_this_month=limit → `/make/tenants` returns `skip=true`, `reason=monthly_limit_reached`
- [ ] Month boundary: Set `posts_period_start` to last month → `/make/tenants` resets to 0

### Callback Processing (Phase 0)
- [ ] Status success: `/run-callback status=success` increments `posts_this_month` by 1
- [ ] Status failed: `/run-callback status=failed` leaves usage unchanged, error logged
- [ ] Month boundary + callback: Month rollover resets, then increments by 1 (atomic)

### Authentication (Phase 0)
- [ ] `/make/tenants` without token → HTTP 403 Forbidden
- [ ] `/make/tenants` with invalid token → HTTP 403 Forbidden
- [ ] `/make/tenants` with valid token + SSL → HTTP 200 OK
- [ ] `/active-users` without X-LTL-API-Key → HTTP 403 Forbidden
- [ ] `/active-users` with valid X-LTL-API-Key → HTTP 200 OK

### Phase 1 Security Tests (New)

#### Callback Idempotency (Issue #21)
- [ ] Send callback twice with same `execution_id` → Second request returns 200 (cached), usage not double-incremented
- [ ] Verify `wp_ltl_saas_runs` table has `execution_id` unique index
- [ ] Verify raw_payload stored: `/active-users` response includes callback history

#### Retry Telemetry (Issue #17)
- [ ] Send callback with `attempts=3, last_http_status=429, retry_backoff_ms=1000`
- [ ] Verify fields stored in `wp_ltl_saas_runs`
- [ ] Verify debug log entry: `[LTL-SAAS] Callback: Retry telemetry - attempts=3...`
- [ ] Verify no crash when fields are omitted (backward compatible)

#### Month Rollover Atomic (Issue #22)
- [ ] Simulate parallel requests on month boundary: `/make/tenants` + `/run-callback` simultaneously
- [ ] Verify only ONE request resets month counter (no double-reset via atomic WHERE clause)
- [ ] Verify other request sees already-reset state and increments correctly

#### Rate Limiting (Issue #23)
- [ ] Send 10 failed auth requests (invalid token) within 15 min → All 10 return 403
- [ ] Send 11th request within same window → HTTP 429 Too Many Requests
- [ ] Verify debug log: `[LTL-SAAS] Rate limit exceeded: IP=..., endpoint=...`
- [ ] Wait 15+ minutes → Transient expires, next request returns 403 (not 429)
- [ ] Verify X-Forwarded-For handling: Requests from different IPs not rate-limited together

---

## HTTP Test Commands (curl)

### 1. GET /make/tenants (correct: X-LTL-SAAS-TOKEN)
```sh
curl -H "X-LTL-SAAS-TOKEN: <token_value>" \
  https://<site>/wp-json/ltl-saas/v1/make/tenants \
  --insecure
```
**Expected:** 200 OK with tenant array including `posts_used_month`, `posts_limit_month`

### 2. GET /make/tenants (missing token → 403)
```sh
curl -i https://<site>/wp-json/ltl-saas/v1/make/tenants --insecure
```
**Expected:** 403 Forbidden

### 3. POST /run-callback (status=success, with execution_id)
```sh
curl -X POST \
  -H "X-LTL-API-Key: <api_key>" \
  -H "Content-Type: application/json" \
  -d '{
    "tenant_id": 123,
    "execution_id": "exec_12345",
    "status": "success",
    "started_at": "2025-12-18T10:00:00Z",
    "finished_at": "2025-12-18T10:01:00Z",
    "posts_created": 1,
    "error_message": null,
    "attempts": 1,
    "retry_backoff_ms": 0
  }' \
  https://<site>/wp-json/ltl-saas/v1/run-callback \
  --insecure
```
**Expected:** 200 OK, `posts_used_month` incremented

### 4. POST /run-callback (duplicate execution_id → idempotent)
```sh
# Re-send exact same request as Test 3 (same execution_id)
curl -X POST \
  -H "X-LTL-API-Key: <api_key>" \
  -H "Content-Type: application/json" \
  -d '{
    "tenant_id": 123,
    "execution_id": "exec_12345",
    "status": "success",
    "started_at": "2025-12-18T10:00:00Z",
    "finished_at": "2025-12-18T10:01:00Z",
    "posts_created": 1,
    "error_message": null,
    "attempts": 1,
    "retry_backoff_ms": 0
  }' \
  https://<site>/wp-json/ltl-saas/v1/run-callback \
  --insecure
```
**Expected:** 200 OK, `posts_used_month` NOT incremented again (idempotent)

### 5. POST /run-callback (status=failed, usage unchanged)
```sh
curl -X POST \
  -H "X-LTL-API-Key: <api_key>" \
  -H "Content-Type: application/json" \
  -d '{
    "tenant_id": 123,
    "execution_id": "exec_99999",
    "status": "failed",
    "started_at": "2025-12-18T10:00:00Z",
    "finished_at": "2025-12-18T10:01:00Z",
    "posts_created": 0,
    "error_message": "RSS parse error",
    "attempts": 2,
    "last_http_status": 500,
    "retry_backoff_ms": 500
  }' \
  https://<site>/wp-json/ltl-saas/v1/run-callback \
  --insecure
```
**Expected:** 200 OK, `posts_used_month` unchanged, telemetry stored in DB

### 6. POST /run-callback (rate limit test: 11th failed auth)
```sh
# After 10 failed auth requests (invalid token), 11th request:
curl -X POST \
  -H "X-LTL-API-Key: WRONG_KEY" \
  https://<site>/wp-json/ltl-saas/v1/run-callback \
  --insecure -w "\nHTTP Status: %{http_code}\n"
```
**Expected:** HTTP 429 Too Many Requests (after first 10 returned 403)

### 7. GET /active-users (correct: X-LTL-API-Key)
```sh
curl -H "X-LTL-API-Key: <api_key>" \
  https://<site>/wp-json/ltl-saas/v1/active-users \
  --insecure
```
**Expected:** 200 OK with user list (passwords masked/hashed)

---

## Verification Checklist

After running all tests:
- [ ] No PHP errors in debug.log
- [ ] All HTTP status codes match expected values
- [ ] Database fields exist: `execution_id`, `attempts`, `last_http_status`, `retry_backoff_ms`
- [ ] Rate limit transient key exists in wp_options: `ltl_saas_ratelimit_*`
- [ ] Log entries in debug.log for retry telemetry and rate limit triggers
- [ ] No SQL injection or XSS vectors exposed in error messages
- [ ] Backward compatibility: Older requests without new fields still work

---

## Notes
- All dates/times in ISO 8601 format: `2025-12-18T10:00:00Z`
- Use `--insecure` for local testing with self-signed certs
- Replace `<site>`, `<token_value>`, `<api_key>` with actual values
- For batch testing, use `.env` file or config management

