# Issue #17: Retry-Strategie — Analyse (Prompt A)

## HTTP-Calls im Make Multi-Tenant Loop

### Kritische HTTP Calls (mit Fehlerbehandlung erforderlich)

| Quelle | Ziel | Methode | Aktuell | 429/5xx-Handler | Logs |
|--------|------|---------|---------|-----------------|------|
| Make Engine | Portal `/make/tenants` | GET | ✅ Vorhanden | ❌ Nein | `api.md` erwähnt |
| Make Engine | Tenant WordPress `/wp/v2/posts` | POST | ✅ Vorhanden | ❌ Nein | Nicht dokumentiert |
| Make Engine | Externe RSS Feed | GET | ✅ Im Blueprint | ❌ Nein | Optional Retry in Make UI |
| Portal | `run-callback` | POST | ✅ Vorhanden | ✅ Optional | `wp_ltl_saas_runs` table |

---

## 1) Portal Endpoint: GET `/make/tenants`

**File**: [wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php](../../wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php) (Zeile 220–280)

**Aktuell**:
- ✅ Authentifizierung: `X-LTL-SAAS-TOKEN` Header
- ✅ SSL erzwungen (403 wenn nicht https)
- ✅ Timeout: keiner definiert (WordPress Default: 5s)
- ❌ Response-Code-Checks: Nein
- ❌ Retry-Logik: Nein
- ❌ 429-Handling: Nein
- ❌ 5xx-Handling: Nein

**Fehlerbehandlung in Make**:
- Wenn Portal 500 zurückgibt → Scenario bricht ab ❌
- Wenn Portal 503 zurückgibt → Scenario bricht ab ❌
- **Lösung**: Error Handler in Make vor Callback (Prompt B)

---

## 2) Tenant WordPress: POST `/wp/v2/posts`

**Ort**: Make Engine (in Make UI, nicht in diesem Repo)

**Aktuell**:
- Make verwendet Standard HTTP Module + Basic Auth
- ❌ Keine Retry-Logik in Make UI
- ❌ Keine 429-Behandlung (WordPress Rate Limiting)
- ❌ Keine 503-Behandlung

**Fehlerfälle**:
- 429 Too Many Requests → Plugin limit (z.B. Wordfence, Cloudflare)
- 503 Service Unavailable → WordPress in Wartung oder überlastet
- 504 Gateway Timeout → WordPress hängt

**Lösung**: Error Handler Route in Make (Prompt B)

---

## 3) RSS Feed: GET (external)

**Ort**: Make Engine (in Make UI)

**Aktuell**:
- ❌ Keine offizielle Retry-Logik
- ❌ Keine 429-Behandlung

**Fehlerfälle**:
- 429 → RSS-Hosting ist rate-limited
- 503 → RSS-Server down

**Lösung**: Optional im Make UI (Prompt B)

---

## 4) Portal Callback: POST `/run-callback`

**File**: [wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php](../../wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php) (Zeile 336–395)

**Aktuell**:
- ✅ Fehlerbehandlung: `wp_remote_post()` gibt WP_Error zurück
- ✅ Response-Code-Check: `wp_remote_retrieve_response_code()`
- ✅ Logging: `$wpdb->insert($table, $row)` mit `raw_payload`
- ❌ Retry-Logik: Nein
- ❌ Attempt-Counter: Nein

**Portal-seitig möglich**:
- Erweitere Logging-Felder: `attempts`, `last_http_status`
- Aber: Callback ist **eingehend**, nicht ausgehend
  - **Make Side** müsste retry

---

## 5) Portal: Test WordPress Connection

**File**: [wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php](../../wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php) (Zeile 413–456)

**Code**:
```php
$resp = wp_remote_get( $api_url, $args );
if ( is_wp_error( $resp ) ) {
    return new WP_REST_Response( [ 'success' => false, 'message' => $resp->get_error_message() ], 500 );
}
$code = wp_remote_retrieve_response_code( $resp );
```

**Aktuell**:
- ✅ Error Handling: Ja (WP_Error)
- ❌ Response-Code-Check: Prüft nur 200, nicht 429/503
- ❌ Retry: Nein

---

## Retry-Kandidaten (Priorität)

| Kandidat | Quelle | Problem | Priorität | Retry-Ort |
|----------|--------|---------|-----------|-----------|
| GET `/make/tenants` | Make → Portal | 500/503 | **HIGH** | Make Error Handler |
| POST `/wp/v2/posts` | Make → Tenant WP | 429/503 | **HIGH** | Make Error Handler |
| GET `external RSS` | Make → RSS Host | 429/503 | **MEDIUM** | Make Error Handler (optional) |
| POST `/run-callback` | Make → Portal | 500/503 | **MEDIUM** | Make Retry (Backoff) |

---

## Betroffene Module/Files

### Portal (PHP/WordPress)
- **`wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`**
  - `get_make_tenants()` — Response-Code-Logging optional
  - `run_callback()` — Already has error handling, extend logging fields
  - `test_wp_connection()` — Optional: Better response codes

### Make.com (UI / Blueprint)
- **`blueprints/sanitized/de/` und `blueprints/sanitized/us/`**
  - Need Error Handler routes for:
    1. `/make/tenants` HTTP request
    2. `/wp/v2/posts` HTTP request
    3. (Optional) RSS GET request
  - Each with: Filter `429 OR >= 500` → Sleep 2-5s → Retry 1x → Log fail

### Documentation
- **`docs/engineering/make/retry-strategy.md`** — NEW (Prompt B)
- **`docs/reference/api.md`** — UPDATE (add response codes section)
- **`docs/testing/smoke/issue-17.md`** — NEW (Prompt D)

---

## Logging Infrastruktur

### Wo Logs geschrieben werden

1. **WordPress Debug Log**: `wp-content/debug.log` (if WP_DEBUG enabled)
   ```php
   error_log('[LTL-SAAS] Gumroad ping: missing email');
   ```

2. **Runs Table**: `wp_ltl_saas_runs`
   ```php
   $wpdb->insert($table, [
       'tenant_id' => $tenant_id,
       'status' => $status,
       'error_message' => $error_message,
       'raw_payload' => $raw_payload,
   ]);
   ```

3. **Make.com Logs**: Built-in (Module output)

### Vorschlag für Retry-Logging

**Neue Felder in `wp_ltl_saas_runs`** (optional):
- `attempts` (TINYINT, default 1)
- `last_http_status` (SMALLINT, z.B. 429, 503)
- `retry_backoff_ms` (INT, z.B. 2000, 4000)

**Logging Format**:
```json
{
  "status": "error",
  "error_message": "429 Too Many Requests (attempt 2/2, backoff 2000ms)",
  "last_http_status": 429,
  "attempts": 2,
  "tenant_id": 123
}
```

---

## Zusammenfassung (Prompt A Output)

✅ **Retry Candidates identifiziert**:
1. GET `/make/tenants` (Portal) — 429/5xx Handler in Make UI
2. POST `/wp/v2/posts` (Tenant WP) — 429/5xx Handler in Make UI
3. GET RSS Feed (external) — Optional Handler in Make UI
4. POST `/run-callback` (Portal) — Log enhancement + Make-side retry

✅ **Betroffene Module**:
- `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`
- `blueprints/sanitized/de/` und `blueprints/sanitized/us/`
- `docs/` (new files + updates)

✅ **Logging-Infrastruktur**:
- `wp_ltl_saas_runs` table (exists, extend fields)
- `wp-content/debug.log` (WordPress native)
- Make.com module logs (native)

---

## Next Steps (Prompts B-D)
- **Prompt B**: Create `docs/engineering/make/retry-strategy.md` with Make UI steps
- **Prompt C**: Optional: Extend logging fields in Portal
- **Prompt D**: Create smoke tests + commit
