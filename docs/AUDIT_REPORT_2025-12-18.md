# Audit Report - 2025-12-18

## Lead Engineer Audit des `Phase1-Core` Branch

### 1. Code-Analyse

Der Code im `wp-portal-plugin`, die `docs` und `scripts` wurden analysiert. Die Kern-Features für Phase 1 sind implementiert. Die Code-Struktur ist logisch (`class-admin.php`, `class-rest.php`, `class-ltl-saas-portal.php`, `class-ltl-saas-portal-crypto.php`), was die Wartung erleichtert.

### 2. Issue Status-Board

| Issue | Status | Relevante Dateien | Fehlende Acceptance Checks |
| :--- | :---: | :--- | :--- |
| **#9 Settings-UI** | ✅ | `includes/class-ltl-saas-portal.php` (Dashboard), `includes/Admin/class-admin.php` (Make Token) | Keine. UI für User-Settings und Admin-Token ist vorhanden. |
| **#10 Connect WordPress (encrypted)**| ✅ | `includes/class-ltl-saas-portal.php`, `includes/class-ltl-saas-portal-crypto.php` | Keine. Speicherung mit v1-HMAC-Verschlüsselung funktioniert. |
| **#11 Access Control** | ✅ | `includes/class-ltl-saas-portal.php` (Dashboard Lock), `includes/REST/class-rest.php` (REST 403) | Keine. Lock-Screen und REST-Block für inaktive User sind implementiert. |
| **#12 active-users Endpoint** | ✅ | `includes/REST/class-rest.php` | Keine. Endpoint existiert, `wp_app_password` wird maskiert (`***`). |
| **#13 Make Multi‑Tenant refactor** | ✅ | `includes/REST/class-rest.php` | Keine. Der `/make/tenants`-Endpoint liefert alle nötigen Daten. |
| **#14 Run callback Endpoint** | ✅ | `includes/REST/class-rest.php` | Keine. Der `/run-callback`-Endpoint speichert Run-Daten in die DB. |
| **#15 Runs Tabelle + Dashboard** | ✅ | `includes/class-ltl-saas-portal.php` | Die Tabelle könnte UX-Verbesserungen vertragen (z.B. Paginierung), ist aber funktional. |
| **#16 Posts/Monat Limits** | ❌ | *(Keine)* | Das Feature ist nicht implementiert. Es gibt keine Logik, die Post-Anzahl prüft oder limitiert. |

### 3. Top 10 „Breakpoints“ (Potenzielle Schwachstellen)

1.  **Secret Handling (`/make/tenants`):** Der Endpoint MUSS SSL erzwingen. Aktuell ist der Check `is_ssl()` vorhanden, aber falls die Server-Konfiguration (Proxy) fehlerhaft ist, könnte er umgangen werden.
2.  **dbDelta Fragilität (`activate()`):** `dbDelta` ist empfindlich bei Leerzeichen und Key-Definitionen. Zukünftige Schema-Änderungen müssen exakt formuliert sein, sonst schlagen sie fehl.
3.  **Admin-seitige Validierung (`class-admin.php`):** Der `sanitize_token`-Callback ist eine gute Basis, könnte aber strenger sein (z.B. exakte Längenprüfung).
4.  **Replay-Angriffe (REST):** Die Endpoints nutzen API-Keys/Tokens, aber keine Nonces für Service-zu-Service-Kommunikation. Ein abgefangener `run-callback`-Request könnte erneut gesendet werden.
5.  **Fehlende Fehler-Benachrichtigung:** Wenn ein `run-callback` fehlschlägt (z.B. DB-Fehler), wird dies nur geloggt. Es gibt keine automatische Benachrichtigung an den Admin.
6.  **Input-Sanitization (Dashboard):** Die Sanitization in `shortcode_dashboard()` ist gut (`esc_url_raw`, `sanitize_user`). Bei zukünftigen, komplexeren Feldern (z.B. JSON-Settings) muss diese erweitert werden.
7.  **Legacy Crypto-Pfad (`class-ltl-saas-portal-crypto.php`):** Der Fallback für alte Daten ist wichtig, stellt aber eine potenzielle Angriffsfläche dar, wenn ein Fehler im alten Decrypt-Code gefunden wird.
8.  **Fehlende Transaktionalität:** Beim Speichern von Settings und Connections wird kein "alles oder nichts"-Prinzip verfolgt. Ein Fehler nach der Hälfte der Operationen hinterlässt inkonsistente Daten.
9.  **Denial-of-Service (`/run-callback`):** Der Endpoint hat kein Rate-Limiting. Ein Angreifer könnte die `ltl_saas_runs`-Tabelle mit tausenden Einträgen fluten.
10. **Capability-Checks im Shortcode:** Der Shortcode prüft nur auf `is_user_logged_in()`. Für differenziertere Zugriffslevel (z.B. basierend auf der WordPress-Rolle) fehlen weitere Checks.

### 4. Nächste 3 Schritte (60–90 Min. pro Schritt)

1.  **Refactor & Harden User State:** Zentralisiere die `is_active`-Prüfung. Erstelle eine Helper-Funktion `ltl_saas_is_user_active($user_id)` in `class-ltl-saas-portal.php`, die den DB-Status prüft. Ersetze die doppelten DB-Abfragen in den REST-Endpoints und im Dashboard-Shortcode durch Aufrufe dieser Funktion. Das reduziert Redundanz und vereinfacht zukünftige Änderungen (z.B. Plan-Status-Prüfung).
2.  **Implementierung Posts/Monat Limit (MVP für #16):**
    *   Erweitere die `ltl_saas_settings`-Tabelle (via `dbDelta`) um die Spalten `posts_this_month` (INT, default 0) und `limit_last_reset` (DATETIME).
    *   Im `/make/tenants`-Endpoint, füge eine Prüfung hinzu: Wenn `limit_last_reset` älter als 1 Monat ist, setze `posts_this_month` auf 0 zurück.
    *   Wenn `posts_this_month` >= Plan-Limit, setze `is_active` im *zurückgegebenen* Tenant-Objekt auf `false` (damit Make den Run für diesen User überspringt).
    *   Im `/run-callback`-Endpoint: Inkrementiere `posts_this_month` für den jeweiligen Tenant.
3.  **Manueller End-to-End-Test & Smoke-Test-Protokoll:** Führe die `SMOKE_TEST_CHECKLIST.md` manuell durch. Erstelle eine neue Datei `docs/TESTING_LOG.md` und protokolliere für jeden Punkt der Checkliste den Testfall und das Ergebnis (z.B. "Inaktiver User versucht, Settings zu speichern -> Erwartet: Fehlermeldung, Erhalten: Fehlermeldung 'Account inaktiv.' -> OK"). Das ist entscheidend, bevor der Branch gemerged wird.
