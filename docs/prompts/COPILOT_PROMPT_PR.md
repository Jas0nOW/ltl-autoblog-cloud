# Copilot Prompt: Finaler Merge (alle Commits zusammenfassen → PR erstellen → nach Checks mergen)

**Ziel:** Copilot soll **alle Commits/Änderungen** aus `Phase1-Core` gegenüber `main` zusammenfassen, eine **Final-PR** vorbereiten und **erst nach erfolgreichen Checks + deiner Bestätigung mergen**.

**Copilot-Modell (Empfehlung):**
- **Claude Sonnet**: meist sehr gut für PR-Text + Security-Review.
- Alternativ **GPT-5 / GPT-5.2**: sehr zuverlässig für saubere Struktur + Checklisten.

---

## Prompt (Agent Mode empfohlen: darf Git-Befehle ausführen)

> Kopiere alles in Copilot (Agent/Terminal-fähig).
> Voraussetzung: Du hast Zugriff auf Repo + optional GitHub CLI `gh` (wenn nicht, soll Copilot nur Text liefern).

```text
You are the repository maintainer.

Goal:
Create the FINAL PR from branch `Phase1-Core` into `main`, summarizing ALL commits/changes in this round, then merge it — but ONLY after checks pass and after I explicitly confirm.

Hard rules (non-negotiable):
1) NEVER commit directly to main.
2) NEVER merge without showing me a summary + checklist first.
3) Before running ANY command, show the exact commands and ask: "Run these commands? (yes/no)"
4) Merge step must require a second explicit confirmation after tests/checks are green.

Step 0 — Gather facts (read-only):
- Fetch latest refs and verify current branch and upstreams.
- Compute the complete change range main...Phase1-Core.
Run (propose first, then ask to execute):
- git fetch --all --prune
- git branch --show-current
- git status
- git log --oneline --decorate main..Phase1-Core
- git diff --stat main...Phase1-Core
- git diff main...Phase1-Core

Step 1 — Draft PR content (based on actual diff, not guesses):
Produce:
- PR Title (final merge)
- PR Description containing:
  - Summary (1–3 bullets)
  - Changes (grouped by module/area, bullets)
  - Commits included (short list or count + highlights)
  - Testing (what was run / what must be run)
  - Risks / Rollback plan
  - Exactly one closing line for known issue(s), e.g.: "Closes #10"
Also add:
- Short Security Review: nonce/auth, capabilities, secrets storage, SQL (prepared statements), sanitization/escaping.
- Local WP Pre-merge checklist (checkbox list): activate plugin, settings pages, connect flow, endpoint auth, WP_DEBUG, multisite if relevant, uninstall cleanup.

Step 2 — Create PR (NO merge yet):
If GitHub CLI `gh` is available:
- Create PR from Phase1-Core -> main using your title/body.
If `gh` is NOT available:
- Output copy/paste text + instructions for creating the PR in GitHub UI.

Commands (propose then confirm):
- gh pr create --base main --head Phase1-Core --title "<PR Title>" --body "<PR Body>"

Step 3 — Verify checks locally (before merge):
Propose a minimal test run for a WordPress plugin repo:
- Composer install (if applicable), PHP lint, unit tests (if present), WP-CLI smoke (if applicable).
Also provide manual WP checklist steps.

Step 4 — Merge (ONLY if green + confirmed twice):
Condition to merge:
- PR created
- No merge conflicts
- Required checks are green (or none configured) AND I confirm
Then propose ONE merge strategy (default: squash or merge-commit) and explain tradeoff in 2 lines.
Commands (propose then require explicit confirmation):
- gh pr merge <PR_NUMBER> --squash   (or --merge if we want full commit history)

Finally:
- Output a short post-merge note: branch cleanup suggestions + next steps.