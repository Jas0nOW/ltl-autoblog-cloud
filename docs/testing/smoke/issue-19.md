# Smoke Test — Issue 19 (Pricing Landing)

> **Objective**: Verify pricing landing page loads, renders, and buttons work correctly.

---

## Test 1: Landing Page Loads (HTTP 200)

**Command**:
```bash
curl -I https://yourdomain/preise
```

**Expected**:
```
HTTP/2 200
Content-Type: text/html
```

**Verification**:
- ✅ Status 200 (not 404, 500, etc.)
- ✅ Page loads in browser (< 2 seconds)

---

## Test 2: Shortcode Renders (No Errors)

**Setup**:
1. Log out (or use incognito browser)
2. Open https://yourdomain/preise

**Expected to see**:
- ✅ Hero headline: "Schreibe automatisch Blogposts mit KI" or "Automatically Write Blog Posts with AI"
- ✅ 4 benefit boxes (✓ Zeit sparen, etc.)
- ✅ 3 plan cards (Starter, Pro, Agency)
- ✅ CTA section at bottom

**No errors**:
- ✅ No PHP warnings in `wp-content/debug.log`
- ✅ No JavaScript console errors (F12)
- ✅ No blank/white screen

---

## Test 3: Checkout Buttons Link Correctly

**Setup**:
1. Admin Panel → LTL AutoBlog Cloud → Marketing (Pricing Landing)
2. Set checkout URLs:
   - Starter: `https://gumroad.com/checkout/starter`
   - Pro: `https://gumroad.com/checkout/pro`
   - Agency: `mailto:contact@lazytechlab.de`
3. Save
4. Open pricing page (incognito)

**Test**:
1. Click "Get Started" on Starter card
   - Expected: Browser navigates to `https://gumroad.com/checkout/starter` ✓
2. Click "Get Started" on Pro card
   - Expected: Browser navigates to `https://gumroad.com/checkout/pro` ✓
3. Click "Contact Us" on Agency card
   - Expected: Email client opens with `contact@lazytechlab.de` ✓

**Verify in Browser DevTools**:
- F12 → Network Tab
- Click button → Check "Location" header in response

---

## Test 4: Responsive Layout (Desktop)

**Setup**:
- Open https://yourdomain/preise on desktop (1920x1080)

**Expected**:
- ✅ Hero section full width with background
- ✅ Plans displayed in 3 columns (grid)
- ✅ Pro plan card highlighted (centered, slightly larger)
- ✅ All text readable (font-size > 14px)
- ✅ Buttons clickable (min 44px height)

**No Layout Issues**:
- ✅ No horizontal scroll
- ✅ No overlapping text
- ✅ No cut-off content

---

## Test 5: Responsive Layout (Tablet)

**Setup**:
- Chrome DevTools (F12) → Toggle Device Toolbar → iPad (768x1024)

**Expected**:
- ✅ Plans displayed in 2 columns (or responsive grid)
- ✅ All content readable
- ✅ Buttons clickable (touch-friendly)

**Verify**:
- ✅ No layout breaks
- ✅ No horizontal scroll

---

## Test 6: Responsive Layout (Mobile)

**Setup**:
- Chrome DevTools → Toggle Device Toolbar → iPhone 12 (390x844)

**Expected**:
- ✅ Plans stacked vertically (1 column)
- ✅ Hero text centered and readable
- ✅ Buttons full-width or touch-friendly (> 44px)

**Verify**:
- ✅ No horizontal scroll
- ✅ No overlapping elements

---

## Test 7: Bilingual Support (English)

**Setup**:
1. Create another WordPress page (test)
2. Add shortcode: `[ltl_saas_pricing lang="en"]`
3. Open that page

**Expected**:
- ✅ Headlines in English: "Automatically Write Blog Posts with AI"
- ✅ Benefits in English: "Save Time", "SEO & Traffic", etc.
- ✅ Plan details in English
- ✅ Buttons: "Get Started", "Contact Us"

**Verify**:
- ✅ No German text mixed in
- ✅ All text properly translatedрок

---

## Test 8: Bilingual Support (German Default)

**Setup**:
1. Open page with shortcode `[ltl_saas_pricing]` (no lang param)

**Expected**:
- ✅ Defaults to German
- ✅ All text in German

**Verify**:
- ✅ "Schreibe automatisch Blogposts mit KI"
- ✅ "Starten", not "Get Started"

---

## Test 9: Missing Checkout URLs (Graceful Fallback)

**Setup**:
1. Admin: Remove all checkout URLs
2. Save
3. Open pricing page

**Expected**:
- ✅ Page still loads (no error)
- ✅ Buttons still visible (but may be disabled or show alt text)
- ✅ No PHP errors

**Acceptable**:
- Buttons show but are inactive OR
- Buttons display alternate text (e.g., "Coming Soon")
- No crash or white screen

---

## Test 10: Admin Settings Validation

**Setup**:
1. Admin Panel → LTL AutoBlog Cloud → Marketing
2. Try to save invalid URL (not https://):
   - Example: `http://unsecured.com` or `ftp://...`

**Expected**:
- ✅ Invalid URL rejected or sanitized to blank
- ✅ Valid URL saved
- ✅ No PHP warnings

**Verify**:
- ✅ Setting saved correctly
- ✅ Page still renders after save

---

## Test 11: SEO Basics

**Setup**:
- Open pricing page in browser

**Expected Meta**:
- ✅ Page title (appears in browser tab)
- ✅ H1 heading (hero section)
- ✅ No duplicate H1 tags

**Verify**:
```bash
# Browser → Right-click → View Page Source
# Search for <h1, <title, <meta
```

---

## Test 12: Loading Performance

**Setup**:
- Chrome DevTools → Performance Tab
- Throttle: Fast 3G
- Reload page

**Expected**:
- ✅ Page loads in < 3 seconds
- ✅ Hero section visible within 1 second (LCP < 1s ideal)
- ✅ No layout shift (CLS < 0.1)

**Verify**:
- Open DevTools → Performance → Record → Reload → Stop
- Check: LCP, FID, CLS metrics

---

## Test 13: Incognito Mode (No Authentication)

**Setup**:
- Open pricing page in incognito mode (no cookies, no cache)

**Expected**:
- ✅ Page loads normally
- ✅ No login prompt
- ✅ No "access denied"

**Verify**:
- ✅ Same content as logged-in users

---

## Test 14: Print Layout

**Setup**:
- Open pricing page
- Press Ctrl+P (or Cmd+P)
- Print preview

**Expected**:
- ✅ Content readable in print preview
- ✅ No extra background colors bleeding
- ✅ Button styles not breaking (alt text visible)

**Acceptable**:
- Buttons may not be clickable in print (expected)
- Print stylesheet working or browser defaults OK

---

## Smoke Test Checklist

- [ ] **Test 1**: Landing loads (HTTP 200) ✓
- [ ] **Test 2**: Shortcode renders (no errors) ✓
- [ ] **Test 3**: Checkout buttons link correctly ✓
- [ ] **Test 4**: Desktop layout OK ✓
- [ ] **Test 5**: Tablet layout OK ✓
- [ ] **Test 6**: Mobile layout OK ✓
- [ ] **Test 7**: English version works ✓
- [ ] **Test 8**: German default works ✓
- [ ] **Test 9**: Missing URLs (graceful fallback) ✓
- [ ] **Test 10**: Admin settings validation ✓
- [ ] **Test 11**: SEO basics ✓
- [ ] **Test 12**: Loading performance ✓
- [ ] **Test 13**: Incognito mode works ✓
- [ ] **Test 14**: Print layout OK ✓

---

## Known Issues / TODOs

| Issue | Severity | Status |
|-------|----------|--------|
| — | — | — |

(Add any issues found during testing above)

---

## Cleanup

No cleanup needed (no test data to remove).

---

## Next Steps

1. ✅ All 14 tests passing
2. ✅ Commit code
3. ✅ Create PR with `Closes #19`
