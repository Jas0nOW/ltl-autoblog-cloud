# Issues & Labels Playbook — LTL AutoBlog Cloud

Dieses Dokument ist dein **Arbeits-Betriebssystem**: Wie du Issues auswählst, in kleinen Schritten umsetzt, sauber committest und zuverlässig Richtung „SaaS läuft“ kommst.

---

## Modellwahl (Copilot) — welches Modell für welche Aufgabe?

> Ziel: **stabil bauen**, ohne dass du Premium-Limits “verballerst”.

### Standard (meistens nehmen)
- **GPT‑4.1 (0x)** → Feature-Implementierung, Refactor, Debugging (WordPress/PHP/REST)
- **GPT‑4o (0x)** → UI‑Texte, kleine Anpassungen, schnelle Iterationen
- **GPT‑5 mini (0x)** → Commit/PR‑Texte, kurze Reviews, kleine Snippets

### BYOK (dein eigener API Key: Gemini)
- **Gemini 2.5 Pro** → wenn’s wirklich „heavy“ wird (große Umstrukturierung, knifflige Bugs, Architektur)
- **Gemini Flash** → schnelle Mini-Aufgaben, wenn Pro nicht nötig ist

**Faustregel:** Erst **GPT‑4.1 (0x)** probieren. BYOK nur bewusst einsetzen.

---

## Der tägliche Mini-Workflow (15 Minuten)

1) Öffne Issues und filtere: `label:mvp is:open`
2) Nimm **das kleinste** Issue, das den größten „MVP‑Blocker“ löst.
3) Arbeite in **einem Branch pro Issue** (statt alles in einem Sammel-Branch).
4) Stage bewusst → Commit Message sauber → Push.
5) PR erstellen → kurz testen → Merge.
6) Issue wird automatisch geschlossen, wenn in PR/Commit steht: `Closes #ID` oder `Fixes #ID`.

> Merksatz: **Ein Issue = ein Ergebnis.**

---

## Branch-Regel (super einfach)

**Pro Issue ein Branch**. Beispiele:
- `m2-settings-ui-9`
- `m2-wp-connect-10`
- `m3-active-users-12`
- `m3-run-callback-14`

Sammel-Branches wie `Phase1-Core` sind okay für den Start. Ab jetzt ist „pro Issue“ leichter zu debuggen und zu mergen.

---

## Commit-Regel (Learning-by-doing, ohne Stress)

**Format (Summary):**
`type(scope): kurze aussage`

**Types:** `feat`, `fix`, `docs`, `chore`, `refactor`
**Scopes:** `portal`, `make`, `billing`, `security`, `docs`, `ci`

**Auto-Close (in Description / Commit-Body oder PR Description):**
- `Closes #10`
- `Fixes #12`

Beispiel (VS Code Source Control):
- Summary: `feat(portal): add wp connect form`
- Description:
  - `Stores encrypted app password and adds REST test endpoint.`
  - `Closes #10`

> Wenn du’s vergisst: Issue notfalls manuell schließen. Beim nächsten Mal wieder richtig. Das ist normal.

---

## Prompts — wann nutzt du welchen?

Du hast aktuell 3 Prompt-Dateien im Repo. Nutze sie so:

1) **Sprint / Implementierung** (wenn du Code schreibst)
   - Datei: `docs/COPILOT_PROMPTS_SPRINT_01.md`
   - Nutzung: Issue umsetzen (mehrere Files ändern)

2) **Commit + PR Text** (wenn du fertig bist)
   - Datei: `docs/COPILOT_PROMPT_COMMIT_AND_PR.md`
   - Nutzung: Commit Summary/Description + PR Title/Description generieren (inkl. `Closes #...`)

3) **PR Maintainer Review** (bevor du mergest)
   - Datei: `docs/COPILOT_PROMPT_PR.md`
   - Nutzung: Security-Check, Test-Checklist, Risiken/Edge Cases

**Perfekte Reihenfolge:**
Sprint Prompt → Commit/PR Prompt → PR Review Prompt → Merge

---

## Labels (praktisch)

- `mvp`: Muss rein, sonst kein Launch.
- `wp-portal`: WordPress Plugin/Portal Arbeit.
- `make-engine`: Make Szenario/Engine Arbeit.
- `billing`: Pläne, Webhooks, Limits.
- `security`: Secrets, Verschlüsselung, Auth.
- `docs`: Dokumentation.
- `bug`: Fehler
- `blocked`: Hängt von anderem Issue ab
- `nice-to-have`: später

---

## Milestones (praktisch)

- 1 Milestone = 1 Phase (M0…M5)
- Jedes Issue gehört zu **genau einem** Milestone.
- Arbeite Milestone-weise von **M0 → M5**.

---

## Sprint 01 — End-to-End MVP Loop (Reihenfolge)

Ziel: Kleinster funktionierender Loop: Portal → Make → Kunden‑WP → Callback → Dashboard.

**Reihenfolge:**
1) **#10 Connect WordPress** (speichern + testen) ✅ (bei dir im Branch umgesetzt)
2) **#9 Settings UI** (RSS/Sprache/Ton/Frequenz/Publish‑Mode speichern + reload)
3) **#12 REST `active-users`** (Make zieht Konfiguration)
4) **#14 REST `run-callback`** (Make schickt Status zurück; Portal zeigt Runs)

> Wenn #10 fertig gemerged ist: als nächstes #9.

---

## Definition of Done (DoD)

Ein Issue ist „Done“, wenn:
- Code implementiert + lint/CI ok
- Kurz lokal/auf Staging getestet (2–5 Minuten)
- PR erstellt & gemerged
- Issue automatisch geschlossen (`Closes #...`) oder manuell geschlossen

---

## Pro-Tipp: Saved Searches

Lege in GitHub Saved Searches an:
- `is:issue is:open label:mvp`
- `is:issue is:open label:wp-portal`
- `is:issue is:open label:make-engine`
- `is:issue is:open label:billing`
- `is:issue is:open label:security`

---

## Sicherheits-Grundregel

- **Goldene Regel:** Nie unsanitized Blueprints oder Secrets committen.
- Nur sanitized Versionen aus `/blueprints/` gehören ins Repo.
- `blueprints_raw/` bleibt in `.gitignore`.

---

## Wenn du nicht weißt, was als nächstes kommt

1) Prüfe: „Habe ich einen offenen PR zum Mergen?“ → zuerst mergen.
2) Nimm das nächste `mvp` Issue im aktuellen Milestone.
3) Sprint Prompt → Commit/PR → Review → Merge.