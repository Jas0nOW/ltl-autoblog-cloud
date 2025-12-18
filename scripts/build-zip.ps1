# build-zip.ps1
# Build script for ltl-autoblog-cloud (Windows PowerShell)
# Output: dist/ltl-autoblog-cloud-<version>.zip, dist/SHA256SUMS.txt

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

$files = Get-IncludedFiles $pluginDir

# Create ZIP
if (Test-Path $zipPath) { Remove-Item $zipPath }
Compress-Archive -Path $files.FullName -DestinationPath $zipPath

# SHA256SUMS
$hash = Get-FileHash -Path $zipPath -Algorithm SHA256
"$($hash.Hash)  $zipName" | Set-Content $shaPath

Write-Host "Build complete: $zipPath"
Write-Host "SHA256: $($hash.Hash)"
