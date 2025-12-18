# Pricing Plans — V1 Final (Issue #8)

> **Canonical Plan Names** (normalized to lowercase in code): `basic`, `pro`, `studio`
> **Post Limits** are enforced at API level via `/make/tenants` (skip with `monthly_limit_reached` reason).

## Basic
- 1 Blog (1 WP Connection)
- 1 RSS Feed
- Draft-Mode (optional Publish nach Freischaltung)
- **30 Posts/Monat**
- Canonical name: `basic`

## Pro
- 1–3 Blogs
- bis zu 5 RSS Feeds
- Publish-Mode
- **120 Posts/Monat**
- Canonical name: `pro`
- Add-on: Cross-Promoter light (später)

## Studio
- alles aus Pro
- Add-on: Newsletter Digest
- Add-on: Video-Bot (später)
- **300 Posts/Monat**
- Canonical name: `studio`

## Upgrades & Add-ons (später)
- Cross-Promoter (Social Push)
- Newsletter Bot (weekly digest)
- Video Bot (Auto clips)
- Hosting-Bonus (Blog-Provisioning auf deinem Hosting)

---

### Limits Enforcement (Issue #8)

**Database**: `wp_ltl_saas_settings`
- `plan` (VARCHAR): One of `basic`, `pro`, `studio` (lowercase)
- `posts_this_month` (INT): Usage counter for current period
- `posts_period_start` (DATE): Start of current billing period (rolls over monthly)

**API Response** (`GET /make/tenants`):
- `posts_used_month` (INT): Current usage (Issue #8: renamed from `posts_this_month` for clarity)
- `posts_limit_month` (INT): Plan limit (derived from `plan` via `ltl_saas_plan_posts_limit()`)
- `posts_remaining` (INT): Calculated remaining = `posts_limit_month - posts_used_month`
- `skip` (BOOL): If `true`, skip this tenant (monthly_limit_reached or is_active=false)

**Rollover Logic**:
- Each `/make/tenants` call checks if `posts_period_start != current month`
- If true: resets `posts_this_month` to 0 and updates `posts_period_start`
- Prevents month-to-month spillover

---

### Gumroad Product Mapping

Example in WordPress Admin → LTL AutoBlog Cloud → Billing:
```json
{
  "prod_BASIC_PRODUCT_ID": "basic",
  "prod_PRO_PRODUCT_ID": "pro",
  "prod_STUDIO_PRODUCT_ID": "studio"
}
```

On webhook `/gumroad/webhook`, product_id is mapped to plan; if unmapped, defaults to `basic`.

