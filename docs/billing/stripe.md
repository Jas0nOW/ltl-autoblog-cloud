# Stripe Billing Integration

> **Goal**: Automatically activate tenant accounts and assign plans when Stripe sends payment confirmations via webhook.

> **Note**: This is the **primary payment provider** for LTL AutoBlog Cloud. For the alternative (Gumroad), see `docs/billing/gumroad.md`.

---

## Overview

The LTL AutoBlog Cloud Portal integrates with Stripe's webhook system to:
1. Create user accounts automatically for new customers
2. Activate subscriptions and assign pricing plans
3. Handle subscription updates (upgrades, downgrades)
4. Deactivate accounts when subscriptions are canceled or fail

**Why Stripe?**
- Full branding control (checkout on your own domain)
- Lower fees than marketplace platforms
- Better customer data ownership
- SEPA, credit card, and multiple payment methods
- Professional invoicing and tax handling

---

## Setup: Stripe Webhook Endpoint

### Step 1: Create Stripe Secret in Portal

1. Log in to the LTL AutoBlog Cloud Portal as Admin
2. Navigate to **LTL AutoBlog Cloud** → **Billing (Stripe)**
3. Click **"Generate new Stripe secret"**
4. Copy the generated secret (shown only once)

> **Note**: This secret is different from your Stripe API keys. It's used to verify webhook signatures.

### Step 2: Configure Product-ID → Plan Mapping

In the **"Stripe Product-ID → Plan Mapping"** textarea, enter a JSON mapping:

```json
{
  "prod_stripe_free": "free",
  "prod_stripe_basic": "basic",
  "prod_stripe_pro": "pro",
  "prod_stripe_studio": "studio"
}
```

Replace the product IDs with your actual Stripe product IDs (found in Stripe Dashboard → Products).

Click **"Validate JSON"** to ensure the format is correct before saving.

### Step 3: Configure Stripe Webhook

1. Log in to **Stripe Dashboard** (dashboard.stripe.com)
2. Go to **Developers** → **Webhooks**
3. Click **"Add endpoint"**
4. Paste the Webhook URL from the LTL Admin panel:
   ```
   https://YOURDOMAIN/wp-json/ltl-saas/v1/stripe/webhook
   ```
5. Select events to listen to:
   - `checkout.session.completed` (new purchase)
   - `customer.subscription.created` (subscription started)
   - `customer.subscription.updated` (plan change)
   - `customer.subscription.deleted` (cancellation)
   - `invoice.payment_succeeded` (recurring payment)
   - `invoice.payment_failed` (failed payment)
6. Click **"Add endpoint"**
7. Copy the **Signing secret** (starts with `whsec_...`)
8. Paste it into the Portal Admin panel → **Stripe Webhook Secret** field
9. Save

> **Important**: Use `https://` (HTTPS required). If you're behind a proxy, see [Proxy SSL Setup](../ops/proxy-ssl.md).

---

## How It Works

### Incoming Webhook Events

When a customer purchases or manages their subscription on Stripe, it sends POST requests with:

**Common Fields:**
- **type** (string): Event type (e.g., `checkout.session.completed`)
- **data.object** (object): Event data (customer, subscription, invoice, etc.)
- **created** (timestamp): Event creation time

**Key Objects:**
- **Customer**: Email, name, Stripe customer ID
- **Subscription**: Plan ID, status (active, canceled, past_due)
- **Invoice**: Amount, payment status

### Processing Logic

1. **Verify Signature**: Validates `Stripe-Signature` header using webhook secret
   - ❌ Invalid → HTTP 400 (Bad Request)
   - ✅ Valid → Continue

2. **Event Routing**:
   - `checkout.session.completed` → Create/activate user + assign plan
   - `customer.subscription.updated` → Update plan (upgrade/downgrade)
   - `customer.subscription.deleted` → Deactivate user (set `is_active = 0`)
   - `invoice.payment_failed` → Log warning, keep active for grace period
   - `invoice.payment_succeeded` → Reactivate if was past_due

3. **Find or Create User**:
   - Look up user by email (from Stripe customer object)
   - If not found: Create new user with username derived from email, random password, and send welcome email

4. **Assign Plan**:
   - Look up `product_id` or `price_id` in Product-ID → Plan mapping
   - If not found: Assign default plan (`basic`)
   - Normalize plan name (e.g., `starter` → `basic`)

5. **Activate/Deactivate**:
   - Active subscription: Set `is_active = 1`
   - Canceled/expired: Set `is_active = 0` and log `deactivated_reason = 'subscription_canceled'`

6. **Store Metadata**:
   - Save `stripe_customer_id` and `stripe_subscription_id` as user meta (for future updates)

7. **Response**: Always return HTTP 200 with `{ "received": true }` within 5 seconds
   - Stripe will retry if it times out

---

## Testing Locally

### Option 1: Stripe CLI (Recommended)

Install the [Stripe CLI](https://stripe.com/docs/stripe-cli):

```bash
# Forward webhooks to local environment
stripe listen --forward-to https://localhost/wp-json/ltl-saas/v1/stripe/webhook

# Trigger test events
stripe trigger checkout.session.completed
stripe trigger customer.subscription.created
stripe trigger customer.subscription.deleted
```

### Option 2: Manual Test (via Stripe Dashboard)

1. Go to Stripe Dashboard → **Developers** → **Webhooks** → Your endpoint
2. Click **"Send test webhook"**
3. Select event type (e.g., `checkout.session.completed`)
4. Click **"Send test webhook"**
5. Check WordPress debug logs for processing

Expected response:
```json
{
  "received": true
}
```

### Expected Results

| Scenario | User | Plan | is_active | Email Sent |
|----------|------|------|-----------|-----------|
| New checkout (basic plan) | Created | basic | 1 | ✓ Welcome |
| Subscription updated (upgrade) | —found— | pro | 1 | ✗ |
| Subscription canceled | —found— | — | 0 | ✗ |
| Payment failed | —found— | — | 1* | ⚠️ Warning |
| Invalid signature | ✗ | — | — | HTTP 400 |

*Grace period: User stays active for 1 billing cycle after failed payment.

---

## Checking Logs

WordPress logs are in `wp-content/debug.log` if `WP_DEBUG` is enabled:

```
[timestamp] [LTL-SAAS] Stripe webhook: checkout.session.completed, new user created: user_id=123
[timestamp] [LTL-SAAS] Stripe webhook: subscription updated to 'pro' for user_id=123
[timestamp] [LTL-SAAS] Stripe webhook: subscription canceled, user_id=123 deactivated
[timestamp] [LTL-SAAS] Stripe webhook: payment failed for user_id=123, keeping active (grace period)
```

> **Note**: Secrets and card details are never logged. Only sanitized event types and user IDs appear.

---

## Troubleshooting

### "Invalid signature → 400 Bad Request"
- Verify you copied the correct **Signing secret** from Stripe (starts with `whsec_...`)
- Check that you're using the webhook secret (not your Stripe API keys)
- Ensure HTTPS is enabled

### "No user created / account not activated"
- Check WordPress debug logs for errors
- Verify the Product-ID → Plan Mapping is valid JSON
- Ensure the customer email in Stripe is valid
- Check that webhook events are being sent (Stripe Dashboard → Webhooks → Logs)

### "Email not sent to customer"
- Check WordPress mail settings (`wp-config.php`)
- Verify SMTP is configured (consider using WP Mail SMTP plugin)
- Review the welcome email template in the code
- Check spam folder

### "Webhook timeouts (502/504 errors)"
- Optimize webhook handler (ensure < 5 seconds response time)
- Check for slow database queries
- Consider using Stripe webhook retry logic (automatic)

### "Test mode vs Live mode confusion"
- Stripe has separate webhooks for **Test mode** and **Live mode**
- Use test product IDs during development
- Switch to live product IDs and live webhook for production

---

## Security Best Practices

1. **Always verify webhook signatures** (handled automatically by our integration)
2. **Use HTTPS only** (HTTP webhooks will be rejected)
3. **Keep webhook secrets safe** (never commit to Git)
4. **Monitor failed webhooks** (Stripe Dashboard → Webhooks → Logs)
5. **Implement idempotency** (handle duplicate events gracefully)
6. **Rate limit webhook endpoint** (prevent abuse)

---

## Stripe vs Gumroad: Which to Use?

| Feature | Stripe | Gumroad |
|---------|--------|---------|
| **Branding** | Full control (own checkout) | Gumroad branding |
| **Fees** | 1.5% + €0.25 (EU) | 10% + payment fees |
| **Setup** | More complex (webhooks, products) | Quick setup (paste link) |
| **Customer Data** | Full ownership | Limited access |
| **Payment Methods** | Cards, SEPA, Wallets, etc. | Cards, PayPal |
| **Invoicing** | Automatic (Stripe Billing) | Basic invoices |
| **Recommended For** | Professional SaaS, own brand | MVPs, quick validation |

**Our recommendation**: Use **Stripe** for production. Use Gumroad for early testing or as a fallback option.

---

## Implementation Checklist

- [ ] Install Stripe PHP SDK (if not using WordPress native HTTP)
- [ ] Generate Stripe webhook secret in Portal Admin
- [ ] Create products in Stripe Dashboard
- [ ] Map Stripe product IDs to plans in Portal Admin
- [ ] Create webhook endpoint in Stripe Dashboard
- [ ] Copy signing secret to Portal Admin
- [ ] Test with Stripe CLI or test mode
- [ ] Verify user creation and plan assignment
- [ ] Check welcome emails are sent
- [ ] Monitor webhook logs in Stripe Dashboard
- [ ] Switch to live mode for production
- [ ] Set up monitoring/alerts for failed webhooks

---

## Related Documentation

- [Gumroad Billing Integration](gumroad.md) (alternative payment provider)
- [Pricing Plans](../product/pricing-plans.md) (plan structure and limits)
- [API Reference](../reference/api.md) (webhook endpoint details)
- [Onboarding Guide](../product/onboarding-detailed.md) (customer-facing setup)

---

## Support

For Stripe-specific issues:
- [Stripe Documentation](https://stripe.com/docs)
- [Stripe Support](https://support.stripe.com)

For LTL AutoBlog Cloud issues:
- Email: `support@lazytechlab.de`
- Docs: `https://portal.lazytechlab.de/docs`
