# Multi-Tenant Blueprint — Make.com Scenario

> **Status**: Deliverable (Phase 2)
> **For**: Customers + Implementation Team
> **Description**: Complete Make.com scenario for iterating over multiple WordPress tenants, pulling content, publishing, and reporting back to Portal.

---

## Overview: How It Works

```
[Scheduler Trigger]
    ↓
[HTTP GET /make/tenants] ← Pull active tenants from Portal
    ↓
[Iterator] ← Loop over each tenant
    ├→ [RSS Reader] ← Fetch tenant's RSS feed
    ├→ [AI/Gen] ← Generate content
    ├→ [WP REST POST] ← Publish to tenant's WordPress
    ├→ [HTTP POST /run-callback] ← Report success/failure
    └→ [Error Handler] ← Catch & report failures
```

---

## Scenario Modules (Make.com)

1. **Scheduler Trigger**
   - **Type**: Scheduler Module
   - **Interval**: Every 30 minutes (configurable)
   - **Purpose**: Kicks off the entire scenario

2. **HTTP GET — Tenant Pull**
   - **URL**: `https://<portal>/wp-json/ltl-saas/v1/make/tenants`
   - **Method**: GET
   - **Headers**: `X-LTL-SAAS-TOKEN: <token>`
   - **Output**: Array of tenant objects (see Payload Spec)
   - **Retry**: Yes (3 attempts on 5xx/429)

3. **Iterator**
   - **Input**: Array from HTTP GET
   - **Output**: Individual tenant object per iteration
   - **Repeat**: One iteration per active tenant

4. **RSS Reader**
   - **Feed URL**: `{{5.rss_url}}` (from iterator output)
   - **Output**: Latest N items (e.g., top 1 for single post per run)

5. **AI Generator** (Optional, example: OpenAI)
   - **Prompt**: Combine RSS item + language + tone settings
   - **Input**: RSS title + description + `{{5.language}}`, `{{5.tone}}`
   - **Output**: Generated post title + content

6. **WP REST — Create Post**
   - **URL**: `{{5.site_url}}/wp-json/wp/v2/posts`
   - **Method**: POST
   - **Auth**: Basic Auth with `{{5.wp_username}}:{{5.wp_app_password}}`
   - **Payload**: `{ "title": "...", "content": "...", "status": "{{5.publish_mode}}" }`
   - **Output**: Post ID (if successful)

7. **HTTP POST — Run Callback**
   - **URL**: `https://<portal>/wp-json/ltl-saas/v1/run-callback`
   - **Method**: POST
   - **Headers**: `X-LTL-API-Key: <api_key>`, `Content-Type: application/json`
   - **Payload**: Success/failure object (see Payload Spec)
   - **Retry**: Yes (3 attempts on 5xx/429)

8. **Error Handler** (Optional)
   - **Trigger**: Any error in chain above
   - **Action**: Log error, POST callback with `status=failed`
   - **Continue**: Yes (don't break scenario for other tenants)

---

## Payload Specification

### Request: `GET /make/tenants`

```http
GET /wp-json/ltl-saas/v1/make/tenants HTTP/1.1
Host: portal.example.com
X-LTL-SAAS-TOKEN: <your_make_token_here>
```

### Response: Tenant Array

```json
[
  {
    "tenant_id": 123,
    "site_url": "https://customer.de",
    "wp_username": "customer_user",
    "wp_app_password": "<decrypted_app_password>",
    "rss_url": "https://customer.de/feed",
    "language": "de",
    "tone": "professional",
    "publish_mode": "draft",
    "frequency": "weekly",
    "plan": "basic",
    "is_active": true,
    "skip": false,
    "skip_reason": "",
    "posts_used_month": 5,
    "posts_limit_month": 30,
    "posts_remaining": 25,
    "posts_period_start": "2025-12-01"
  },
  {
    "tenant_id": 456,
    "site_url": "https://enterprise.de",
    "wp_username": "enterprise_user",
    "wp_app_password": "<decrypted_app_password>",
    "rss_url": "https://enterprise.de/feed",
    "language": "en",
    "tone": "casual",
    "publish_mode": "publish",
    "frequency": "daily",
    "plan": "studio",
    "is_active": true,
    "skip": false,
    "skip_reason": "",
    "posts_used_month": 150,
    "posts_limit_month": 300,
    "posts_remaining": 150,
    "posts_period_start": "2025-12-01"
  }
]
```

### Request: `POST /run-callback` (Success)

```http
POST /wp-json/ltl-saas/v1/run-callback HTTP/1.1
Host: portal.example.com
X-LTL-API-Key: <your_api_key_here>
Content-Type: application/json

{
  "tenant_id": 123,
  "execution_id": "exec_abc123xyz",
  "status": "success",
  "started_at": "2025-12-18T10:00:00Z",
  "finished_at": "2025-12-18T10:05:30Z",
  "posts_created": 1,
  "error_message": null,
  "attempts": 1,
  "last_http_status": 200,
  "retry_backoff_ms": 0,
  "wp_post_id": 789,
  "rss_item_url": "https://example.com/rss/item/123"
}
```

### Response: Success

```json
{
  "success": true,
  "id": 789,
  "message": "Callback processed. Usage incremented."
}
```

### Request: `POST /run-callback` (Failure)

```http
POST /wp-json/ltl-saas/v1/run-callback HTTP/1.1
Host: portal.example.com
X-LTL-API-Key: <your_api_key_here>
Content-Type: application/json

{
  "tenant_id": 123,
  "execution_id": "exec_def456uvw",
  "status": "failed",
  "started_at": "2025-12-18T10:05:30Z",
  "finished_at": "2025-12-18T10:07:15Z",
  "posts_created": 0,
  "error_message": "RSS parse error: Invalid feed format",
  "attempts": 3,
  "last_http_status": 500,
  "retry_backoff_ms": 2000
}
```

### Response: Failure (Usage Not Incremented)

```json
{
  "success": false,
  "error": "RSS parse error: Invalid feed format",
  "id": null,
  "message": "Callback logged. Usage unchanged."
}
```

---

## Step-by-Step Setup

### 1. Scenario Template

We provide a `.blueprint.json` file (sanitized, no secrets) that includes:
- Module structure (Scheduler, HTTP, Iterator, WP REST, Callbacks)
- Placeholder variables (URL, tokens, headers)
- Error handling branches
- Retry logic on HTTP failures

### 2. Import & Configure

1. Log into your Make.com account
2. **Create → Scenario**
3. **Upload or copy** the provided blueprint JSON
4. **Configure:**
   - Replace `<portal_url>` with your portal domain
   - Replace `<make_token>` and `<api_key>` with credentials from Portal Admin
   - Set Scheduler interval (default: 30 min)
   - Optionally add AI module (OpenAI, Gemini, etc.)

### 3. Test

- Run once manually (Scheduler →)
- Verify `/make/tenants` returns expected tenant array
- Verify WP post created on tenant WordPress
- Verify callback POST received at Portal
- Check Portal logs: `/wp-content/debug.log`

### 4. Deploy

- Enable Scheduler
- Monitor first few runs
- Adjust interval + error handling as needed

---

## Security Best Practices

✅ **DO:**
- Store Make Token + API Key in Make.com Secure Storage (not visible in UI)
- Use HTTPS for all URLs
- Use Basic Auth for tenant WordPress (app passwords, never full password)
- Log errors but never log full credentials
- Rotate tokens/API keys monthly

❌ **DON'T:**
- Hardcode credentials in scenario (use variables/vault)
- Share blueprints with credentials included (use sanitized version)
- Log full request/response bodies (passwords, tokens visible)
- Leave test scenarios running on production

---

## Troubleshooting

### Tenants Not Pulling

- Verify Portal token in Make.com Secure Storage
- Check Portal logs for auth failures
- Ensure HTTPS + SSL cert valid
- Test HTTP manually: `curl -H "X-LTL-SAAS-TOKEN: ..." https://.../make/tenants`

### Posts Not Created

- Verify tenant WP site is accessible
- Check WP app password correct + not expired
- Check WP REST API is enabled
- Verify post status matches WP permissions (draft, publish, etc.)
- Check WP logs: `/wp-content/debug.log` on tenant side

### Callbacks Not Received

- Verify Portal API Key in Make.com Secure Storage
- Check Portal logs for 401/403 errors
- Verify callback URL correct (https://portal/wp-json/ltl-saas/v1/run-callback)
- Ensure Portal HTTPS certificate valid

### Rate Limiting

- If you see HTTP 429, Make.com may be throttled
- Configure retry logic in HTTP modules (3 attempts, exponential backoff)
- Spread Scheduler across different times (stagger multiple scenarios)

---

## Provided Files

1. **ltl-multi-tenant-scenario.blueprint.json**
   - Sanitized template (no secrets, placeholder tokens)
   - Import directly into Make.com
   - Customize for your setup

2. **ltl-multi-tenant-scenario.md** (this file)
   - Full documentation
   - Module setup guide
   - Payload reference
   - Troubleshooting

3. **Sanitizer Script** (`scripts/sanitize_make_blueprints.py`)
   - Removes secrets from any exported blueprint
   - Use before sharing with team/customers

---

## Support

- **Issues**: Check Portal Admin Panel → Settings → Logs
- **Questions**: See `docs/reference/api.md` for full API spec
- **Feedback**: Open issue on GitHub or contact engineering team

---

**Version**: 1.0
**Last Updated**: 2025-12-18
**Maintained by**: LTL AutoBlog Cloud Team
