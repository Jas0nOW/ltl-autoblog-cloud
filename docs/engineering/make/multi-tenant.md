# Make.com Multi-Tenant Scenario – Implementation Guide

> **Version**: 1.0
> **Status**: Complete — Deliverable blueprint available
> **Location**: [blueprints/LTL-MULTI-TENANT-SCENARIO.md](../../blueprints/LTL-MULTI-TENANT-SCENARIO.md) (documentation)
> **Template**: [blueprints/sanitized/LTL-MULTI-TENANT-TEMPLATE.json](../../blueprints/sanitized/LTL-MULTI-TENANT-TEMPLATE.json) (importable JSON)

---

## Quick Start

1. **Read Full Spec**: Open [LTL-MULTI-TENANT-SCENARIO.md](../../blueprints/LTL-MULTI-TENANT-SCENARIO.md)
2. **Import Template**: Copy contents of `LTL-MULTI-TENANT-TEMPLATE.json` into Make.com
3. **Configure**:
   - Set `{{PORTAL_URL}}`
   - Set `{{MAKE_TOKEN}}` (from Portal Admin)
   - Set `{{API_KEY}}` (from Portal Admin)
4. **Test**: Run manually on 1 tenant
5. **Deploy**: Enable Scheduler

---

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
- Scheduler/Trigger (z.B. „Scheduler")
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
    "posts_limit_month": 20,
    "posts_period_start": "2025-12-01"
  },
  ...
]
```

### 2. Make → Portal (Run Callback - Success)
**Request:**
```http
POST /wp-json/ltl-saas/v1/run-callback
Header: X-LTL-API-Key: <api_key>
Content-Type: application/json

{
  "tenant_id": 123,
  "execution_id": "exec_abc123",
  "status": "success",
  "posts_created": 1,
  "started_at": "2025-12-18T10:00:00Z",
  "finished_at": "2025-12-18T10:05:00Z",
  "attempts": 1,
  "last_http_status": 200,
  "retry_backoff_ms": 0
}
```
**Response:**
```json
{
  "success": true,
  "id": 456,
  "message": "Callback processed. Usage incremented."
}
```

### 3. Make → Portal (Run Callback - Failure)
**Request:**
```http
POST /wp-json/ltl-saas/v1/run-callback
Header: X-LTL-API-Key: <api_key>
Content-Type: application/json

{
  "tenant_id": 123,
  "execution_id": "exec_def456",
  "status": "failed",
  "posts_created": 0,
  "error_message": "RSS parse error",
  "started_at": "2025-12-18T10:00:00Z",
  "finished_at": "2025-12-18T10:02:00Z",
  "attempts": 3,
  "last_http_status": 500,
  "retry_backoff_ms": 2000
}
```
**Response:**
```json
{
  "success": false,
  "error": "RSS parse error",
  "id": null,
  "message": "Callback logged. Usage unchanged."
}
```

---

## Fehlerhandling
- Bei Fehler pro Tenant: Sofort Callback an Portal mit `status: failed` und `error_message`.
- Scenario läuft für andere Tenants weiter (Error Handler mit continue=true).
- Rate Limiting: Wenn Portal antwortet mit HTTP 429, verwende Retry (exponential backoff).

---

## Sicherheitsnotiz
- **Token/API-Key niemals in Logs oder Screenshots zeigen!**
- **Nur HTTPS verwenden!** (Portal und Tenant-WordPress)
- Token/Keys in Make.com Secure Storage speichern (nicht hardcoded)
- Token/Keys regelmäßig rotieren und nur im Secure Storage ablegen.
- Tenant App Passwords verwenden, nie volle Passwörter

---

## Erweiterungen & Customization

### AI Integration
- Template enthält optional OpenAI Modul
- Austauschbar mit: Anthropic, Google Gemini, Hugging Face, o.ä.
- Oder entfernen wenn manueller Content gewünscht

### Webhook auf Feedback
- Callback könnte auch External Webhook triggern (z.B. Slack notification on failure)

### Retry Logic
- Alle HTTP Module sollten Retry konfiguriert haben (3 attempts, exponential backoff)
- Make.com unterstützt auto-retry auf 5xx/429

### Batch Processing
- Standard: 1 Post pro Tenant pro Run
- Ändern: In RSS Modul `max_items` erhöhen

---

## See Also
- [Full Blueprint Documentation](../../blueprints/LTL-MULTI-TENANT-SCENARIO.md)
- [API Reference](../../reference/api.md)
- [Sanitizer Script](../../scripts/sanitize_make_blueprints.py)
