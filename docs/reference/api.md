# API Reference – LTL AutoBlog Cloud

## GET /wp-json/ltl-saas/v1/make/tenants

**Beschreibung:**
Liefert eine Liste aller aktiven Tenants für Make.com (Multi-Tenant Config Pull).

**Auth:**
- Header: `X-LTL-SAAS-TOKEN: <token>`
- Token wird in WordPress-Option `ltl_saas_make_token` gesetzt

**Response:**
- 200 OK + JSON-Array von Tenants
- 401 wenn Header fehlt
- 403 wenn Token fehlt/falsch/leer

**Tenant-Objekt (Issue #8):**
```json
{
  "tenant_id": 123,
  "site_url": "https://kunde.de",
  "wp_username": "kunde",
  "wp_app_password": "<decrypted>",
  "rss_url": "https://kunde.de/feed",
  "language": "de",
  "tone": "professional",
  "publish_mode": "draft",
  "frequency": "weekly",
  "plan": "basic",
  "is_active": true,
  "skip": false,
  "skip_reason": "",
  "posts_used_month": 0,
  "posts_limit_month": 30,
  "posts_remaining": 30
}
```

**Hinweise:**
- Plan names (canonical): `free` (10/mo), `basic` (30/mo), `pro` (120/mo), `studio` (300/mo) — see `docs/product/pricing-plans.md`
- `posts_used_month` (Issue #8): Current month usage (renamed from `posts_this_month` for clarity)
- `posts_limit_month` (Issue #8): Plan-based limit (derived from `ltl_saas_plan_posts_limit()`)
- `posts_remaining` (Issue #8): Calculated = `posts_limit_month - posts_used_month`
- `skip=true` → Skip this tenant (either `is_active=false` or `monthly_limit_reached`)
- Month rollover happens automatically in this endpoint: if `posts_period_start != current month start`, resets `posts_used_month` to 0
- Alle URLs werden validiert.
- Secrets werden niemals geloggt.
- Endpoint ist deaktiviert, wenn kein Token gesetzt ist.

---

## POST /wp-json/ltl-saas/v1/run-callback

**Beschreibung:**
Callback-Endpoint für Make.com, um Ergebnisse eines Runs zurückzumelden.

**Auth:**
- Header: `X-LTL-API-Key: <api_key>`
- API-Key wird in WordPress-Option `ltl_saas_api_key` gesetzt

**Request:**
```json
{
  "tenant_id": 123,
  "execution_id": "make_exec_abc123_retry_1",
  "status": "success", // oder "failed"
  "started_at": "2025-12-18T10:00:00Z",
  "finished_at": "2025-12-18T10:01:00Z",
  "posts_created": 1,
  "error_message": null,
  "attempts": 1,
  "last_http_status": null,
  "retry_backoff_ms": 0,
  "meta": { "post_id": 456, "title": "..." }
}
```

**Idempotency (Issue #21):**
- `execution_id` ist optional aber empfohlen: eindeutige Make.com Execution ID
- Wenn derselbe `execution_id` zweimal gesendet wird (Retry/Fehlerfall): Portal antwortet mit 200 OK + `"idempotent": true`, inkrementiert Usage aber NICHT zweimal
- Ohne `execution_id`: Callback wird mehrfach verarbeitet (altes Verhalten, für Backward Compat)

**Retry Telemetry (Issue #17):**
- `attempts` (optional, default 1): Retry attempt count (1 = first try, 2 = after 1 retry)
- `last_http_status` (optional): HTTP status code from last Make.com error (429, 503, etc.)
- `retry_backoff_ms` (optional, default 0): Backoff delay in milliseconds (2000, 4000, etc.)
- These fields are stored in `wp_ltl_saas_runs` table and logged to `debug.log` for debugging

**Response:**
- 200 OK bei Erfolg
- 401 wenn Header fehlt
- 403 wenn API-Key fehlt/falsch/leer
- 400 bei ungültigen Daten
- 500 bei Serverfehlern

**Curl Beispiel:**
```bash
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
    "meta": { "post_id": 456, "title": "..." }
  }' \
  https://<your-portal>/wp-json/ltl-saas/v1/run-callback
```

**Hinweise:**
- Fehlerhafte Requests liefern detaillierte Fehlermeldungen.
- Endpoint ist deaktiviert, wenn kein API-Key gesetzt ist.
---

## POST /wp-json/ltl-saas/v1/gumroad/webhook

**Beschreibung (Issue #7):**
Webhook-Endpoint für Gumroad Billing Events. Verarbeitet Gumroad Verkäufe, Abos, Stornierungen und Rückerstattungen.

**Auth:**
- Query-Param: `?secret=<secret>`
- Secret wird in WordPress-Option `ltl_saas_gumroad_secret` gesetzt
- HMAC-SHA256 Validierung des Gumroad-Signatures

**Event Semantik:**
- `sale` oder `subscribe` (mit `refunded=false` oder ohne `refunded` Feld) → Benutzer aktivieren, Plan zuweisen
- `cancel` oder `refund` (mit `refunded=true`) → Benutzer deaktivieren (nicht löschen)

**Request-Body (Gumroad Webhook):**
```json
{
  "product_id": "prod_ABC123",
  "product_name": "LTL AutoBlog Pro",
  "license_key": null,
  "url": "https://example.com",
  "email": "customer@example.com",
  "price": 4900,
  "currency": "USD",
  "recurring": true,
  "refunded": false,
  "subscription_id": "sub_XYZ789",
  "purchase_id": "pur_ABC123"
}
```

**Response:**
- 200 OK bei erfolgreicher Verarbeitung
- 401 bei fehlendem/falschem Secret
- 400 bei ungültigen Daten (fehlende Email, Product Map)
- 500 bei Serverfehlern

**Logging:**
Alle Events werden in `wp-content/debug.log` protokolliert:
- Secret-Validierungsfehler
- Benutzer erstellt/aktualisiert
- Plan zugewiesen
- Unmapped-Produkte (mit Fallback-Plan)
- Benutzer deaktiviert (Refund)

**Curl Beispiel:**
```bash
curl -X POST \
  -H "X-Gumroad-Secret: <your_secret>" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d 'email=customer@example.com&product_id=prod_ABC123&refunded=false&subscription_id=sub_XYZ789' \
  https://<your-portal>/wp-json/ltl-saas/v1/gumroad/webhook
```

**Backward Compatibility:**
Das Legacy-Endpoint `/gumroad/ping` funktioniert identisch und wird noch unterstützt:
```
https://<your-portal>/wp-json/ltl-saas/v1/gumroad/ping
```

**Hinweise:**
- **Keine Zeichen-Duplikation**: Jeder Webhook wird genau einmal verarbeitet (Idempotenz via HMAC + Log-Prüfung).
- Secret Header oder Query-Param akzeptiert.
- Alle Benutzer-Emails werden normalisiert (lowercase).
- Bei unbekanntem Plan wird Fallback-Plan aus `wp_options` verwendet.

---

## GET /wp-json/ltl-saas/v1/active-users

**Beschreibung:**
Liefert eine Liste aller aktiven Benutzer mit ihren Settings (für Make.com Iteration).

**Auth:**
- Header: `X-LTL-API-Key: <api_key>`
- API-Key wird in WordPress-Option `ltl_saas_api_key` gesetzt

**Response:**
- 200 OK + JSON-Array von aktiven Benutzern
- 401 wenn Header fehlt
- 403 wenn API-Key fehlt/falsch/leer

**Benutzer-Objekt:**
```json
{
  "user_id": 123,
  "user_email": "user@example.com",
  "rss_url": "https://example.com/feed",
  "language": "de",
  "tone": "professional",
  "publish_mode": "draft",
  "frequency": "weekly",
  "plan": "pro",
  "is_active": true
}
```

**Hinweise:**
- Secrets (wp_app_password, wp_url) werden NICHT in dieser Response geliefert (nur in `/make/tenants`).
- Nur aktive Benutzer werden gelistet.

**Curl Beispiel:**
```bash
curl -H "X-LTL-API-Key: <api_key>" \
  https://<your-portal>/wp-json/ltl-saas/v1/active-users
```

---

## POST /wp-json/ltl-saas/v1/test-connection

**Beschreibung:**
Testet die Verbindung zu einem Kunden-WordPress (nur eingeloggte Benutzer).

**Auth:**
- Benutzer muss eingeloggt sein (Session/Cookie)

**Request:**
```json
{
  "wp_url": "https://example.com",
  "wp_username": "admin",
  "wp_app_password": "xxxx yyyy zzzz ..."
}
```

**Response:**
- 200 OK wenn Verbindung erfolgreich
- 400 wenn Parameter fehlen/ungültig
- 401 wenn Benutzer nicht eingeloggt

**Curl Beispiel:**
```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <session_token>" \
  -d '{
    "wp_url": "https://example.com",
    "wp_username": "admin",
    "wp_app_password": "xxxx yyyy zzzz ..."
  }' \
  https://<your-portal>/wp-json/ltl-saas/v1/test-connection
```

---

## POST /wp-json/ltl-saas/v1/test-rss

**Beschreibung:**
Testet das Parsing eines RSS-Feeds (nur eingeloggte Benutzer).

**Auth:**
- Benutzer muss eingeloggt sein (Session/Cookie)

**Request:**
```json
{
  "rss_url": "https://example.com/feed"
}
```

**Response:**
- 200 OK + JSON mit Feed-Info (Titel, Einträge) wenn erfolgreich
- 400 wenn URL ungültig/nicht erreichbar
- 401 wenn Benutzer nicht eingeloggt

**Curl Beispiel:**
```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{
    "rss_url": "https://example.com/feed"
  }' \
  https://<your-portal>/wp-json/ltl-saas/v1/test-rss
```