# COPILOT_PROMPTS_SPRINT_06_LAUNCH_READY.md

## Prompt A — Smoke Tests #16 + Testing Log
**Empfohlenes Modell:** GPT‑4.1 (0x)

Ich will Issue #16 (Posts/Monat Limits) offiziell abschließen.

1) Erstelle/aktualisiere `docs/SMOKE_TEST_SPRINT_04.md` (falls sie fehlt) mit den 7 Checks:
   - make/tenants skip/remaining
   - month rollover reset
   - run-callback increment
   - Regression: active-users maskiert password, make/tenants 403 ohne Token
2) Erstelle `docs/TESTING_LOG.md` (oder aktualisiere sie), basierend auf einer Template-Struktur,
   damit ich nur noch Ergebnisse eintragen muss.
3) Gib mir 5 konkrete curl/HTTP Beispiele, die ich 1:1 ausführen kann.

Keine neuen Features — nur Testbarkeit + Logging.

---

## Prompt B — Admin UI: API Key maskiert + Regenerate Button
**Empfohlenes Modell:** GPT‑4.1 (0x)

Der `ltl_saas_api_key` soll NICHT mehr nur über DB/CLI verwaltbar sein.

Bitte erweitere die bestehende Admin-Seite:

- Feld: „API Key (Portal → Make)“
- Anzeige: maskiert (`••••••ABCD` letzte 4 Zeichen), niemals Klartext
- Button: „Generate new API key“ (nonce protected)
- Speicherung: über Secrets Manager / zentrale Klasse (keine direkten get_option überall)

Akzeptanz:
- Keine Secrets in Logs
- Nonce check vorhanden
- sanitize/trim beim Set
- Keine Regression in REST (active-users bleibt geschützt)

---

## Prompt C — Proxy/SSL Setup: Doc + wp-config snippet (optional)
**Empfohlenes Modell:** GPT‑4o (0x)

Erstelle `docs/proxy-ssl.md`:

- Warum `is_ssl()` hinter Reverse Proxy falsch sein kann
- Wie man in `wp-config.php` `$_SERVER['HTTPS']='on'` setzt, wenn `HTTP_X_FORWARDED_PROTO` `https` enthält
- Security Note: nur wenn Proxy Header trusted sind

Kein Code im Plugin ändern. Nur Doku.

---

## Prompt D — GitHub Closeout (Issues sauber schließen)
**Empfohlenes Modell:** GPT‑5 mini (0x)

Gib mir 2 Texte:

1) Einen Issue-Kommentar (max 5 Bullets), den ich in #16 posten kann: „done in main, tests logged, next: launch“
2) Eine Checkliste, wie ich #9–#16 schnell schließe (manuell), inkl. Hinweis warum Auto-close manchmal nicht greift.

---

## Prompt E — Release Pack bauen (ZIP + SHA + Version)
**Empfohlenes Modell:** GPT‑4.1 (0x) für Build, GPT‑5 mini (0x) für Release Notes

Erstelle im Repo:
- `scripts/build-zip.ps1` (Windows) ODER erweitere vorhandenes Script
- Output:
  - `dist/ltl-autoblog-cloud-<version>.zip`
  - `dist/SHA256SUMS.txt`
- Version aus Plugin-Header ziehen (oder package.json falls vorhanden)
- Excludes: `.git`, `.github`, `node_modules`, `vendor`, `.env`, `dist`, `blueprints_raw`

Akzeptanz:
- Script läuft auf Windows PowerShell
- ZIP enthält nur Plugin-Dateien
- SHA Datei wird geschrieben
