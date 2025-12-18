# Make.com Multi-Tenant Scenario – Schritt-für-Schritt Anleitung

## Überblick: Szenario-Ablauf

1. **Trigger:** Scheduler (z.B. alle 30 Minuten)
2. **HTTP Pull Tenants:**
   - HTTP GET auf `/wp-json/ltl-saas/v1/make/tenants` (mit Token)
   - Liefert Array aller aktiven Tenants
3. **Iterator:**
   - Iteriere über jeden Tenant im Array
4. **Pro Tenant:**
   - **RSS holen:** RSS-Feed des Tenants abrufen
   - **AI Schritt:** Prompt generieren, Text erzeugen
   - **WP Create Post:** Per HTTP POST auf Tenant-WordPress (REST API, Basic Auth)
   - **Callback:** Ergebnis an Portal zurückmelden (`/wp-json/ltl-saas/v1/run-callback`)

---

## Benötigte Make-Module (generisch)
- Scheduler/Trigger (z.B. „Scheduler“)
- HTTP (GET/POST)
- Iterator (Array iterieren)
- RSS (Feed abrufen)
- Text/AI (OpenAI, Gemini, o.ä.)
- JSON (Daten mappen)
- Error Handler (optional: Break/Error-Branch)

---

## Beispiel-Payloads

### 1. Portal → Make (`/make/tenants`)
**Request:**
```http
GET /wp-json/ltl-saas/v1/make/tenants
Header: X-LTL-SAAS-TOKEN: <token>
```
**Response:**
```json
[
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
  },
  ...
]
```

### 2. Make → Portal (Run Callback)
**Request:**
```http
POST /wp-json/ltl-saas/v1/run-callback
Header: X-LTL-API-Key: <api_key>
Content-Type: application/json

{
  "tenant_id": 123,
  "status": "success",
  "posts_created": 1,
  "started_at": "2025-12-18T10:00:00Z",
  "finished_at": "2025-12-18T10:05:00Z"
}
```
**Response:**
- 200 OK: {"success": true, "id": 456}
- 401 Unauthorized
- 400 Bad Request (e.g., missing tenant_id)

**Hinweise:**
- Neue Felder in `/make/tenants`: `skip`, `skip_reason`, `remaining`, `posts_this_month`, `posts_limit_month`.
- Callback-Status: `success` oder `error`. Bei `success` wird `posts_this_month` inkrementiert.

---

## Fehlerhandling
- Bei Fehler pro Tenant: Sofort Callback an Portal mit `status: failed` und `error_message`.
- Scenario läuft für andere Tenants weiter.

---

## Sicherheitsnotiz
- **Token/API-Key niemals in Logs oder Screenshots zeigen!**
- **Nur HTTPS verwenden!** (Portal und Tenant-WordPress)
- Token/Keys regelmäßig rotieren und nur im Secure Storage ablegen.

---

**Mit dieser Anleitung kannst du das bestehende Make.com Scenario auf Multi-Tenant umbauen.**
