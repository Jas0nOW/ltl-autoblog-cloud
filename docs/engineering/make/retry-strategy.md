# Make.com Retry-Strategie für 429/5xx Fehler (Issue #17 Prompt B)

> **Ziel**: Bei HTTP-Fehlern 429 oder 5xx genau 1 Retry mit Backoff durchführen.

---

## Überblick: Error Handler Routes

Im Make.com Scenario müssen **3 kritische HTTP Requests** Error Handler bekommen:

1. **GET `/make/tenants`** (Portal abrufen)
2. **POST `/wp/v2/posts`** (Post erstellen)
3. **GET RSS Feed** (optional, Backoff)

Jeder bekommt die gleiche Struktur:
```
[HTTP Request]
    ↓
[If error: 429 OR >= 500]
    ↓ [Yes]
[Sleep 2-5 seconds]
    ↓
[Retry HTTP Request 1x]
    ↓
[If still error: Log + Mark tenant as failed]
    ↓ [Continue with next tenant]
```

---

## Schritt-für-Schritt: Error Handler Route hinzufügen

### Für jeden HTTP Request:

#### Step 1: HTTP Request Module erstellen
```
Name: "[Step Name] HTTP Request"
(Z.B. "GET Active Tenants HTTP Request")
Konfiguration: URL, Methode, Auth, Body
```

#### Step 2: Error Handler hinzufügen
```
Rechtsklick auf HTTP Module → "Add an error handler"
Name: "[Step Name] Error Handler"
```

#### Step 3: Error Filter setzen
```
Condition 1: HTTPStatus == 429 (Too Many Requests)
OR
Condition 2: HTTPStatus >= 500 (Server Errors: 500, 502, 503, 504)
```

#### Step 4: Backoff einfügen
```
Module: Sleep
Duration:
  - First attempt: 2000 ms (2 Sekunden)
  - Optional second: 4000 ms (4 Sekunden, aber nur 1 Retry total)
```

#### Step 5: Retry durchführen
```
Module: HTTP Request (1x, gleiche Konfiguration wie Step 1)
OR Use: "Repeat Module" (wenn Make unterstützt)
```

#### Step 6: Abbruch bei Fehler
```
Condition: If response status is still 429 or >= 500
Action:
  - Continue Iterator (nicht Scenario brechen)
  - Optional: Set flag "skip_this_tenant" = true
```

---

## Konkrete Implementierung (3 Error Handler)

### A) Portal GET `/make/tenants` Error Handler

**Module 1: HTTP GET Request**
```
Label: "Fetch Active Tenants"
Method: GET
URL: https://{{portal_domain}}/wp-json/ltl-saas/v1/make/tenants
Headers:
  - X-LTL-SAAS-TOKEN: {{make_token}}
```

**Error Handler Route**:
```
Trigger: On Error
Filter: Response Status == 429 OR >= 500

Module 2: Sleep
Duration: 2000 ms

Module 3: HTTP GET Request (identical to Module 1)
Label: "Fetch Active Tenants (Retry)"

Module 4: Router
Condition 1: If response.status == 429 OR >= 500
  → Module 5: Callback ("Tenant fetch failed, skip tenant")
Condition 2: Else (Success)
  → Continue to Iterator
```

**Callback bei Fehler**:
```
POST /wp-json/ltl-saas/v1/run-callback
{
  "tenant_id": "unknown",
  "status": "error",
  "error_message": "Failed to fetch active tenants (retry 1/1): HTTP {{response.status}}",
  "last_http_status": {{response.status}}
}
```

---

### B) Tenant WordPress POST `/wp/v2/posts` Error Handler

**Module: HTTP POST Request**
```
Label: "Create Post on Tenant WP"
Method: POST
URL: {{tenant.site_url}}/wp-json/wp/v2/posts
Auth: Basic ({{tenant.wp_username}} : {{tenant.wp_app_password}})
Body:
{
  "title": "{{post_title}}",
  "content": "{{post_content}}",
  "status": "{{publish_mode}}", // "draft" or "publish"
  "categories": []
}
```

**Error Handler Route**:
```
Trigger: On Error
Filter: Response Status == 429 OR >= 500

Module: Sleep
Duration: 2000 ms

Module: HTTP POST Request (Retry, identical)
Label: "Create Post on Tenant WP (Retry)"

Module: Router
Condition 1: If response.status == 429 OR >= 500
  → Module: Callback (Failed)
Condition 2: If response.status == 200/201 (Success)
  → Module: Callback (Success)
```

**Callback bei Fehler**:
```
POST /wp-json/ltl-saas/v1/run-callback
{
  "tenant_id": {{tenant_id}},
  "status": "error",
  "error_message": "Failed to create post on WordPress (retry 1/1): HTTP {{response.status}}",
  "last_http_status": {{response.status}},
  "posts_created": 0
}
```

---

### C) RSS Feed GET (Optional)

**Module: HTTP GET Request**
```
Label: "Fetch RSS Feed"
Method: GET
URL: {{tenant.rss_url}}
Timeout: 10 seconds
```

**Error Handler** (Optional, nur wenn kritisch):
```
Trigger: On Error
Filter: Response Status == 429 OR >= 500

Module: Sleep
Duration: 2000 ms

Module: HTTP GET Request (Retry)
Label: "Fetch RSS Feed (Retry)"

Module: Router
Condition 1: If still error
  → Skip this tenant (use placeholder content or error callback)
Condition 2: Else
  → Continue
```

---

## Best Practices für Error Handler

### 1) Backoff-Strategie
- **First attempt**: 2000 ms (2 Sekunden)
- **Total**: 1 Retry only (nicht mehr)
- **Grund**: Zu viele Retries verlangsamen Scenario, 1 ist meist ausreichend

### 2) Logging
- Schreibe **immer** einen Callback bei Fehler
- Inkludiere: `last_http_status`, `error_message`, `attempts`
- **Portal speichert** Log in `wp_ltl_saas_runs` für Dashboard

### 3) Iterator weiterführen
- **Wichtig**: Nach fehlgeschlagenem Retry **weitermachen** mit nächstem Tenant
- **Nicht**: Ganzes Scenario beenden
- Nutze: `Continue` in Router, nicht `Break`

### 4) Error Handler-Struktur
```
[HTTP Request]
    ↓
[Catch Error if 429 or 5xx]
    ↓
[Sleep 2 Seconds]
    ↓
[Retry 1x]
    ↓
[If still error → Log + Continue]
    ↓
[Else → Success]
```

---

## Testing Error Handler lokal

### Mock 429 Response (Wordfence Rate Limit)
```bash
# Auf Tenant WP: Setze temporär Rate Limit sehr streng
# Oder nutze Firewall Plugin zum Blockieren
```

### Mock 503 Response
```bash
# Setze WordPress in Maintenance Mode
# Oder deaktiviere temporär Tenant WP
```

### Beobachte in Make
- Make Execution Log anschauen
- Sollte sehen:
  1. HTTP Request → 429/503 ❌
  2. Sleep 2s ⏳
  3. Retry HTTP Request → 200 ✅
  4. Continue to next tenant

---

## Blueprints updaten (Optional)

Falls Blueprints in `/blueprints/sanitized/de/` und `/blueprints/sanitized/us/` existieren:

**Nicht riskant neu bauen**, aber dokumentieren:
1. Öffne Blueprint JSON
2. Suche nach HTTP Request Modules
3. Füge Kommentar ein:
   ```json
   {
     "id": "http_fetch_tenants",
     "label": "Fetch Active Tenants",
     "type": "http",
     "note": "ISSUE #17: Add error handler for 429/5xx (Prompt B)",
     "config": { ... }
   }
   ```

Oder: Schreibe neue sanitized Blueprints als separate Commit nach dieser Implementation.

---

## Zusammenfassung (Prompt B Output)

✅ **3 Error Handler Routes definiert**:
1. GET `/make/tenants` — Portal abrufen
2. POST `/wp/v2/posts` — Post erstellen
3. GET RSS (optional) — Feed abrufen

✅ **Retry-Logik**:
- Filter: `429 OR >= 500`
- Backoff: 2000 ms
- Retries: 1x total
- Logging: Callback mit `last_http_status`

✅ **Dokumentiert in Step-by-Step Format**:
- Error Handler hinzufügen (Rechtsklick)
- Module konfigurieren
- Router für Erfolg/Fehler
- Callback zum Portal

✅ **Next Steps**:
- Prompt C: Portal-seitige Logging-Erweiterung
- Prompt D: Smoke tests
