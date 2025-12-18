# Issue #17: Logging Enhancement + Smoke Tests

## Prompt C — Logging nach Retry (Portal-seitig optional)

### Vorschlag: Erweiterte Felder in `wp_ltl_saas_runs`

Die `wp_ltl_saas_runs` Tabelle existiert bereits. Folgende Felder könnten optional erweitert werden:

**Aktuell**:
```sql
- id
- tenant_id
- status (success/failed)
- started_at
- finished_at
- posts_created
- error_message (longtext)
- raw_payload (longtext)
- created_at
```

**Neue Felder (optional, nur für besseres Debugging)**:
```sql
ALTER TABLE wp_ltl_saas_runs ADD COLUMN (
  attempts TINYINT DEFAULT 1,           -- 1 = first attempt, 2 = after retry
  last_http_status SMALLINT DEFAULT NULL, -- 429, 503, etc.
  retry_backoff_ms INT DEFAULT 0         -- 2000 = 2 seconds
);
```

### Logging-Format im Portal

Wenn Make Callback sendet, Portal nutzt für Log:

```json
{
  "status": "error",
  "error_message": "Failed to create post (attempt 2/2, backoff 2000ms): HTTP 429 Too Many Requests",
  "last_http_status": 429,
  "attempts": 2,
  "raw_payload": { "tenant_id": 123, "...": "..." }
}
```

### Implementation im `run_callback` Endpoint ✅

**Datei**: [wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php](../../wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php)

**Status**: IMPLEMENTED (2025-12-18, Commit: TBD)

The callback now accepts and stores:
- `attempts` (integer, default 1): retry attempt counter
- `last_http_status` (integer, optional): HTTP status code from last attempt
- `retry_backoff_ms` (integer, default 0): backoff delay in milliseconds

Fields are persisted in `wp_ltl_saas_runs` table and logged to `debug.log` when attempts > 1.

### Make Callback mit Retry-Info

Make sendet jetzt optional:

```json
POST /wp-json/ltl-saas/v1/run-callback

{
  "tenant_id": 123,
  "status": "error",
  "error_message": "Failed to create post on WordPress (attempt 2/2): HTTP 429",
  "attempts": 2,
  "last_http_status": 429,
  "retry_backoff_ms": 2000,
  "posts_created": 0
}
```

### Kein Secret in Logs ✅
- Keine Auth-Header oder Tokens in `raw_payload`
- Nur: HTTP Status, Error Message, Attempt Count

---

## Prompt D — Smoke Tests (Issue #17)

Erstelle **Integrations-Tests**, um Retry-Verhalten zu verifizieren.

### Test 1: Portal 503 → Make Retry → Success

**Setup**:
1. Starte Make Scenario
2. Setze Portal temporär in Maintenance Mode (simuliert 503)
3. Beobachte Make Execution Log

**Erwartet**:
- HTTP GET `/make/tenants` → 503 ❌
- Sleep 2 Sekunden ⏳
- Retry HTTP GET `/make/tenants` → 200 ✅
- Scenario läuft weiter mit Tenant-Daten
- Kein Error Callback gesendet

**Verifizierung**:
```bash
# WordPress Maintenance Mode
wp maintenance-mode activate

# Nach Test
wp maintenance-mode deactivate
```

---

### Test 2: Tenant WordPress 429 → Make Retry → Success

**Setup**:
1. Starte Make Scenario
2. Setze Tenant WordPress Rate Limit sehr streng (z.B. Wordfence 1 Request/Min)
3. Beobachte Make Execution Log

**Erwartet**:
- HTTP POST `/wp/v2/posts` → 429 ❌
- Sleep 2 Sekunden ⏳
- Retry HTTP POST `/wp/v2/posts` → 201 ✅ (Post erstellt)
- Make sendet Success Callback an Portal
- `wp_ltl_saas_runs` speichert: `status=success`, `attempts=1`

**Verifizierung**:
```sql
SELECT * FROM wp_ltl_saas_runs WHERE tenant_id = 123 ORDER BY created_at DESC LIMIT 1;
-- Sollte zeigen: status=success, posts_created=1
```

---

### Test 3: Tenant WordPress 503 → Make Retry → Fail

**Setup**:
1. Starte Make Scenario
2. Setze Tenant WordPress offline (deaktivieren)
3. Beobachte Make Execution Log

**Erwartet**:
- HTTP POST `/wp/v2/posts` → 503 ❌
- Sleep 2 Sekunden ⏳
- Retry HTTP POST `/wp/v2/posts` → 503 ❌ (still fails)
- Make sendet Error Callback an Portal
- `wp_ltl_saas_runs` speichert: `status=error`, `last_http_status=503`, `attempts=2`
- **Wichtig**: Make läuft weiter mit nächstem Tenant (nicht gebrochen)

**Verifizierung**:
```sql
SELECT * FROM wp_ltl_saas_runs WHERE status='error' ORDER BY created_at DESC LIMIT 1;
-- Sollte zeigen: status=error, last_http_status=503, error_message mit "attempt 2/2"
```

---

### Test 4: Portal 429 → Make Retry → Success (Rate Limit Recovery)

**Setup**:
1. Starte Make Scenario mit mehreren Tenants
2. Setze Portal Rate Limit temporär (z.B. 1 Request/Min)
3. Erste Ausführung sollte 429 bekommen, Retry sollte durchgehen

**Erwartet**:
- HTTP GET `/make/tenants` → 429 ❌
- Sleep 2 Sekunden ⏳
- Retry HTTP GET `/make/tenants` → 200 ✅
- Alle Tenants abgearbeitet
- Kein Error Callback

**Verifizierung**:
```sql
SELECT COUNT(*) FROM wp_ltl_saas_runs WHERE status='success';
-- Sollte alle Tenants erfolgreich zeigen (nicht weniger wegen 429)
```

---

### Test 5: Idempotent Retry (Wiederholter Run nach Fehler)

**Setup**:
1. Starte Make Scenario
2. Lasse Tenant WP für 5 Sekunden down
3. Make macht Retry, Fehler → Error Callback
4. Reaktiviere Tenant WP
5. Starte Make Scenario erneut

**Erwartet**:
- 1. Run: Fehler gespeichert (status=error)
- 2. Run: Erfolg (status=success)
- Beide Runs sind sauber dokumentiert in `wp_ltl_saas_runs`

**Verifizierung**:
```sql
SELECT id, status, attempts, last_http_status FROM wp_ltl_saas_runs
WHERE tenant_id = 123 ORDER BY created_at;
-- Zeigt: error (attempt 2), dann success (attempt 1)
```

---

## Smoke Test Checklist

Erstelle `docs/testing/smoke/issue-17.md`:

- [ ] **Test 1**: Portal 503 → Retry → Success
  - Maintenance Mode aktiviert/deaktiviert ✓
  - Make Execution Log zeigt Retry ✓
  - Kein Error Callback ✓

- [ ] **Test 2**: Tenant WP 429 → Retry → Success
  - Rate Limit Regel gesetzt ✓
  - Make Execution Log zeigt Retry ✓
  - Post erstellt ✓
  - Success Callback empfangen ✓

- [ ] **Test 3**: Tenant WP 503 → Retry → Fail
  - WordPress offline ✓
  - Make Execution Log zeigt Retry ✓
  - Error Callback mit `last_http_status=503` ✓
  - Make läuft weiter (nicht gebrochen) ✓

- [ ] **Test 4**: Portal 429 → Retry → Success
  - Rate Limit temporär ✓
  - Retry nach 2 Sekunden funktioniert ✓
  - Alle Tenants abgearbeitet ✓

- [ ] **Test 5**: Idempotent (Fehler → Erfolg in separaten Runs)
  - Unterschiedliche Runs haben unterschiedliche Ergebnisse ✓
  - Logs sauber dokumentiert ✓

---

## Commit & PR

Nach Smoke Tests:

```bash
git add -A
git commit -m "Issue #17: Retry-Strategie für 429/5xx Fehler

- Analysiert HTTP-Calls im Make Multi-Tenant Loop (Prompt A)
- Dokumentiert Make.com Error Handler Routes (Prompt B)
- Optional: Portal Logging-Felder für Retry-Info (Prompt C)
- Smoke Tests: 5 Szenarien für Retry-Verhalten (Prompt D)

Closes #17"
```

### PR Beschreibung

```markdown
## Issue #17: Retry-Strategie für 429/5xx Fehler

### Zusammenfassung
Adds resilience to Make.com scenario by implementing error handlers for HTTP 429 (Rate Limit) and 5xx (Server Errors) responses.

### Änderungen
- **Analyse** (Prompt A): Identifiziert 4 kritische HTTP-Endpoints
- **Make Strategy** (Prompt B): Error Handler Routes für 3 Endpoints
- **Portal Logging** (Prompt C): Optional: Erweiterte Log-Felder
- **Tests** (Prompt D): 5 Smoke Tests für Retry-Szenarien

### Retry-Logik
- Trigger: Status 429 oder >= 500
- Backoff: 2000 ms (2 Sekunden)
- Retries: 1x total
- Behavior: Fehler wird geloggt, Iterator läuft weiter

### Testing
Alle 5 Smoke Tests im Dokument beschrieben

### Closes
Closes #17
```

---

## Zusammenfassung (Prompts C + D)

✅ **Prompt C: Logging-Enhancement**
- Optional: Neue Felder `attempts`, `last_http_status`, `retry_backoff_ms`
- Format: Error Message mit Retry-Info
- Kein Secret in Logs

✅ **Prompt D: Smoke Tests**
- 5 Szenarien für Fehlerbehandlung
- Jedes Test-Case mit Setup, Erwartet, Verifizierung
- SQL Queries zur Daten-Kontrolle

✅ **Commit vorbereitet** mit PR-Beschreibung "Closes #17"
