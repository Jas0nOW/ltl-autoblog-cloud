# Sprint 08 — Security Hardening (Phase 2)

Date opened: 2025-12-18
Scope: Convert all P1 security risks discovered in current code into small, auditable tasks. No code implementation in this document.

## Entry Criteria
- Phase 1 features exist but release is blocked by P1 security items.
- Smoke tests are not yet fully logged (see Phase 3 RC gate in the Master Plan).

## Exit Criteria
- All P1 tasks in this sprint are DONE with evidence.
- Master Plan OPEN RISKS contains no P0/P1 items not covered by tasks.

---

Task: [P1] Remove plaintext password from Gumroad welcome email (planner)
- Context:
  - Email currently includes `Passwort: %s` in the welcome message.
- DoD:
  - Welcome email includes no password.
  - Email includes login URL + password reset URL (or activation path) only.
  - No logs or templates contain the generated password string.
- Tests to run:
  - `php -l wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`
  - Manual: trigger webhook “new user created” and verify email body.
- Evidence required:
  - file: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php#L262-L283`
  - file: `docs/testing/logs/testing-log.md#L1-L160` (note: email verified)
- Risk note: Passwords in email are frequently leaked and long-lived.

---

Task: [P1] Disallow Gumroad secret in query string; require header-only auth (planner)
- Context:
  - Webhook currently reads `secret` from query param OR header.
  - Admin UI help text currently suggests a URL that embeds the secret.
- DoD:
  - Webhook rejects any request attempting to authenticate via query string.
  - Webhook accepts `X-Gumroad-Secret` only.
  - Admin UI help text updated to header-only usage.
- Tests to run:
  - `php -l wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`
  - `php -l wp-portal-plugin/ltl-saas-portal/includes/Admin/class-admin.php`
  - Negative: `curl -i -X POST "https://<site>/wp-json/ltl-saas/v1/gumroad/ping?secret=BAD" --insecure` → expect 403
  - Positive: `curl -i -X POST -H "X-Gumroad-Secret: <secret>" https://<site>/wp-json/ltl-saas/v1/gumroad/webhook --insecure` → expect 200 (with valid payload)
- Evidence required:
  - file: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php#L136-L156`
  - file: `wp-portal-plugin/ltl-saas-portal/includes/Admin/class-admin.php#L214-L226`
- Risk note: Query-string secrets leak via logs/referrers.

---

Task: [P1] Reduce blast radius of decrypted `wp_app_password` in `/make/tenants` (planner)
- Context:
  - Endpoint returns decrypted WP app passwords for active tenants.
- DoD (choose ONE control; document selection and rationale):
  - A) Source IP allowlist gate (config-driven), OR
  - B) Secrets excluded by default; explicit opt-in required, OR
  - C) Replace plaintext return with short-lived token exchange.
  - Document operational steps for Make.com.
- Tests to run:
  - `php -l wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`
  - Manual curl demonstrating blocked vs allowed access based on chosen control.
- Evidence required:
  - file: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php#L312-L378`
  - file: `docs/testing/logs/testing-log.md#L1-L200` (note: access control verified)
- Risk note: One token compromise can expose credentials for many tenants.

---

Task: [P1] Support env/wp-config overrides for secrets (planner)
- Context:
  - Secrets currently come from `wp_options` via `get_option()`.
- DoD:
  - Add documented override mechanism (constants/env) for:
    - Make token
    - Portal→Make API key
    - Gumroad secret
    - (optional) Gumroad product map
  - Keep backward compatibility (options remain supported).
- Tests to run:
  - `php -l wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal-secrets.php`
  - Manual: define overrides in `wp-config.php`; verify auth continues with options empty.
- Evidence required:
  - file: `wp-portal-plugin/ltl-saas-portal/includes/class-ltl-saas-portal-secrets.php#L1-L80`
  - file: `docs/testing/logs/testing-log.md#L1-L220` (note: override verified)
- Risk note: `wp_options` exposure is a common exfil path.

---

Task: [P2] Proxy/IP trust model documented for rate limiting (planner)
- Context:
  - Rate limiting derives IP from `HTTP_X_FORWARDED_FOR` when present.
- DoD:
  - Document intended deployment mode: direct vs behind trusted reverse proxy.
  - Align IP extraction rules to that mode.
- Tests to run:
  - `php -l wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php`
- Evidence required:
  - file: `wp-portal-plugin/ltl-saas-portal/includes/REST/class-rest.php#L82-L122`
- Risk note: Spoofable IP headers can weaken abuse controls.
