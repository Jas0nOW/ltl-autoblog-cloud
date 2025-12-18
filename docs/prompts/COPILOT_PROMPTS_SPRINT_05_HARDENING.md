# COPILOT_PROMPTS_SPRINT_05_HARDENING.md

## Prompt A — Smoke Tests Sprint 04 + Testing Log
**Empfohlenes Modell:** GPT‑4.1 (0x)

Ich habe Issue #16 (Posts/Monat Limits) umgesetzt. Bitte:

1) Öffne `SMOKE_TEST_SPRINT_04.md` (oder die entsprechende Checkliste im Repo)
2) Gib mir eine **kurze** Anleitung, wie ich das lokal in WordPress in 10 Minuten teste:
   - welche Werte ich in DB/Settings setzen muss
   - welche Endpoints ich aufrufen soll (curl Beispiele)
3) Erstelle `docs/TESTING_LOG.md` basierend auf `TESTING_LOG_TEMPLATE.md` und trage Platzhalter ein (___), damit ich nur noch ausfüllen muss.

Wichtig: keine neuen Features, nur Test-Flow und Log-Datei.

---

## Prompt B — Secrets Manager Klasse
**Empfohlenes Modell:** GPT‑4.1 (0x)

Erstelle eine neue Klasse z.B. `includes/class-ltl-saas-portal-secrets.php` (Name gerne konsistent zum Projekt):

Ziele:
1) Zentralisiere Secret-Reads/Writes:
   - `ltl_saas_make_token`
   - `ltl_saas_api_key`
2) Public Methods:
   - `get_make_token(): string`
   - `set_make_token(string $token): void`
   - `get_api_key(): string`
   - `set_api_key(string $key): void`
   - `has_make_token(): bool` (token nicht leer)
3) Sicherheitsregeln:
   - niemals Secrets loggen
   - sanitize/trim beim set
4) Refactor:
   - Ersetze direkte `get_option('ltl_saas_make_token')` / `get_option('ltl_saas_api_key')`
     in REST/Admin/Portal Code durch den Secrets Manager.

Akzeptanz:
- Keine Funktionsänderung außer Centralization.
- Unit tests nicht nötig, aber kurze manuelle Testliste.

---

## Prompt C — run_callback tenant_id Validierung
**Empfohlenes Modell:** GPT‑4.1 (0x)

Im Endpoint `LTL_SAAS_Portal_REST::run_callback`:

1) Lies `tenant_id` aus Payload.
2) Prüfe, ob tenant_id existiert:
   - Lookup gegen die passende Verbindungstabelle (z.B. `ltl_saas_connections`) oder Settings-Tabelle
   - Wenn nicht gefunden: `return new WP_REST_Response([ 'ok'=>false, 'error'=>'unknown_tenant' ], 400);`
3) Erst wenn gültig: Run speichern / counters increment.
4) Achte darauf:
   - `is_wp_error` checks bei Crypto calls
   - sanitize/absint tenant_id

Akzeptanz:
- Unbekannte tenant_id erzeugt **keinen** DB write.
- Bekannte tenant_id verhält sich wie vorher.

---

## Prompt D — Quick Security Sweep (nur Bugfixes)
**Empfohlenes Modell:** GPT‑4.1 (0x)

Scanne den Code nach:
- fehlenden `is_wp_error()` checks nach decrypt/crypto calls
- Stellen, wo Secrets in error messages geraten könnten
- REST: permission_callback überall korrekt?
- sanitize/escapes für neue Felder

Mache nur kleine Fixes, keine neuen Features.

---

## Prompt E — Issue Closeout (GitHub Hygiene)
**Empfohlenes Modell:** GPT‑5 mini (0x)

Erstelle einen PR Text (oder Issue Kommentar), um die erledigten Issues sauber zu schließen:
- Wenn bereits in `main`: kurze Liste, welche Issues jetzt manuell geschlossen werden können.
- Erklärung: Auto-close klappt nur beim Merge in Default Branch mit Closing Keywords.

Gib mir dazu genau 5 Bulletpoints, die ich in GitHub posten kann.
