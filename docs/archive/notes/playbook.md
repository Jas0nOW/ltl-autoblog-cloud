# LTL AutoBlog Cloud — PLAYBOOK (Arbeitsmodus)

Dieses Dokument ist dein „Gehirn auf Papier“: **Was bauen wir, wie arbeiten wir, welches Modell nehmen wir, und wie halten wir das Projekt sauber.**

> Repo: `Jas0nOW/ltl-autoblog-cloud`  
> Working Branch (dein aktueller Workflow): `Phase1-Core`  
> Stable Branch: `main`

---

## 0) Was wir bauen (in 1 Satz)

Ein **WordPress-Portal-Plugin**, in dem Kunden **Account + Settings** pflegen (RSS, Sprache, Ton, WP-Connect, Plan), während ein **Make Multi‑Tenant Scenario** diese Konfiguration zieht und automatisch Blogposts veröffentlicht.

---

## 1) Modellwahl in Copilot (mit deinem aktuellen Kontingent)

Du hast aktuell diese Modelle:

**0x (kostenlos in Copilot bei dir):**
- GPT‑4.1
- GPT‑4o
- GPT‑5 mini
- Grok Code Fast 1
- Raptor mini (Preview)

**BYOK (dein eigener API-Key, kostet dich je nach Gemini-Tarif):**
- Gemini 2.5 Flash Preview
- Gemini 2.5 Pro

### Meine Default-Empfehlung (für 90% der Arbeit)
- **GPT‑4.1 (0x)** → „Arbeitstier“ für Feature‑Implementierung, Refactor, Debugging (PHP/WP/REST).
- **GPT‑4o (0x)** → UI‑Texte, kleine Änderungen, schnelle Iterationen/Copy.
- **GPT‑5 mini (0x)** → Commit‑Messages, PR‑Texte, kurze Reviews, „schreibe es sauber“.

### Wann du dein Gemini (BYOK) nutzen solltest
- **Gemini 2.5 Pro** → Wenn Copilot *viele Dateien auf einmal* verstehen muss (großer Refactor, Architektur‑Audit, komplexe Bug‑Jagd).
- **Gemini 2.5 Flash** → Wenn du *sehr schnell* durchscannen willst (z.B. „find all TODOs / insecure patterns“), aber mit weniger Tiefe.

**Merksatz:** Erst **0x** versuchen → wenn Kontext/Komplexität zu groß wird → **Gemini Pro**.

---

## 2) Dein Workflow (so wie du’s jetzt machst)

Du arbeitest **direkt auf `Phase1-Core`**, machst **Issue für Issue**, und nach jedem Issue:

**Stage → Commit → Push**.

Am Ende:
**eine finale PR** `Phase1-Core → main` mit sauberer Zusammenfassung + `Closes #…`.

**Safety-Net:**
- `main` bleibt „Production“.
- Nur `main` wird deployed/ausgeliefert.
- Vor dem Final-Merge: Smoke‑Test (siehe Abschnitt 7).

---

## 3) Die 60‑Sekunden Session-Routine (damit du Issues wirklich nutzt)

**Jede Session beginnt so:**
1) GitHub → **Issues** öffnen
2) Filter: `is:open label:mvp` (oder Milestone)
3) **1 Issue wählen** (nur eins!)
4) In VS Code: *kurz* in den Code schauen (wo ist der Einstieg?)
5) Copilot Prompt aus Sprint-Datei ausführen

**Jede Session endet so:**
1) `git status` muss clean sein
2) Commit Message nach Template (Abschnitt 5)
3) Push
4) Kurz in der Issue kommentieren: „Was ist fertig / was fehlt noch“

---

## 4) Wann nutze ich welche Prompt-Datei?

Du hast u.a. diese Prompts:

- `COPILOT_PROMPTS_SPRINT_XX_TEMPLATE.md`  
  → Vorlage für neue Sprints (A/B/C/D …). Kopieren → Sprint anpassen.

- `COPILOT_PROMPT_PR.md`  
  → Wenn du **eine PR erstellen willst** (z.B. Final PR `Phase1-Core → main`).

- `COPILOT_PROMPT_COMMIT_AND_PR.md`  
  → Wenn du **nach einer Arbeitseinheit** schnell und sauber committen willst:
  - Es kann dir eine **Conventional Commit** Message bauen
  - und optional die PR‑Description vorbereiten
  - In deinem aktuellen Workflow: nutz es primär als **Commit‑Generator** (PR erst am Ende)

---

## 5) Commit Messages (Learning by Doing — aber nicht chaotisch)

Wir nehmen **Conventional Commits**:

Format:
`<type>(scope): <kurze Aussage>`

**Types:**
- `feat` neue Funktion
- `fix` Bugfix
- `refactor` Umbau ohne Feature
- `docs` Dokumentation
- `chore` Tools/Config
- `test` Tests

**Beispiele:**
- `feat(wp-connect): store app password encrypted + add test endpoint`
- `feat(settings): add RSS/language/tone UI with validation`
- `fix(rest): require auth header for make endpoints`
- `docs(make): add multi-tenant scenario wiring guide`

**Regel:** 1 Issue = 1–3 Commits max.  
Wenn du merkst, du hast 12 Commits für ein Issue → du hast eigentlich 3 Issues gebaut. 😄

---

## 6) Issue Hygiene (wie du sauber trackst, ohne Overhead)

- Issue bleibt offen, bis es in `main` gemerged ist.
- Auf `Phase1-Core` kannst du in der Issue kommentieren: „Done in Phase1-Core, waiting for final PR“.
- Final PR schließt mehrere Issues mit:
  - `Closes #9`
  - `Closes #10`
  - …

---

## 7) Smoke Test Checklist (vor dem Final Merge)

In deiner lokalen WP‑Testumgebung:

1) Plugin aktivieren ohne Fatal Errors
2) `[ltl_saas_dashboard]` Seite lädt
3) REST `GET /wp-json/ltl-saas/v1/health` → 200
4) Settings speichern → Reload → Werte bleiben
5) WP Connect Test → Erfolg / saubere Fehlermeldung
6) Make Pull Endpoint (wenn vorhanden) → nur mit Token erreichbar
7) Run Callback (wenn vorhanden) → schreibt Run‑Eintrag / zeigt „letzter Run“

---

## 8) Wenn du nicht weißt, was als nächstes kommt

1) Hast du uncommitted Änderungen? → commit/push (kleines Stück)
2) Nimm das **nächste `mvp` Issue** im aktuellen Milestone
3) Sprint Prompt → commit/push
4) Wiederholen

Ende.
