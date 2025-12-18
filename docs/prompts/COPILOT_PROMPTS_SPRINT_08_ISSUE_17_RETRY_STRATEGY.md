# Sprint — Issue #17: Basic Retry Strategie (429/5xx)

**Issue Link:** https://github.com/Jas0nOW/ltl-autoblog-cloud/issues/17
**Status:** nice-to-have (nicht MVP), aber senkt “random fails” deutlich.

## Ziel (DoD)
- Bei 429 oder 5xx wird **genau 1 Retry** gemacht (mit Backoff).
- Nach Retry wird **sauber geloggt** (inkl. attempt count und final status).
- Dokumentiert in `docs/engineering/make/retry-strategy.md` + Eintrag in `docs/testing/logs/testing-log.md`.

## Empfohlenes Modell
- **Docs + technische Steps:** GPT‑4o (0x)
- **Wenn du eine Code-Wrapper-Logik im Portal ergänzt:** GPT‑4.1 (0x)

---

## Prompt A — Analyse: Wo passieren 429/5xx?
**Modell:** GPT‑4o (0x)

Scanne Repo (docs + wp-portal-plugin + Make blueprint samples) und finde:
1) Welche HTTP Calls im Make Multi‑Tenant Loop passieren (Portal endpoints / WordPress REST / externe RSS).
2) Wo aktuell Fehler behandelt werden (Error handlers? try/catch? response-code checks?).
3) Wo Logs/Runs geschrieben werden (welche DB table / welche Funktion).

Output: Liste der “retry candidates” + genau *welche Module/Files* betroffen sind.

---

## Prompt B — Make.com Retry Route (Manual Steps + optional Blueprint update)
**Modell:** GPT‑4o (0x)

Erstelle `docs/engineering/make/retry-strategy.md` als Step-by-step Anleitung (Make UI), inkl:
- Für jeden HTTP Request im Tenant Loop: Error handler Route hinzufügen
- Filter: **status == 429 OR status >= 500**
- Backoff: **Sleep 2–5s** (oder 2s, dann 4s; aber nur 1 Retry insgesamt)
- Retry: HTTP Request **1x erneut**
- Wenn erneut fail: markiere tenant-run als failed und schreibe Log (oder Portal callback / runs table)
- WICHTIG: Nach fail **weiter mit nächstem Tenant**, nicht Scenario crashen

Optional:
- Falls im Repo eine “sanitized blueprint” JSON liegt, update sie nur dokumentarisch (Kommentar/README), nicht riskant „komplett neu bauen“.

---

## Prompt C — Logging nach Retry (Portal-Seite, falls sinnvoll)
**Modell:** GPT‑4.1 (0x)

Wenn im Portal bereits Runs/Logs existieren:
- Ergänze Log-Feld(e): `attempts`, `last_http_status`, `retry_backoff_ms` (nur wenn leicht).
- Stelle sicher: bei Retry-Fail wird ein Run-Eintrag geschrieben, damit Dashboard “warum fail” zeigt.

Akzeptanz:
- Kein Secret im Log
- Log-Eintrag ist für Support/Debug brauchbar

---

## Prompt D — Smoke Tests + Closeout
**Modell:** GPT‑5 mini (0x)

1) Erstelle `docs/testing/smoke/issue-17.md`:
- Simuliere 429/5xx (z.B. temporär Endpoint 503, oder rate limit)
- Beobachte: 1 Retry passiert, dann Erfolg oder sauberer Fail-Log
2) PR Text: `Closes #17`

