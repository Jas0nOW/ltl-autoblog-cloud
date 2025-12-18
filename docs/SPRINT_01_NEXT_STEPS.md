# Sprint 01 – “End-to-End MVP Loop” (Portal → Make → Customer WordPress)

Target: The smallest possible **working loop**:
1) Customer logs in on your portal
2) Customer connects their WordPress site (URL + username + Application Password)
3) Customer saves blog settings (RSS, language, tone, frequency, publish mode)
4) Make.com fetches active users via portal REST
5) Make.com publishes a post to the customer site
6) Make.com reports back (run-callback) and portal shows last run

## The 4 issues to implement (order)

### 1) Issue #10 – WP connect (store + test)
- UI fields: wp_url, wp_user, app_password
- Store per user in DB, encrypted at rest
- Button “Test connection” calls `GET /wp/v2/users/me` on the customer site

### 2) Issue #9 – Settings UI (store + reload)
- UI fields: rss_url, language, tone, frequency, publish_mode
- Store per user in DB (validated)
- Reload shows values again

### 3) Issue #12 – REST `active-users` (Make pulls config)
- `GET /wp-json/ltl-saas/v1/active-users`
- Protected by simple API key (header)
- Returns: user_id, plan, limits, settings, wp_url, wp_user, wp_app_password (or token ref)

### 4) Issue #14 – Run callback (Make pushes result)
- `POST /wp-json/ltl-saas/v1/run-callback`
- Store run status + post_url + error
- Show “Last runs” on dashboard

## Commit rhythm (learning-by-doing)

For each issue:
1. Create branch: `feat/issue-10-wp-connect`
2. Small commits while coding
3. Final commit message uses conventional style:
   - `feat(portal): add encrypted WP connection storage (closes #10)`
4. Merge to `main` and close issue automatically with keywords.

GitHub supports keywords like `Closes #10` / `Fixes #10` to auto-close issues when the PR is merged to the default branch.
