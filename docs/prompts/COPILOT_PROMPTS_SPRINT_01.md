# Copilot Prompts – LTL AutoBlog Cloud

Paste one prompt at a time into GitHub Copilot Chat (VS Code). Keep the scope tight.

---

## Prompt A — Implement Issue #10 (WP connect + test)

You are working in this repo: `ltl-autoblog-cloud`.
Implement **Issue #10** in the WordPress plugin located at:
`wp-portal-plugin/ltl-saas-portal/`

Requirements:
- Add customer dashboard UI (shortcode `[ltl_saas_dashboard]`) to collect:
  - `wp_url` (site base URL)
  - `wp_user` (username or user email)
  - `wp_app_password` (WordPress Application Password)
- Save values per logged-in user in table `{$wpdb->prefix}ltl_saas_connections`.
- Encrypt the application password at rest before saving.
  - Use `openssl_encrypt` with AES-256-CBC.
  - Derive key from `AUTH_KEY` and `SECURE_AUTH_KEY` (hash them).
  - Store IV alongside ciphertext in a single string.
- Add a “Test connection” button.
  - On click, call an authenticated portal endpoint `POST /wp-json/ltl-saas/v1/wp-connection/test`
  - This endpoint should:
    - Load the user’s saved connection
    - Call the remote endpoint `{wp_url}/wp-json/wp/v2/users/me` using Basic Auth with app password
    - Return success + remote user info (id, name, roles) OR error message
- Add nonce checks and sanitize/validate URL and username.
- Provide a minimal UI (no fancy CSS needed yet) but clean structure.
- Update docs if needed.

Also:
- Add/update unit-ish helper functions as needed.
- No secrets in repo.
- After coding, list the files changed and how to test.

---

## Prompt B — Implement Issue #9 (Settings UI)

Implement **Issue #9** in the same plugin.
Add fields to the dashboard:
- rss_url (url)
- language (enum: de, en, es, fr, it, pt, nl, pl)
- tone (enum: professional, casual, nerdy, funny, serious)
- frequency (enum: daily, 3x_week, weekly)
- publish_mode (enum: draft, publish)

Save per user in `{$wpdb->prefix}ltl_saas_settings` and reload values on page load.
Validate enums and URLs.
Show a “Saved ✓” message.

---

## Prompt C — Implement Issue #12 (active-users endpoint)

Implement **Issue #12**:
- Add route `GET /wp-json/ltl-saas/v1/active-users`
- Protect via API key:
  - Read key from WP option `ltl_saas_api_key`
  - Compare against header `X-LTL-API-Key`
- Return array of active users (for now treat every user with a saved connection as active).
Return:
- user_id
- settings (structured)
- wp_url, wp_user
- wp_app_password (decrypted) OR a token reference (choose and document)

---

## Prompt D — Implement Issue #14 (run-callback endpoint)

Implement **Issue #14**:
- Route `POST /wp-json/ltl-saas/v1/run-callback`
- Auth with same API key method as active-users.
- Accept JSON body:
  - user_id (int)
  - status (success|error)
  - post_url (optional)
  - error (optional)
  - meta (optional object)
- Store into `{$wpdb->prefix}ltl_saas_runs`.
- Dashboard shows last 5 runs for the logged-in user.

