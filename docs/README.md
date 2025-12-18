# Documentation

This folder is intentionally structured to keep the repo professional and easy to navigate.

## Start here
- Product onboarding: [onboarding.md](product/onboarding.md)
- Architecture overview: [architecture.md](reference/architecture.md)
- API contract (Portal ↔ Make): [api.md](reference/api.md)
- Release process: [release-checklist.md](releases/release-checklist.md)
- Smoke tests: [sprint-04.md](testing/smoke/sprint-04.md) (canonical reference)

## Structure
- **`reference/`** — core reference docs (API, architecture)
- **`product/`** — customer-facing product docs (onboarding, plans, pricing)
- **`billing/`** — billing/webhook integration (Gumroad)
- **`engineering/`** — implementation details + Make.com engine contracts
- **`ops/`** — operational notes (SSL/proxy, security hardening)
- **`testing/`** — smoke tests and test logs (sprint-04.md is canonical)
- **`workflow/`** — how we work (issue workflow, closeout, VS Code setup)
- **`marketing/`** — landing page copy + publishing steps
- **`releases/`** — release checklists, notes, version history
- **`archive/`** — historical + internal planning docs (not customer-facing)

## Guidelines (Keep it Clean)

✅ **DO:**
- Update existing docs when content evolves
- Move obsolete docs to `archive/` with explanation
- Link from active docs to archived versions if needed for context
- Write docs as single sources of truth (avoid duplication)
- Use consistent example formats across all API/technical docs

❌ **DON'T:**
- Delete docs — move to archive instead
- Create one-off sprint notes in the repo (use issues + PRs)
- Duplicate sections across multiple files (link instead)
- Include AI prompts, personal notes, or scratchpads (archive them)

## When Reviewing PRs

1. **API changes** → Update `reference/api.md` + corresponding smoke tests
2. **Onboarding changes** → Update `product/onboarding-detailed.md` + summary in `onboarding.md`
3. **Security/Ops** → Update `ops/` + consider impact on `release-checklist.md`
4. **Release artifacts** → Update `releases/` + `testing/logs/testing-log.md` template
5. **Deprecated content** → Move to `archive/` (don't delete)

---

**Last Updated**: 2025-12-18  
**Maintained by**: Engineering Team

