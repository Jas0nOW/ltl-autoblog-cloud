# build-zip.ps1
# Build script for ltl-autoblog-cloud (Windows PowerShell)
# Output: dist/ltl-autoblog-cloud-<version>.zip, dist/SHA256SUMS.txt
# Usage: .\build-zip.ps1
# Verification: Compare SHA256 with dist/SHA256SUMS.txt after download

$ErrorActionPreference = 'Stop'

# Paths
$pluginRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$repoRoot = Resolve-Path "$pluginRoot\..\.."
$pluginDir = "$repoRoot\wp-portal-plugin\ltl-saas-portal"
$distDir = "$repoRoot\dist"

# Ensure dist dir
if (!(Test-Path $distDir)) { New-Item -ItemType Directory -Path $distDir | Out-Null }

# Get version from plugin header
$mainFile = "$pluginDir/ltl-saas-portal.php"
$version = (Select-String -Path $mainFile -Pattern 'Version:\s*([0-9.]+)' | ForEach-Object { $_.Matches[0].Groups[1].Value })
if (-not $version) { Write-Error 'Version not found in plugin header.' }

Write-Host "[BUILD] Starting build for ltl-autoblog-cloud v$version" -ForegroundColor Green

# Output file names
$zipName = "ltl-autoblog-cloud-$version.zip"
$zipPath = "$distDir\$zipName"
$shaPath = "$distDir\SHA256SUMS.txt"

# Exclude patterns
$exclude = @( '.git', '.github', 'node_modules', 'vendor', '.env', 'dist', 'blueprints_raw' )

# Gather files to include
function Get-IncludedFiles($base, $rel = '') {
    $full = Join-Path $base $rel
    Get-ChildItem -Path $full -Recurse -File | Where-Object {
        $p = $_.FullName.Replace($base, '').TrimStart('\/')
        foreach ($ex in $exclude) {
            if ($p -like "$ex*" -or $p -match "\\$ex(\\|$)") { return $false }
        }
        return $true
    }
}

Write-Host "[BUNDLE] Gathering files from $pluginDir..." -ForegroundColor Cyan
$files = Get-IncludedFiles $pluginDir
$fileCount = ($files | Measure-Object).Count
Write-Host "[BUNDLE] Found $fileCount files to include" -ForegroundColor Cyan

# Create ZIP
if (Test-Path $zipPath) { Remove-Item $zipPath }
Write-Host "[ZIP] Creating $zipName..." -ForegroundColor Cyan
Compress-Archive -Path $files.FullName -DestinationPath $zipPath
$zipSize = (Get-Item $zipPath).Length / 1MB
Write-Host "[ZIP] Package created: $($zipSize.ToString('F2')) MB" -ForegroundColor Green

# SHA256SUMS
Write-Host "[HASH] Computing SHA256 checksum..." -ForegroundColor Cyan
$hash = Get-FileHash -Path $zipPath -Algorithm SHA256
"$($hash.Hash)  $zipName" | Set-Content $shaPath

Write-Host "✓ BUILD COMPLETE" -ForegroundColor Green
Write-Host "  Artifact:  $zipPath" -ForegroundColor Green
Write-Host "  Size:      $($zipSize.ToString('F2')) MB" -ForegroundColor Green
Write-Host "  SHA256:    $($hash.Hash)" -ForegroundColor Green
Write-Host "  Checksums: $shaPath" -ForegroundColor Green
Write-Host ""
Write-Host "VERIFY DEPLOYMENT (after download):" -ForegroundColor Yellow
Write-Host '  (certUtil -hashfile "ltl-autoblog-cloud-'"$version"'.zip" SHA256) -replace " ","" -eq ((Get-Content SHA256SUMS.txt) -split "  ")[0]' -ForegroundColor Yellow

