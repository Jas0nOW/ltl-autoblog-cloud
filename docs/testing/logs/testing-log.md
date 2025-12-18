# Testing Log

> Fülle diese Datei aus. Wenn du sie zurücksetzen willst, ersetze sie mit der Vorlage `docs/testing/logs/testing-log-template.md`.

## Release Info
- **Version**: ___
- **Build Date**: ___
- **Branch**: ___
- **Build Commit**: ___

## Datum
- ___

## Umgebung
- WordPress: ___
- PHP: ___
- SSL: ___
- Host: ___

## Build Verification
- ZIP created: ✓/✗ (Size: ___ MB)
- SHA256 computed: ✓/✗ (Hash: _______________)
- SHA256 reproducible (2nd build match): ✓/✗
- ZIP contents verified (no .git/.env): ✓/✗

## Sprint 04 Tests — Limits

### Limits — make/tenants
- Test 1: posts_this_month=0 → Erwartet: skip=false → Ergebnis: ___
- Test 2: posts_this_month=limit → Erwartet: skip=true → Ergebnis: ___
- Test 3: posts_period_start=letzter Monat → Erwartet: reset → Ergebnis: ___

### Callback
- Test 4: successful publish → Erwartet: +1 → Ergebnis: ___
- Test 5: month rollover → Erwartet: reset +1 → Ergebnis: ___

### Regression
- Test 6: /active-users maskiert passwords → Ergebnis: ___
- Test 7: /make/tenants ohne Token 403 → Ergebnis: ___

## Phase 1 Security Tests (Retry Telemetry, Month Rollover, Rate Limiting)

- Test 8: Retry telemetry fields stored (attempts, last_http_status, retry_backoff_ms): ✓/✗
- Test 9: Month rollover atomic (parallel requests don't double-reset): ✓/✗
- Test 10: Rate limit blocks after 10 failed auth attempts: ✓/✗
- Test 11: Rate limit resets after 15 minutes: ✓/✗
- Test 12: Callback idempotency (duplicate execution_id returns cached): ✓/✗

## Notes / Issues
- ___

