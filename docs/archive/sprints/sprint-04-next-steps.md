# Sprint 04 — Posts/Monat Limits (Issue #16) + Hardening

**Datum:** 2025-12-18

## Ziel (Definition of Done)
- Issue **#16 "Posts/Monat Limits enforce"** ist implementiert: Make überspringt Tenants, die ihr Monatslimit erreicht haben, und es wird sauber geloggt.
- Monats-Reset funktioniert (ohne Cron: beim ersten Run im neuen Monat).
- Keine Regression: existing Endpoints laufen weiter.

---

## Copilot Modell-Empfehlung (deine verfügbaren Modelle)
- Code / Implementierung: **GPT‑4.1 (0x)**
- Docs & Texte: **GPT‑4o (0x)**
- Commit/PR Text: **GPT‑5 mini (0x)**
- Wenn Copilot Kontext verliert / Halluziniert: **Gemini 2.5 Pro (BYOK)**

---

## Branch-Strategie (simpel)
- Branch: `feat/m4-post-limits`
- Nach Sprint: PR → merge nach `main`

---

## Reihenfolge (Abarbeiten)
1) Prompt A — Helper: `ltl_saas_get_tenant_state()` (reduziert doppelte Logik)
2) Prompt B — DB: Felder für Limit Tracking (settings table)
3) Prompt C — Logic: Reset + enforce im `/make/tenants`
4) Prompt D — Logic: Inkrement im `/run-callback`
5) Prompt E — Docs Update (API + Make Contract + Limits)
6) Prompt F — Smoke Test + Commit + PR

---

## Notiz zu Issues
Deine Issues #9–#15 stehen auf GitHub noch als „Open“, obwohl im Code erledigt.  
Das ist normal, wenn man keine Closing Keywords in PR/Commit verwendet hat. Du kannst die erledigten Issues einfach manuell schließen oder später in einem PR-Text „Closes #..“ verwenden.
