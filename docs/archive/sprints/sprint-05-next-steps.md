# Sprint 05 — Audit V2 Closeout (Smoke Tests + Secret Manager + Callback Validation)

**Datum:** 2025-12-18

Du hast Sprint 2–4 umgesetzt und nach `main` gemerged. Laut Audit V2 ist alles ✅, nur:
- **Smoke Tests für #16 fehlen**
- Hardening: **Secret Handling zentralisieren**
- Hardening: **run_callback tenant_id validieren**

Dieses Sprint-Paket bringt dich von „funktioniert“ zu „verkaufbar & ruhiger Schlaf“.

---

## Modell-Empfehlung (deine verfügbaren Modelle)
- Implementierung (Code): **GPT‑4.1 (0x)**
- Docs/Copy: **GPT‑4o (0x)**
- Commit/PR Text: **GPT‑5 mini (0x)**
- Nur wenn Kontext zu groß: **Gemini 2.5 Pro (BYOK)**

---

## Reihenfolge (Abarbeiten)
1) **Prompt A** — Smoke Tests (Sprint 04) + Testing Log
2) **Prompt B** — Secrets Manager Klasse (zentralisiert Options & Tokens)
3) **Prompt C** — run_callback: tenant_id exists check (DB Validation)
4) **Prompt D** — Quick Security sweep (WP_Error checks, no secrets in logs)
5) **Prompt E** — GitHub Issue Closeout (optional, aber sauber)

---

## Definition of Done
- `docs/TESTING_LOG.md` ist ausgefüllt (Sprint 04 Limits)
- `Secrets Manager` existiert und wird überall genutzt (kein wildes get_option überall)
- `run_callback` lehnt unbekannte tenant_id ab (400/403) statt zu schreiben
- Keine Regression: make/tenants weiterhin 403 ohne Token, 200 mit Token
