# Audit Report - 2025-12-18

This audit was conducted by your Lead Engineer to assess the current state of the `Phase1-Core` branch.

## 1. Issue Status Board

| Issue | Status | Relevant Files | Missing Acceptance Checks |
|---|---|---|---|
| **#9 Settings-UI** | ✅ Done | `includes/class-ltl-saas-portal.php` (shortcode), `includes/Admin/class-admin.php` | - |
| **#10 Connect WordPress (encrypted)** | ✅ Done | `includes/class-ltl-saas-portal.php` (shortcode), `includes/class-ltl-saas-portal-crypto.php` | - |
| **#11 Access Control** | ✅ Done | `includes/class-ltl-saas-portal.php` (shortcode), `includes/REST/class-rest.php` | - |
| **#12 active-users Endpoint** | ✅ Done | `includes/REST/class-rest.php` | - |
| **#13 Make Multi-Tenant refactor** | ✅ Done | `includes/REST/class-rest.php` | - |
| **#14 Run callback Endpoint** | ✅ Done | `includes/REST/class-rest.php` | - |
| **#15 Runs Tabelle + Dashboard Ansicht** | ✅ Done | `includes/class-ltl-saas-portal.php` (shortcode) | - |
| **#16 Posts/Monat Limits enforce** | ✅ Done | `includes/class-ltl-saas-portal.php`, `includes/REST/class-rest.php` | Smoke tests pending. |

## 2. Top 10 Breakpoints

1.  **`LTL_SAAS_Portal_Crypto::decrypt`**: Handles both legacy and v1 HMAC formats. An error in logic here could expose secrets.
2.  **`LTL_SAAS_Portal_REST::permission_make_tenants`**: `hash_equals` is used correctly, but the token is stored in `wp_options`, which could be exposed by other plugins.
3.  **`LTL_SAAS_Portal_REST::get_make_tenants`**: `LTL_SAAS_Portal_Crypto::decrypt` is called here. Any unhandled `WP_Error` could break the entire tenant list generation.
4.  **dbDelta in `LTL_SAAS_Portal::activate`**: Complex SQL changes in the future could fail, leaving the DB in an inconsistent state.
5.  **Nonce checks in `LTL_SAAS_Portal::shortcode_dashboard`**: The nonce is checked for form submissions, which is good, but the "Test Connection" AJAX call relies on `wp_rest` nonce, which is standard but could be a weak point if not handled correctly on the frontend.
6.  **`LTL_SAAS_Portal_REST::get_active_users`**: API key check is hash_equals, which is good. However, this endpoint reveals potentially sensitive URLs and usernames.
7.  **Input sanitization in `LTL_SAAS_Portal::shortcode_dashboard`**: `esc_url_raw`, `sanitize_user`, etc., are used, which is correct. A missing sanitization on a new field could introduce XSS.
8.  **`LTL_SAAS_Portal_REST::run_callback`**: `tenant_id` is taken directly from the JSON payload. If an attacker could guess tenant IDs, they could potentially increment post counts for other users. The API key provides a layer of protection.
9.  **Error handling in `LTL_SAAS_Portal_Crypto`**: Errors return a `WP_Error`, which is good practice. However, the calling code must always check for `is_wp_error`.
10. **`ltl_saas_get_tenant_state` Helper**: This function is central. A performance issue or bug here would affect all limit-checking logic.

## 3. Next 3 Steps (60-90 min each)

1.  **Smoke Test Sprint 04**: Manually run through the checks in `docs/SMOKE_TEST_SPRINT_04.md`. This will validate the new post-limiting feature end-to-end.
2.  **Refactor Secret Handling**: Create a dedicated `LTL_SAAS_Portal_Secrets_Manager` class. Move all `get_option` calls for `ltl_saas_make_token` and `ltl_saas_api_key` into this class. This centralizes secret management and makes it easier to add more robust secret storage in the future (e.g., environment variables, a vault).
3.  **Improve Input Validation in `run_callback`**: In `LTL_SAAS_Portal_REST::run_callback`, before processing the callback, add a check to ensure the `tenant_id` from the payload exists in the `ltl_saas_connections` table. This prevents bad actors from sending bogus callbacks.
