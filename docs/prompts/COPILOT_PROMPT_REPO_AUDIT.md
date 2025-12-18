# COPILOT_PROMPT_REPO_AUDIT.md

**Empfohlenes Modell:** Gemini 2.5 Pro (BYOK) — weil du dafür sehr viel Kontext hast.

---

Du bist mein Lead-Engineer. Ich arbeite in einem Repo, das ein WordPress Portal Plugin + Make Multi‑Tenant SaaS Workflow baut.

**Branch:** Phase1-Core
**Ziel:** Sag mir objektiv, was fertig ist, was fehlt, und was als nächstes die beste kleine Einheit ist.

## Aufgabe

1) Lies den Code im Workspace komplett (Portal Plugin, docs, scripts).
2) Erstelle ein Status‑Board für diese Issues:
   - #9 Settings-UI
   - #10 Connect WordPress (encrypted)
   - #11 Access Control
   - #12 active-users Endpoint
   - #13 Make Multi‑Tenant refactor
   - #14 Run callback Endpoint
   - #15 Runs Tabelle + Dashboard Ansicht
   - #16 Posts/Monat Limits enforce (falls schon begonnen)

3) Für jedes Issue:
   - Status: ✅ / 🟡 / ❌
   - Welche Dateien sind relevant (konkrete Pfade)
   - Welche Acceptance Checks fehlen noch (konkret)

4) Finde die Top 10 „Breakpoints“ (Stellen, wo Bugs/Security-Probleme wahrscheinlich sind):
   - Auth / Capability checks
   - SQL / dbDelta
   - Secret handling (encryption/decryption, logging)
   - REST validation/sanitization
   - Nonce/CSRF (UI)

5) Gib mir **nur** die nächsten 3 Schritte, die jeweils in 60–90 Minuten fertig werden können.

6) packe den Output in einen neuen `docs/audits/<yyyy-mm-dd>-audit-vX.md`.

Wichtig: Ich will konkrete Antworten (Dateinamen, Funktionen, TODOs), kein allgemeines Gerede.
