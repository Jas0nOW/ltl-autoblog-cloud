# LTL AutoBlog Cloud (Portal + Make Multi-Tenant)

This repository contains:
- **Sanitized Make.com blueprints** (for reference / customer delivery)
- A **WordPress Portal plugin** (customer login + settings + WP connect)
- Docs, scripts, and project management assets (issues/milestones/templates)

> Goal: Customers subscribe, connect their own WordPress site, configure RSS+tone+language, and your Make scenario publishes posts to their site.

## Quick start (local / staging)

1. Install WordPress (LocalWP, XAMPP, etc.)
2. Copy `wp-portal-plugin/ltl-saas-portal` into `wp-content/plugins/`
3. Activate **LTL AutoBlog Cloud Portal**
4. Create a page with shortcode: `[ltl_saas_dashboard]`
5. Test API: `GET /wp-json/ltl-saas/v1/health`

## Roadmap

Work is tracked via GitHub Issues + Milestones (see `/docs` and `docs/README.md`).

## Security

Never commit secrets.
Store credentials encrypted at rest and limit REST endpoints with auth.
