# Make.com Blueprints — Customer Deliverables

> **Status**: Production Ready  
> **Last Updated**: 2025-12-18

---

## Overview

This folder contains **sanitized** (secrets-removed) Make.com scenario blueprints for customers and implementation teams.

### For Customers

Use these templates to set up your own Make.com scenarios:

1. **LTL-MULTI-TENANT-TEMPLATE.json** — Multi-tenant automation scenario
   - Iterates over Portal tenants
   - Pulls RSS feeds
   - Generates content (optional AI)
   - Publishes to WordPress
   - Reports back to Portal

See [LTL-MULTI-TENANT-SCENARIO.md](LTL-MULTI-TENANT-SCENARIO.md) for full setup guide.

### For Implementation Team

All blueprints are:
- ✅ **Sanitized** (no secrets, tokens, credentials visible)
- ✅ **Versioned** (committed in git with change tracking)
- ✅ **Documented** (markdown guides + inline comments)
- ✅ **Ready for sharing** (safe for GitHub, email, etc.)

---

## File Organization

```
blueprints/
├── README.md                           (this file)
├── LTL-MULTI-TENANT-SCENARIO.md        (full documentation)
├── sanitized/
│   ├── LTL-MULTI-TENANT-TEMPLATE.json  (importable template)
│   ├── us/
│   │   └── LTL Blog-Bot v2.1 [US].blueprint.json
│   ├── de/
│   │   └── LTL Blog-Bot v2.1 [DE].blueprint.json
│   └── ...
└── (raw blueprints not in git - see .gitignore)
```

---

## Security

### ✅ Safe Files (Committed in Git)

- **`sanitized/`** — All secrets removed, safe to commit
- **`.md` documentation** — Setup guides, no credentials

### ⛔ Unsafe Files (Gitignored)

- **`blueprints_raw/`** — Original exports with credentials (if used locally)
- Any `.env` files or credential stores

### Guidelines

1. **Never commit** unsanitized blueprints (credentials visible)
2. **Always use** `scripts/sanitize_make_blueprints.py` before committing
3. **Share only** files from `sanitized/` folder
4. **Credentials** go in Make.com Secure Storage (not in blueprints)

---

## How to Import

### For Customers

1. Copy contents of desired `.json` file (e.g., `LTL-MULTI-TENANT-TEMPLATE.json`)
2. Log into Make.com account
3. Click **Create → Scenario**
4. Click **Import Blueprint** (usually in top menu or paste raw JSON)
5. Paste JSON contents
6. Follow configuration steps in [LTL-MULTI-TENANT-SCENARIO.md](LTL-MULTI-TENANT-SCENARIO.md)

### For Team (Testing)

1. Export your configured scenario from Make.com
2. Run through sanitizer: `python scripts/sanitize_make_blueprints.py <input.json> <output.json>`
3. Verify credentials are removed
4. Commit sanitized version to `blueprints/sanitized/`

---

## Sanitizer Script

See [scripts/README.md](../scripts/README.md) for details on `sanitize_make_blueprints.py`.

**Quick usage:**
```bash
python scripts/sanitize_make_blueprints.py \
  ./my-exported-blueprint.json \
  ./blueprints/sanitized/my-scenario.json
```

**What it removes:**
- Make connection IDs (`__IMTCONN__`)
- API keys, tokens, passwords
- Email addresses (full → redacted)
- URLs that look like webhooks/secrets
- Sensitive field values (auth, bearer, secret, etc.)

---

## Template Customization

All templates include placeholder variables (e.g., `{{PORTAL_URL}}`, `{{MAKE_TOKEN}}`).

**Configuration steps:**
1. Import template
2. Find placeholders in each module
3. Replace with your actual values
4. Use Make.com Secure Storage for sensitive values
5. Save scenario

---

## Support & Examples

- **Multi-Tenant Setup**: [LTL-MULTI-TENANT-SCENARIO.md](LTL-MULTI-TENANT-SCENARIO.md)
- **API Contract**: See [docs/reference/api.md](../docs/reference/api.md)
- **Troubleshooting**: Check Portal logs at `/wp-content/debug.log`

---

## Versioning

Blueprints are versioned by date + scenario name:
- `LTL-MULTI-TENANT-TEMPLATE.json` — Template (no version suffix, always latest)
- `2025-12-18_LTL-Blog-Bot_v2.1_US.json` — Archived versions (for reference)

---

**Last Updated**: 2025-12-18  
**Maintained by**: LTL AutoBlog Cloud Engineering Team
