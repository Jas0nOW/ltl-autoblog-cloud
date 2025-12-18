# Smoke Test: Gumroad Webhook Integration (Sprint 07 – Issue #7)

> **Objective (Issue #7)**: Verify Gumroad webhook endpoint `/gumroad/webhook` processes all scenarios correctly: authentication, user creation, plan assignment, and refunds. Legacy `/gumroad/ping` endpoint also tested for backward compatibility.

---

## Prerequisites

- Admin access to LTL AutoBlog Cloud Portal
- Gumroad Secret configured (see [Billing Gumroad Setup](billing-gumroad.md))
- Product-ID → Plan mapping configured (JSON valid)
- HTTPS enabled (local: self-signed OK, can use `--insecure` with curl)
- Access to `wp-content/debug.log` to verify webhook logging

---

## Test Case 1: Wrong Secret → HTTP 403 (New Endpoint)

**Objective**: Verify that invalid/missing secrets are rejected on `/gumroad/webhook`.

### Command
```bash
curl -X POST \
  "https://YOURDOMAIN/wp-json/ltl-saas/v1/gumroad/webhook?secret=WRONG_SECRET" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=test1@example.com&product_id=prod_ABC123&refunded=false" \
  --insecure  # For self-signed certificates
```

### Expected Result
```
HTTP 403 Forbidden
{ "error": "Forbidden" }
```

### Verification
- No user created in WordPress
- No entry in `wp_ltl_saas_settings` table
- Log entry: `[LTL-SAAS] Gumroad webhook: Secret mismatch or missing`

---

## Test Case 2: Valid Secret + New Email → User Created + Active (New Endpoint)

**Objective**: Verify new user account is created with correct plan on `/gumroad/webhook`.

### Setup
- Ensure `product_ABC123` maps to `starter` in admin panel
- Replace `YOUR_SECRET` with actual secret from admin

### Command
```bash
curl -X POST \
  "https://YOURDOMAIN/wp-json/ltl-saas/v1/gumroad/ping?secret=YOUR_SECRET" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=newcustomer@example.com&product_id=prod_ABC123&refunded=false&subscription_id=sub_test123" \
  --insecure
```

### Expected Result
```
HTTP 200 OK
{ "ok": true }
```

### Verification
1. ✅ New WordPress user created (username derived from email)
2. ✅ User has role `subscriber`
3. ✅ Entry in `wp_ltl_saas_settings` with:
   - `plan = 'starter'`
   - `is_active = 1`
   - `posts_this_month = 0`
4. ✅ User meta `gumroad_subscription_id = sub_test123`
5. ✅ Welcome email sent to `newcustomer@example.com`
   - Subject: "Willkommen bei LTL AutoBlog Cloud!"
   - Contains: login URL, username, temporary password

### SQL Verification
```sql
SELECT * FROM wp_users WHERE user_email = 'newcustomer@example.com';
SELECT * FROM wp_ltl_saas_settings WHERE user_id = <user_id>;
SELECT meta_key, meta_value FROM wp_usermeta WHERE user_id = <user_id> AND meta_key = 'gumroad_subscription_id';
```

---

## Test Case 3: Valid Secret + Existing User → Plan Updated (No Duplicate)

**Objective**: Verify repeated pings for same email update plan cleanly.

### Setup
- Use email from Test Case 2 (`newcustomer@example.com`)
- Change `product_id` to `prod_PRO456` (maps to `pro` plan)

### Command
```bash
curl -X POST \
  "https://YOURDOMAIN/wp-json/ltl-saas/v1/gumroad/ping?secret=YOUR_SECRET" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=newcustomer@example.com&product_id=prod_PRO456&refunded=false&subscription_id=sub_test456" \
  --insecure
```

### Expected Result
```
HTTP 200 OK
{ "ok": true }
```

### Verification
1. ✅ **No new user** created (same email → same user_id)
2. ✅ Plan updated in `wp_ltl_saas_settings`:
   - `plan = 'pro'` (changed from `starter`)
   - `is_active = 1` (remains active)
   - `posts_this_month = 0` (unchanged)
3. ✅ User meta `gumroad_subscription_id` updated to `sub_test456`
4. ✅ No duplicate rows in settings table

### SQL Verification
```sql
-- Should return exactly 1 row
SELECT COUNT(*) FROM wp_ltl_saas_settings WHERE user_id = <user_id>;

-- Should show plan = 'pro'
SELECT plan, is_active FROM wp_ltl_saas_settings
WHERE user_id = <user_id>;
```

---

## Test Case 4: Refunded=true → is_active=0 (Deactivate)

**Objective**: Verify that refunded=true deactivates the account (support-friendly, no delete).

### Setup
- Use email from Test Case 2 (already created)
- Set `refunded=true` to trigger deactivation

### Command
```bash
curl -X POST \
  "https://YOURDOMAIN/wp-json/ltl-saas/v1/gumroad/ping?secret=YOUR_SECRET" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=newcustomer@example.com&product_id=prod_ABC123&refunded=true" \
  --insecure
```

### Expected Result
```
HTTP 200 OK
{ "ok": true }
```

### Verification
1. ✅ **User NOT deleted** (account preserved for support)
2. ✅ Settings updated:
   - `is_active = 0` (deactivated)
   - `plan` remains unchanged
3. ✅ User cannot access dashboard (checked in Test Case 5)

### SQL Verification
```sql
-- User still exists
SELECT ID, user_email FROM wp_users WHERE user_email = 'newcustomer@example.com';

-- But is_active is now 0
SELECT is_active FROM wp_ltl_saas_settings WHERE user_id = <user_id>;
```

---

## Test Case 5: Inactive User Cannot Save Settings

**Objective**: Verify that deactivated users cannot modify their settings (access control).

### Setup
- Deactivate a user (see Test Case 4)
- Log in as that user

### Procedure
1. Log in to the portal with the deactivated user
2. Try to save new settings (RSS URL, language, etc.)
3. Expected: Error message "Account inaktiv. Einstellungen können nicht gespeichert werden."

### Verification
- ✅ No settings updated
- ✅ Error notice displayed

---

## Test Case 6: Idempotent Refund (Repeated Refund Ping)

**Objective**: Verify that repeated refund pings don't break anything.

### Setup
- User already deactivated from Test Case 4
- Send same refund ping again

### Command
```bash
curl -X POST \
  "https://YOURDOMAIN/wp-json/ltl-saas/v1/gumroad/ping?secret=YOUR_SECRET" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=newcustomer@example.com&product_id=prod_ABC123&refunded=true" \
  --insecure
```

### Expected Result
```
HTTP 200 OK
{ "ok": true }
```

### Verification
1. ✅ No errors in response
2. ✅ `is_active` remains 0
3. ✅ No duplicate database entries

---

## Test Case 7: Missing Required Field (email) → No-op

**Objective**: Verify graceful handling of malformed requests.

### Command
```bash
curl -X POST \
  "https://YOURDOMAIN/wp-json/ltl-saas/v1/gumroad/ping?secret=YOUR_SECRET" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "product_id=prod_ABC123&refunded=false" \
  --insecure
```

### Expected Result
```
HTTP 200 OK
{ "ok": true }
```

### Verification
- ✅ No user created
- ✅ No error (quick response)
- ✅ Error logged in WordPress debug log

---

## Test Case 8: Header Secret (Alternative to Query Param)

**Objective**: Verify secret can also be passed via HTTP header.

### Command
```bash
curl -X POST \
  "https://YOURDOMAIN/wp-json/ltl-saas/v1/gumroad/ping" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "X-Gumroad-Secret: YOUR_SECRET" \
  -d "email=header_test@example.com&product_id=prod_ABC123&refunded=false" \
  --insecure
```

### Expected Result
```
HTTP 200 OK
{ "ok": true }
```

### Verification
- ✅ User created successfully
- ✅ Query param `?secret=` not required

---

## Test Case 9: Unmapped Product-ID → Default Plan

**Objective**: Verify fallback behavior when product_id is not in mapping.

### Setup
- Use a product_id not in the mapping (e.g., `prod_UNKNOWN`)

### Command
```bash
curl -X POST \
  "https://YOURDOMAIN/wp-json/ltl-saas/v1/gumroad/ping?secret=YOUR_SECRET" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=unmapped_product@example.com&product_id=prod_UNKNOWN&refunded=false" \
  --insecure
```

### Expected Result
```
HTTP 200 OK
{ "ok": true }
```

### Verification
- ✅ User created with default plan (`starter`)
- ✅ Warning/notice logged in debug log

---

## Test Case 10: SSL Not Available (HTTP Instead of HTTPS) → 403

**Objective**: Verify HTTPS enforcement.

### Command (simulate non-SSL)
```bash
# Only works if your local setup can simulate this
# In production, this MUST return 403
curl -X POST \
  "http://YOURDOMAIN/wp-json/ltl-saas/v1/gumroad/ping?secret=YOUR_SECRET" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=test@example.com&product_id=prod_ABC123&refunded=false"
```

### Expected Result
```
HTTP 403 Forbidden
{ "error": "HTTPS required" }
```

---

## Checklist

- [ ] Test 1: Wrong secret → 403 ✓
- [ ] Test 2: New user created ✓
- [ ] Test 3: Plan updated (idempotent) ✓
- [ ] Test 4: Refund deactivates ✓
- [ ] Test 5: Inactive user blocked ✓
- [ ] Test 6: Repeated refund safe ✓
- [ ] Test 7: Missing email handled ✓
- [ ] Test 8: Header secret works ✓
- [ ] Test 9: Unmapped product → default plan ✓
- [ ] Test 10: HTTPS enforced ✓

---

## Backward Compatibility: Legacy `/gumroad/ping` Endpoint

**Issue #7 Note**: The new `/gumroad/webhook` endpoint is now recommended. The legacy `/gumroad/ping` endpoint is aliased to the same handler for backward compatibility.

### Test: Both Endpoints Produce Identical Results

**Setup**:
1. Record a baseline user state: `SELECT COUNT(*) FROM wp_users;`
2. Prepare webhook payload:
```json
{
  "email": "backcompat-test@example.com",
  "product_id": "prod_ABC123",
  "refunded": false,
  "subscription_id": "sub_TEST123"
}
```

**Test New Endpoint (/webhook)**:
```bash
curl -X POST \
  "https://YOURDOMAIN/wp-json/ltl-saas/v1/gumroad/webhook?secret=YOUR_SECRET" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=webhook-test@example.com&product_id=prod_ABC123&refunded=false" \
  --insecure
```
**Expected**: HTTP 200, user created

**Test Legacy Endpoint (/ping)**:
```bash
curl -X POST \
  "https://YOURDOMAIN/wp-json/ltl-saas/v1/gumroad/ping?secret=YOUR_SECRET" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=ping-test@example.com&product_id=prod_ABC123&refunded=false" \
  --insecure
```
**Expected**: HTTP 200, user created (identical behavior)

**Verification**:
- Both `/webhook` and `/ping` process events identically
- No breaking changes for existing Gumroad configurations
- Logs show both routes working:
  - `[LTL-SAAS] Gumroad webhook: new user created, plan=...`

---

## Cleanup

After smoke tests, optionally delete test users:

```bash
# Find test user IDs
SELECT ID FROM wp_users WHERE user_email LIKE '%@example.com%';

# Delete via WordPress (or keep for manual verification)
wp user delete <ID> --allow-root
```

---

## Next Steps

1. ✅ Run all test cases above (including backward compatibility)
2. ✅ Verify debug logs contain no sensitive data
3. ✅ Review admin panel billing section
4. ✅ Verify both `/webhook` and `/ping` routes work
5. ✅ Commit changes (`git commit -m "fix(billing): add /gumroad/webhook route alias (Issue #7)"`)

5. ✅ Create PR with `Closes #17`
