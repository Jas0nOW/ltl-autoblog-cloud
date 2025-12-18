# Copilot-Agent-Loop — Controller Prompt (V2)

Ziel: Ein deterministischer Loop **Planner → Executor → Auditor** für `ltl-autoblog-cloud`, ohne Chaos, ohne Endlos-Drehen.

WICHTIG (Real Talk):
- Copilot kann **nicht automatisch** zwischen Modellen umschalten. Du wählst das Modell pro Run im UI.
- Diese Datei ist dein “eine Quelle”-Controller: Du startest sie immer wieder, sie sagt dir **welcher Schritt** als nächstes dran ist.

## Modell-Empfehlung pro Schritt
- **Planner/Scan**: GPT-5.2
- **Executor**: Claude Sonnet 4.5 (Haiku nur für mechanische Doku-Edits/kleine Sachen)
- **Auditor**: GPT-5.2 (oder Sonnet 4.5, wenn extrem code-lastig)

## Entscheidungslogik (in dieser Reihenfolge)
1) **Wenn** `docs/archive/personal/Master-Plan.md` NICHT existiert → führe **Planner/Scan** aus (siehe Abschnitt A) und STOP.
2) **Wenn** Master-Plan existiert UND in Phase 0/1/2/3 noch `Task:`-Blöcke vorhanden sind → führe **Executor** aus (Abschnitt B) und STOP.
3) **Wenn** du gerade einen Executor-Run gemacht hast → führe **Auditor** aus (Abschnitt C) und STOP.
4) Wiederhole.

## A) Planner/Scan (nur Master-Plan, kein Code)
- Öffne Repo komplett, scanne Struktur, Docs, Security, Reliability, Multi-Tenant, API/Endpoints.
- Erstelle/aktualisiere **nur**: `docs/archive/personal/Master-Plan.md`
- Master-Plan muss enthalten:
  - Phase 0/1/2 (Launch-Blocker → Reliability → Readiness)
  - **Phase 3 — Release Candidate (Final Gate)** (Build/Install/Smoke/Packaging/Docs)
  - Jeder Task: Goal, Files to touch, DoD (mit Tests + Evidence), Impact, Komplexität

Output: “PLANNER DONE” + 10 Zeilen Executive Summary.

## B) Executor (Task-by-Task, maximal 3 Commits pro Cluster)
- Nimm den **obersten** Task in der **frühesten** Phase (0 zuerst).
- Pro Task: Branch → Implement → Tests → Evidence → Master-Plan Update als letzter Commit.
- QUALITY GATE (Pflicht): Tests + Expected Result + Evidence `path:line-range` + Regression Note.
- STOP CONDITION: Wenn DoD nach 2 echten Versuchen nicht erreichbar → `HANDOFF TO PLANNER REQUIRED`.

Output: “CLUSTER DONE” + Diff Summary + Tests + Evidence + Commit Messages + PR-Text.

## C) Auditor (strenger Verifier)
- Entferne Tasks nur, wenn DoD + Tests + Evidence **wirklich** erfüllt sind.
- Wenn FAIL:
  - Schärfe Gaps/Next Actions/Evidence Needed
  - Füge/erhöhe `AUDIT_FAILS` im Task
  - Update Issue-Tabelle + Risk List
- Output: Removed Tasks / Updated Tasks / Top 5 Blocker

Output: “AUDIT DONE”.

## Praktischer Bedienablauf (so nutzt du’s in VS Code)
1) Run A (GPT-5.2) → Master-Plan erzeugen.
2) Run B (Sonnet 4.5) → 1–n Tasks abarbeiten (aber pro Run lieber 1–3 Tasks).
3) Run C (GPT-5.2) → Audit.
4) Repeat bis Phase 3 fertig.