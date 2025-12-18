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

---

## Beispiel-HTTP-Tests (curl)

1. **make/tenants (normal, Token OK)**
	```sh
	curl -H "Authorization: Bearer <MAKE_TOKEN>" https://<site>/wp-json/ltl-saas/v1/make/tenants
	```

2. **make/tenants (ohne Token, erwartet 403)**
	```sh
	curl -i https://<site>/wp-json/ltl-saas/v1/make/tenants
	```

3. **run-callback (publish success, inkrementiert Zähler)**
	```sh
	curl -X POST -H "Authorization: Bearer <MAKE_TOKEN>" -H "Content-Type: application/json" \
	  -d '{"tenant_id":"demo","event":"publish_success"}' \
	  https://<site>/wp-json/ltl-saas/v1/make/run-callback
	```

4. **active-users (prüfen, dass keine Secrets im Klartext)**
	```sh
	curl -H "Authorization: Bearer <MAKE_TOKEN>" https://<site>/wp-json/ltl-saas/v1/make/active-users
	```

5. **Monatswechsel simulieren (posts_period_start manuell setzen, dann make/tenants)**
	*(DB-Änderung nötig, dann:)*
	```sh
	curl -H "Authorization: Bearer <MAKE_TOKEN>" https://<site>/wp-json/ltl-saas/v1/make/tenants
	```
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
