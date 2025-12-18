# COPILOT_PROMPTS_SPRINT_XX.md (Single-File Workflow Template)

> **Dein Wunsch-Workflow (einfach):**
> Du arbeitest **direkt auf `Phase1-Core`**, erledigst **Issue für Issue**, und nach jedem Issue gibt’s:
> **Stage → Commit → Push**.
> Am Ende machst du **eine finale PR `Phase1-Core → main`** mit Zusammenfassung + mehreren `Closes #…`.

---

## 0) Sprint Header
- Sprint/Phase: `SPRINT_XX`
- Repo: `…`
- Working branch: `Phase1-Core`
- Base branch (stable): `main`
- Merge-Regel: **nur am Ende** (Final PR `Phase1-Core → main`)

### Modell-Empfehlung (Copilot)
- Implementierung / Issue-Abarbeitung: **GPT-5 / GPT-5.2**
- Final PR + Security Review: **Claude Sonnet** (falls verfügbar), sonst **GPT-5 / GPT-5.2**

---

## 1) Sprint Task-Liste (Issue für Issue)
> Pflege hier den Status. Copilot nutzt das als Wahrheit.

**Status:**
- `[ ]` TODO
- `[x]` DONE (fertig in `Phase1-Core`, bereit für finalen Merge nach `main`)

### DONE → wird im Final-Merge als `Closes #…` aufgenommen
### TODO → bleibt offen

#### Aufgaben
- [ ] **#10 — WP Connect + Test Endpoint**
  - AC (Acceptance Criteria):
    - wp_url/wp_user/app_pass sicher speichern (keine Leaks im UI/Logs)
    - Test-Endpoint: Auth/Capability check, saubere Errors
    - Fail-Path getestet
  - Scope: `security` (oder `portal`)
  - Test (lokal):
    - WP_DEBUG=true, Plugin aktivieren, Settings öffnen, Connect testen
- [ ] **#11 — <Titel>**
  - AC: …
  - Scope: …
  - Test: …
- [ ] **#12 — <Titel>**
  - AC: …
  - Scope: …
  - Test: …

---

## 2) PROMPT: Nächstes TODO-Issue abarbeiten (Commit + Push)
> **Das kopierst du in Copilot Chat/Agent.**
> Ziel: Copilot nimmt **genau das nächste TODO** aus dieser Datei, implementiert es auf `Phase1-Core`, macht 1–3 Commits und pusht.
> **Kein Merge nach main.**

```text
You are my coding + git assistant. Work strictly issue-by-issue from this sprint file.

Hard rules:
1) NEVER merge into main/master.
2) Work only on branch `Phase1-Core` (do not create PRs in this step unless I ask).
3) Before running ANY command, show the exact commands and ask for confirmation.
4) Implement exactly ONE next TODO item from this sprint file.
5) After successful implementation and local checks, create 1–3 clean commits and push to origin/Phase1-Core.
6) Update this sprint file: mark the issue as DONE `[x]` and add a short result note under it.

Steps:
A) Read this file and select the first unchecked task `[ ]`.
B) Propose read-only git checks:
   - git fetch --all --prune
   - git branch --show-current
   - git status
C) Ensure we are on `Phase1-Core` and up to date (pull only if needed).
D) Implement the Acceptance Criteria. Keep changes scoped.
E) Security basics where relevant:
   - Nonce/CSRF for admin actions / REST endpoints
   - Capability checks (current_user_can)
   - Sanitization/escaping for inputs/outputs
   - SQL prepared statements if any DB access
F) Commit discipline:
   - Make 1–3 commits max for this issue:
     Conventional commits: type(scope): message
     Body: 2–4 lines, include "Refs #<issue>" (optional).
G) Push:
   - git push (set upstream if needed)
H) Provide:
   - Short summary of what changed
   - Local WP test steps for this issue
I) Update this sprint file (checkbox DONE + short note).
Stop. Do NOT merge to main.
```

---

## 3) PROMPT: Finaler Merge `Phase1-Core → main` (PR + Summary + Security Review + Merge nur nach Bestätigung)
> **Das nutzt du am Sprint-Ende.**
> Copilot soll: (1) alle Änderungen zusammenfassen, (2) PR erstellen, (3) Checks prüfen, (4) **nur nach deiner 2x Bestätigung mergen**.

```text
You are the repository maintainer.

Goal:
Create the FINAL PR from `Phase1-Core` into `main` that summarizes ALL sprint changes.
Use this sprint file to list ALL DONE issues to auto-close on merge.
Then merge ONLY if checks are green AND I confirm twice.

Hard rules:
1) Never commit directly to main.
2) Never merge without: PR draft shown + checks green + my explicit confirmation twice.
3) Before running ANY command, show commands and ask: "Run these commands? (yes/no)"
4) Closing lines:
   - Include multiple `Closes #...` for DONE tasks only.
   - Use `Refs #...` for anything uncertain or not fully done.

Step 1 — Extract closures from sprint file:
- Parse this file and collect all issues marked DONE `[x]` (e.g., #10, #12, ...).
- Prepare a section "Issue closures" with one line per issue: `Closes #10`.

Step 2 — Gather git facts (read-only):
Propose then run:
- git fetch --all --prune
- git log --oneline --decorate main..Phase1-Core
- git diff --stat main...Phase1-Core
- git diff main...Phase1-Core

Step 3 — Draft PR title + body (based on actual diff):
PR body must include:
- Summary (1–3 bullets)
- Changes (grouped by module/area)
- Commits included (count + highlights)
- Testing (local WP + CI)
- Risks / Rollback
- Issue closures (multiple Closes lines from Step 1)

Step 4 — Short security review (must be evidence-based):
- Nonce/CSRF
- Capability checks/auth (esp. endpoints)
- Secrets storage and exposure risk
- SQL prepared statements
- Sanitization/escaping
Report: what you verified in diff + what needs follow-up.

Step 5 — Pre-merge WordPress checklist:
Provide a checkbox list for local verification (activation, settings, flows, WP_DEBUG, roles, uninstall cleanup).

Step 6 — Create PR (no merge yet):
If `gh` exists:
- gh pr create --base main --head Phase1-Core --title "<title>" --body "<body>"
Else:
- Output copy/paste PR Title + Body and GitHub UI steps.

Step 7 — Merge only if green + confirmed twice:
If `gh` exists, check PR checks and status. If not green, stop.
Ask: "All checks are green. Merge now? (yes/no)"
Only if yes:
- gh pr merge <PR_NUMBER> --squash  (recommended)
Then output post-merge steps (cleanup, next sprint).
```

-- -

## 4) Warum dieser Single-File-Workflow sinnvoll ist (und wo er schwächer ist)

**Plus:**
- extrem wenig Overhead (kein Branch/PR pro Issue nötig)
- du hast nach jedem Issue einen Commit+Push “Savepoint” auf `Phase1-Core`
- finaler Merge ist sauber bündelbar

**Minus (ehrlich):**
- wenn ein Issue Mist baut, liegt es schon in `Phase1-Core` (aber noch nicht in main)
- du hast weniger “Review-Schranken” als bei PR pro Issue

**Safety-Net (empfohlen):**
- `main` bleibt dein “Production”-Branch
- Deploy nur von `main` (nicht von `Phase1-Core`)
- vor Final-Merge: WP_DEBUG on + einmal “Smoke Test” über die wichtigsten Flows
