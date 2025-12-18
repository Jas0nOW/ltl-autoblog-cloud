# Sprint 03 — Security + Access + Make Contract (Abarbeiten)

**Datum:** 2025-12-18

Du musst mit dem Audit-Output nichts “manuell verarbeiten”.  
Du gibst ihn mir (wie jetzt), und wir entscheiden gemeinsam weiter. ✅

Aber: Damit nichts verloren geht, **speicherst du den Audit** einmal im Repo:
- `docs/audit/AUDIT_REPORT_2025-12-18.md` (einfach reinkopieren)  
- Commit: `docs: add audit report 2025-12-18`

---

## Ziel dieses Sprints
1) Credentials sind **sicherer** gespeichert (Integrität via HMAC)
2) REST liefert Secrets **nur** an Make (Token geschützt), nicht an “normale” Endpoints
3) **Access Control (#11)**: inaktiv = Lock-Screen + REST 403
4) Docs: 1 Seite API/Make Contract

---

## Reihenfolge (einfach stumpf)
1) Prompt A (Crypto HMAC + backwards compat)
2) Prompt B (REST split: safe vs make)
3) Prompt C (Make Token Admin Setting)
4) Prompt D (Access Control MVP)
5) Prompt E (Docs: api + make contract)
6) Prompt F (Smoke Test + Commit)

---

## Copilot Modell-Empfehlung
- Implementierung (A–D): **GPT‑4.1 (0x)**
- Docs (E): **GPT‑4o (0x)**
- Commit/PR Text: **GPT‑5 mini (0x)**
- Nur falls Copilot “den Kontext verliert”: **Gemini 2.5 Pro (BYOK)**

