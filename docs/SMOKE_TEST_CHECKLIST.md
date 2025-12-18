# SMOKE_TEST_CHECKLIST.md

## Crypto
- [ ] Neue Verschlüsselung (v1) speichert `v1:<iv>:<cipher>:<hmac>`
- [ ] Alte gespeicherte Werte (Legacy) lassen sich noch decrypten
- [ ] Manipulierte Ciphertexte werden abgewiesen (HMAC fail)

## REST
- [ ] `/active-users` (falls vorhanden) gibt **kein** decrypted password zurück
- [ ] `/make/tenants` liefert 403 ohne Token, 200 mit Token
- [ ] `/make/tenants` liefert Secrets nur bei HTTPS (falls aktiv)

## Access
- [ ] is_active=0 → Dashboard Lock Screen
- [ ] is_active=0 → Settings Save 403
- [ ] is_active=0 → make/tenants 403
- [ ] is_active=1 → normal

## Docs
- [ ] docs/api.md vorhanden/aktualisiert
- [ ] docs/make-multi-tenant.md vorhanden/aktualisiert
