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
  "is_active": true
}
```

**Curl Beispiel:**
```bash
curl -X GET \
  -H "X-LTL-SAAS-TOKEN: <token>" \
  https://<your-portal>/wp-json/ltl-saas/v1/make/tenants
```

**Hinweise:**
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
