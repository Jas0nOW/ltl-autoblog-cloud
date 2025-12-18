# SMOKE_TEST_SPRINT_04.md

## Setup
- [ ] Plugin aktiviert
- [ ] Mindestens 1 Test-User mit Settings existiert
- [ ] Make Token ist gesetzt

## Limits
- [ ] Tenant hat posts_this_month = 0 → make/tenants liefert skip=false, remaining=limit
- [ ] Setze posts_this_month = limit → make/tenants liefert skip=true, reason=monthly_limit_reached
- [ ] Wechsel Monat (posts_period_start auf letzten Monat) → make/tenants resettet auf 0

## Callback
- [ ] run-callback “success publish” → posts_this_month +1
- [ ] Wenn Monat gewechselt → callback resettet dann inkrementiert

## Regression
- [ ] active-users gibt KEINE decrypted secrets zurück
- [ ] make/tenants gibt 403 ohne Token
