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
