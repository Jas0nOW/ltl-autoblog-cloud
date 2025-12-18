# SMOKE_TEST_SPRINT_07.md (Template)

## Endpoint Security
- [ ] POST /gumroad/ping ohne secret → 403
- [ ] POST /gumroad/ping mit falschem secret → 403
- [ ] POST /gumroad/ping mit richtigem secret → 200

## Provisioning
- [ ] Neuer Käufer (Email existiert nicht) → WP User wird erstellt, is_active=1, plan gesetzt
- [ ] Existierender Käufer → plan wird aktualisiert, kein Duplicate

## Refund
- [ ] refunded=true → is_active=0

## Docs
- [ ] docs/billing-gumroad.md vorhanden
