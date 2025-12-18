# Archive — Historical Docs & Notes

This folder contains archived documentation that is either:
1. **Historical** — Replaced by newer versions but kept for reference
2. **Personal** — Internal work-in-progress notes (e.g., Master-Plan.md)
3. **Deprecated** — Old approaches/decisions no longer in use
4. **Speculative** — Early-stage ideas that didn't make it to implementation

## Folders

### `personal/`
Work-in-progress and planning notes:
- `Master-Plan.md` — Phase-based task planning and execution log
- **Not customer-facing** — Internal use only

---

## Rationale

By archiving obsolete docs (rather than deleting), we:
- **Preserve history** for reference during troubleshooting
- **Avoid confusion** between old/new approaches
- **Keep active docs clean** (only current best practices in main folders)
- **Enable CI/CD** to avoid broken links (archived docs still exist)

## Rules

1. **When to archive**: Once a doc is superseded or no longer active, move it here with a note explaining why.
2. **Name format**: Preserve original name or add suffix: `old_approach_v1.md`, `sprint_03_notes.md`
3. **Link from active docs**: In the replacement doc, link to archived version if readers need context.
4. **Never delete** — Move to archive instead.

---

## Archived Items (Index)

### Testing & Docs
- None yet (first cleanup phase)

### Engineering / Product
- None yet (first cleanup phase)

---

## See Also
- [Active Docs Structure](../README.md)
- [Release Checklist](../releases/release-checklist.md)
