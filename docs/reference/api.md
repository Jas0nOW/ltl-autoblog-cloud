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

**Tenant-Objekt:**
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
  "remaining": 20,
  "posts_this_month": 0,
  "posts_limit_month": 20
}
```

**Curl Beispiel:**
```bash
curl -X GET \
  -H "X-LTL-SAAS-TOKEN: <token>" \
  https://<your-portal>/wp-json/ltl-saas/v1/make/tenants
```

**Hinweise:**
- Neue Felder: `skip`, `skip_reason`, `remaining`, `posts_this_month`, `posts_limit_month`.
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
  "status": "success", // oder "failed"
  "started_at": "2025-12-18T10:00:00Z",
  "finished_at": "2025-12-18T10:01:00Z",
  "posts_created": 1,
  "error_message": null,
  "meta": { "post_id": 456, "title": "..." }
}
```

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
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "prod_ABC123",
    "email": "customer@example.com",
    "refunded": false,
    "subscription_id": "sub_XYZ789"
  }' \
  https://<your-portal>/wp-json/ltl-saas/v1/gumroad/webhook?secret=<your_secret>
```

**Backward Compatibility:**
Das Legacy-Endpoint `/gumroad/ping` funktioniert identisch und wird noch unterstützt:
```
https://<your-portal>/wp-json/ltl-saas/v1/gumroad/ping?secret=<your_secret>
```

**Hinweise:**
- **Keine Zeichen-Duplikation**: Jeder Webhook wird genau einmal verarbeitet (Idempotenz via HMAC + Log-Prüfung).
- Secret darf nicht geloggt werden, nur Hash.
- Alle Benutzer-Emails werden normalisiert (lowercase).
- Bei unbekanntem Plan wird Fallback-Plan aus `wp_options` verwendet.