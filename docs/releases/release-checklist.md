# Release Checklist — LTL AutoBlog Cloud

## Pre-Release QA

- [ ] All Phase 0/1/2 tasks from Master-Plan.md are in DONE LOG
- [ ] `docs/testing/logs/testing-log.md` filled with latest smoke test results
- [ ] All PHP syntax checks pass: `php -l wp-portal-plugin/ltl-saas-portal/includes/**/*.php`
- [ ] No broken references in docs (links + examples match code)
- [ ] API contract matches implementation (`docs/reference/api.md` vs `class-rest.php`)

## Version Management

- [ ] Version in plugin header is incremented: `wp-portal-plugin/ltl-saas-portal/ltl-saas-portal.php` (e.g., `1.0.0`)
- [ ] Version format is semver: `X.Y.Z` (major.minor.patch)
- [ ] CHANGELOG entry added (if CHANGELOG.md exists, or captured in release notes)

## Build & Verification

- [ ] Run: `powershell -File scripts/build-zip.ps1`
  - Produces: `dist/ltl-autoblog-cloud-<version>.zip`
  - Produces: `dist/SHA256SUMS.txt` (checksum artifact)
- [ ] ZIP file size is reasonable (typically 1–3 MB for plugin)
- [ ] Extract and verify ZIP contains expected files (plugin, docs, README)
- [ ] No `.git`, `.env`, or `node_modules` in ZIP (excluded by build script)

## Deployment Safety

- [ ] SHA256 checksum is reproducible (run build twice, hashes must match)
- [ ] Verify command for end-users is documented in release notes:
  ```powershell
  # Windows PowerShell
  (certUtil -hashfile "ltl-autoblog-cloud-<version>.zip" SHA256) -replace " ","" -eq ((Get-Content SHA256SUMS.txt) -split "  ")[0]
  ```
- [ ] GitHub Release Draft created with:
  - Title: `v<version> — <short description>`
  - Body: Links to CHANGELOG + Migration Guide (if major version)
  - Artifacts: Upload `ltl-autoblog-cloud-<version>.zip` + `SHA256SUMS.txt`

## Final Sign-Off

- [ ] Smoke tests passed in staging environment
- [ ] No regressions vs previous version
- [ ] Security review passed (secrets not in ZIP, encryption intact)
- [ ] GitHub Issues referenced in PR are closed or moved to backlog
- [ ] Release approved by team lead

---

## Quick Commands

**Build Release:**
```powershell
cd scripts
.\build-zip.ps1
ls ..\dist
```

**Verify Checksum (Windows):**
```powershell
(certUtil -hashfile "dist\ltl-autoblog-cloud-1.0.0.zip" SHA256) -replace " ",""
Get-Content "dist\SHA256SUMS.txt" | Select-Object -First 1
```

**Verify Checksum (Linux/Mac):**
```bash
sha256sum -c dist/SHA256SUMS.txt
```

