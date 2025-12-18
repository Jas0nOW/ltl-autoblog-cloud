# Issue → Branch → Commit → Merge (Mini-Workflow)

This is the smallest loop that keeps you organized without feeling like “corporate Jira”.

1) Pick ONE issue (e.g. #10).
2) Create branch in VS Code: `feat/issue-10-wp-connect`
3) Do the work in small commits.

**Commit message template**
- `feat(portal): wp connect form (refs #10)`
- `feat(portal): add connection test endpoint (closes #10)`  ← this one should close it after merge

**Why “closes #10”?**
GitHub can auto-close issues when a PR is merged to the default branch if the PR description (or a commit message) contains keywords like:
`Closes #10`, `Fixes #10`, `Resolves #10`.

4) Open PR, paste a short checklist in the PR description, include:
- `Closes #10`
- Testing steps
5) Merge PR → Issue closes automatically.
6) Move to next issue.

Labels & milestones help you filter and keep a “single source of truth”.
