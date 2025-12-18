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

## 📚 Essential Documentation (Top 10)

New to the project? Start here:

1. **[Product Onboarding](docs/product/onboarding.md)** — Customer journey & setup guide
2. **[Architecture Overview](docs/reference/architecture.md)** — System design & components
3. **[API Reference](docs/reference/api.md)** — Portal ↔ Make.com contracts
4. **[Issues & Workflow](docs/workflow/issues-playbook.md)** — How we work (branches, commits, PRs)
5. **[Smoke Tests (Sprint 04)](docs/testing/smoke/sprint-04.md)** — Canonical test suite
6. **[Security Guide](SECURITY.md)** — Responsible disclosure & best practices
7. **[Pricing Plans](docs/product/pricing-plans.md)** — Plan limits & features
8. **[Release Checklist](docs/releases/release-checklist.md)** — Pre-launch validation
9. **[Gumroad Integration](docs/billing/gumroad.md)** — Webhook setup & testing
10. **[Multi-Tenant Blueprint](blueprints/LTL-MULTI-TENANT-SCENARIO.md)** — Make.com scenario logic

> **Full docs index**: See [docs/README.md](docs/README.md) for complete structure

## Roadmap

Work is tracked via GitHub Issues + Milestones (see `/docs` and `docs/README.md`).

## Security

Never commit secrets.
Store credentials encrypted at rest and limit REST endpoints with auth.
