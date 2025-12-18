# Gumroad Billing Integration

> **Goal**: Automatically activate tenant accounts and assign plans when Gumroad sends payment confirmations.

> **Note**: This is one of two supported payment providers. LTL AutoBlog Cloud also supports **Stripe** (via custom checkout on your own landing page). See `docs/billing/stripe.md` for Stripe setup.

---

## Overview

The LTL AutoBlog Cloud Portal integrates with Gumroad's "Ping endpoint" feature to:
1. Create user accounts automatically for new customers
2. Activate subscriptions and assign pricing plans
3. Deactivate accounts when refunds are issued (support-friendly)

---

## Setup: Gumroad Webhook Endpoint

> **Note (Issue #7)**: As of v0.2.0, the recommended endpoint is `/gumroad/webhook`. The legacy `/gumroad/ping` remains supported for backward compatibility but will be deprecated.

### Step 1: Generate Gumroad Secret

1. Log in to the LTL AutoBlog Cloud Portal as Admin
2. Navigate to **LTL AutoBlog Cloud** → **Billing (Gumroad)**
3. Click **"Generate new secret"**
4. Copy the generated secret (shown only once)

### Step 2: Configure Product-ID → Plan Mapping

In the **"Product-ID → Plan Mapping"** textarea, enter a JSON mapping:

```json
{
   "prod_ABC123": "basic",
  "prod_DEF456": "pro",
   "prod_GHI789": "studio"
}
```

Replace the product IDs with your actual Gumroad product IDs and assign appropriate plan names (`basic`, `pro`, `studio`).

> **Note**: Legacy values like `starter` and `agency` are still accepted for backward compatibility and will be normalized to `basic` / `studio` internally.

Click **"Validate JSON"** to ensure the format is correct before saving.

### Step 3: Configure Gumroad Webhook

1. Log in to **Gumroad** (www.gumroad.com)
2. Go to **Products** → select your product
3. Scroll to **Webhooks** or **Ping endpoint**
4. Paste the Webhook URL from the LTL Admin panel:
   ```
   https://YOURDOMAIN/wp-json/ltl-saas/v1/gumroad/webhook?secret=YOUR_SECRET
   ```
   Or use the legacy endpoint (still supported):
   ```
   https://YOURDOMAIN/wp-json/ltl-saas/v1/gumroad/ping?secret=YOUR_SECRET
   ```
5. Save

> **Important**: Use `https://` (HTTPS required). If you're behind a proxy, see [Proxy SSL Setup](proxy-ssl.md).

---

## How It Works

### Incoming Ping Payload

When a customer purchases or upgrades on Gumroad, it sends a POST request with:

- **email** (string): Customer email
- **product_id** (string): Gumroad product identifier
- **subscription_id** (string, optional): ID for subscriptions
- **recurrence** (string, optional): e.g., "monthly", "yearly"
- **refunded** (string): "true" or "false"
- **sale_id** (string, optional): Transaction ID

### Processing Logic

1. **Verify Secret**: Compares `?secret=` parameter with stored `ltl_saas_gumroad_secret`
   - ❌ Mismatch → HTTP 403 (Forbidden)
   - ✅ Match → Continue

2. **Find or Create User**:
   - Look up user by email
   - If not found: Create new user with username derived from email, random password, and send welcome email

3. **Assign Plan**:
   - Look up `product_id` in Product-ID → Plan mapping
   - If not found: Assign default plan (currently `basic`)

> **Free plan**: The `free` tier is meant as “start without Gumroad” (e.g., via normal WordPress registration). If you want to provision `free` via Gumroad anyway, you can map a product_id to `free`.

4. **Activate/Deactivate**:
   - Normal purchase: Set `is_active = 1`
   - Refunded: Set `is_active = 0` and log `deactivated_reason = 'refunded'`

5. **Store Metadata**:
   - Save `gumroad_subscription_id` as user meta (for future updates)

6. **Response**: Always return HTTP 200 with `{ ok: true }` within 2 seconds
   - Gumroad may retry if it times out

---

## Testing Locally

### Send Test Ping (via curl)

```bash
curl -X POST \
  "https://YOURDOMAIN/wp-json/ltl-saas/v1/gumroad/ping?secret=YOUR_SECRET" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=testcustomer@example.com&product_id=prod_ABC123&refunded=false"
```

Expected response:
```json
{
  "ok": true
}
```

### Expected Results

| Scenario | User | Plan | is_active | Email Sent |
|----------|------|------|-----------|-----------|
| New customer, valid secret | Created | From mapping | 1 | ✓ Welcome |
| Existing customer, plan update | —found— | Updated | 1 | ✗ |
| Customer refund | —found— | — | 0 | ✗ |
| Wrong secret | ✗ | — | — | HTTP 403 |
| Missing email | — | — | — | HTTP 200 (no-op) |

---

## Checking Logs

WordPress logs are in `wp-content/debug.log` if `WP_DEBUG` is enabled:

```
[timestamp] [LTL-SAAS] Gumroad ping: new user created: user_id=123
[timestamp] [LTL-SAAS] Gumroad ping: plan updated to 'pro' for user_id=123
[timestamp] [LTL-SAAS] Gumroad ping: deactivated user_id=123 (refunded)
```

> **Note**: Secrets are never logged. Only product_ids and email domains may appear (sanitized).

---

## Troubleshooting

### "Wrong secret → 403 Forbidden"
- Verify you copied the secret correctly (last 4 chars shown in admin panel)
- Check that you're using `?secret=XXX` in the Ping URL
- Make sure HTTPS is enabled

### "No user created / account not activated"
- Check WordPress debug logs for errors
- Verify the Product-ID → Plan Mapping is valid JSON
- Ensure the email in Gumroad is a valid email address

### "Email not sent to customer"
- Check WordPress mail settings (`wp-config.php`)
- See if the server blocks outgoing SMTP
- Review the welcome email template in the code

### "Ping endpoint not working"
- Confirm HTTPS is active (required for security)
- If behind a proxy, follow [Proxy SSL Setup](proxy-ssl.md)
- Test with a simpler curl command first

---

## FAQ

**Q: Can I test without real Gumroad transactions?**
A: Yes, use `curl` to send test pings manually (see Testing section above).

**Q: What if the same product is purchased twice?**
A: The system is idempotent. Repeated pings for the same email update the plan cleanly without duplicates.

**Q: Is the password visible in logs?**
A: No. Passwords are only printed in the welcome email sent directly to the customer.

**Q: Can I change the plan mapping later?**
A: Yes, edit the JSON in the admin panel anytime. New pings will use the updated mapping.

---

## Support

For issues or questions, contact support@lazytech.com or check the [RELEASE_CHECKLIST.md](RELEASE_CHECKLIST.md) for deployment tips.
