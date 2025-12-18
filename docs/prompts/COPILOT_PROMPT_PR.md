# Copilot Prompt: PR von `Phase1-Core` → `main` (inkl. Security-Review + WP-Testcheckliste)

**Empfohlenes Copilot-Modell:**
- **Claude Sonnet** (wenn verfügbar) für **Security-Review + prägnante PR-Texte**.
- Alternativ: **GPT-5 / GPT-5.2** für **saubere Struktur + vollständige Checklisten**.

---

## Variante A — Copilot Chat (nur Text generieren, keine Git-Aktionen)

> Kopiere alles in Copilot Chat.

```text
You are the maintainer of this repository.
Goal: Draft a merge PR from branch `Phase1-Core` into `main` that includes all changes from this round.

Hard rules:
- Do NOT merge anything.
- Do NOT push to main.
- Only draft text + reviews + checklists based on the actual code changes.

Context:
- Known relevant commit: 1a4874d (WP Connect + Test Endpoint)
- Include: "Closes #10" (exactly once) in the PR description.

Steps:
1) Read the branch diff between main and Phase1-Core:
   - Prefer: `git log --oneline --decorate main..Phase1-Core`
   - And: `git diff --stat main...Phase1-Core`
   - And inspect the actual diff: `git diff main...Phase1-Core`
2) From that diff, produce a PR title and PR description with:
   - Summary (what & why)
   - Changes (bulleted, grouped by area/module)
   - Test steps (local WP steps, concise)
   - Risks / Rollback plan
   - Exactly one closing line: `Closes #10`
3) Do a short security review focused on:
   - Nonce/CSRF protection for admin actions + REST endpoints
   - Capability checks (current_user_can) and auth for endpoints
   - Storage of secrets (options table, encryption, wp-config, env vars) and exposure risk
   - SQL: prepared statements, no string concatenation, use $wpdb->prepare
   - Sanitization/escaping: sanitize_text_field, esc_url, esc_html, wp_kses, validate input types
   - Output escaping for admin UI and API responses
   Report: (a) what you verified in diff, (b) what is missing / needs follow-up.
4) Provide a local WordPress pre-merge checklist:
   - Installation/activation
   - Settings pages load without notices
   - Connect flow works and fails safely
   - Test endpoint behavior + auth
   - Data persistence + uninstall behavior
   - Multisite compatibility (if relevant)
   - PHP/WP versions and WP_DEBUG checks
   - Security checks (XSS/CSRF, permissions)
   Format as a checkbox list.

Output format (exact headings):
- PR Title:
- PR Description:
- Security Review:
- Local WP Pre-Merge Checklist:
```

---

## Variante B — Agent Mode (darf Git-Befehle ausführen, aber NUR zum Analysieren; kein Merge/Push)

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
