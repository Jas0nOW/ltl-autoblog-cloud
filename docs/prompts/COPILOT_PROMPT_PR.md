# Copilot Prompt: PR von `Phase1-Core` → `main` (inkl. Security-Review + WP-Testcheckliste)

**Empfohlenes Copilot-Modell:**
- **Claude Sonnet** (wenn verfügbar) für **Security-Review + prägnante PR-Texte**.
- Alternativ: **GPT-5 / GPT-5.2** für **saubere Struktur + vollständige Checklisten**.

---

## Variante A — Agent Mode (darf Git-Befehle ausführen, aber NUR zum Analysieren; kein Merge/Push)

> Nur nutzen, wenn Copilot bei dir Commands ausführen kann.

```text
You are the repo maintainer in agent mode.

Hard rules:
- NEVER merge.
- NEVER push to main.
- You may run read-only git commands to compute diffs/logs.
- Before running commands, show them.
- After showing commands, ask for confirmation: "Run these commands? (yes/no)"

Goal:
Draft a PR from `Phase1-Core` into `main` including all changes from this round, plus a short security review and a WP local test checklist.
Include exactly one closing line in PR description: `Closes #10`.

Plan:
1) Propose these read-only commands:
   - `git fetch --all --prune`
   - `git branch --show-current`
   - `git log --oneline --decorate main..Phase1-Core`
   - `git diff --stat main...Phase1-Core`
   - `git diff main...Phase1-Core`
2) Ask for confirmation to run them.
3) Based on the results, produce:
   - PR Title
   - PR Description (Summary, Changes, Test steps, Risks/Rollback, "Closes #10")
   - Security Review (Nonce/Auth, Secrets, SQL, Sanitization/Escaping)
   - Local WP Pre-Merge Checklist (checkboxes)

Output headings exactly:
- PR Title:
- PR Description:
- Security Review:
- Local WP Pre-Merge Checklist:
```

---

## Bonus: Copy/Paste PR Template (falls du es direkt in GitHub einfügen willst)

```md
## Summary
- …

## Changes
- …

## Test steps
1. …

## Risks / Rollback
- …

Closes #10
```
