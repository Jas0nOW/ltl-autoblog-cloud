# SMOKE_TEST_SPRINT_04.md

## Setup
- [ ] Plugin aktiviert
- [ ] Mindestens 1 Test-User mit Settings existiert
- [ ] Make Token ist gesetzt

## Limits
- [ ] Tenant hat posts_this_month = 0  make/tenants liefert skip=false, remaining=limit
- [ ] Setze posts_this_month = limit  make/tenants liefert skip=true, reason=monthly_limit_reached
- [ ] Wechsel Monat (posts_period_start auf letzten Monat)  make/tenants resettet auf 0

## Callback
- [ ] run-callback "success"  posts_this_month +1
- [ ] Wenn Monat gewechselt  callback resettet dann inkrementiert
- [ ] run-callback "failed"  Status gespeichert, Usage unverändert

## Regression
- [ ] make/tenants gibt 403 ohne Token (expect 403, not 200)
- [ ] get_active_users erfordert X-LTL-API-Key Header

---

## Beispiel-HTTP-Tests (curl)

### 1. GET /make/tenants (correct header: X-LTL-SAAS-TOKEN)
\\\sh
curl -H "X-LTL-SAAS-TOKEN: <token_value>" \
  https://<site>/wp-json/ltl-saas/v1/make/tenants
\\\
**Expected:** 200 OK with tenant array

### 2. GET /make/tenants (missing token, expect 403)
\\\sh
curl -i https://<site>/wp-json/ltl-saas/v1/make/tenants
\\\
**Expected:** 403 Forbidden

### 3. POST /run-callback (correct header: X-LTL-API-Key, status=success)
\\\sh
curl -X POST \
  -H "X-LTL-API-Key: <api_key>" \
  -H "Content-Type: application/json" \
  -d '{
    "tenant_id": 123,
    "status": "success",
    "started_at": "2025-12-18T10:00:00Z",
    "finished_at": "2025-12-18T10:01:00Z",
    "posts_created": 1,
    "error_message": null,
    "meta": {}
  }' \
  https://<site>/wp-json/ltl-saas/v1/run-callback
\\\
**Expected:** 200 OK, posts_used_month incremented

### 4. POST /run-callback (status=failed, usage unchanged)
\\\sh
curl -X POST \
  -H "X-LTL-API-Key: <api_key>" \
  -H "Content-Type: application/json" \
  -d '{
    "tenant_id": 123,
    "status": "failed",
    "started_at": "2025-12-18T10:00:00Z",
    "finished_at": "2025-12-18T10:01:00Z",
    "posts_created": 0,
    "error_message": "RSS parse error",
    "meta": {}
  }' \
  https://<site>/wp-json/ltl-saas/v1/run-callback
\\\
**Expected:** 200 OK, posts_used_month unchanged, error logged
