# Compile German PO file to MO binary format for WordPress
# Requires msgfmt (part of gettext package)

$pluginDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$languagesDir = Join-Path $pluginDir "languages"
$poFile = Join-Path $languagesDir "ltl-saas-portal-de_DE.po"
$moFile = Join-Path $languagesDir "ltl-saas-portal-de_DE.mo"

Write-Host "=== LTL AutoBlog Cloud - Compile Translations ===" -ForegroundColor Cyan
Write-Host ""

# Check if gettext is installed
$msgfmtPath = Get-Command msgfmt -ErrorAction SilentlyContinue

if (-not $msgfmtPath) {
    Write-Host "ERROR: msgfmt not found!" -ForegroundColor Red
    Write-Host ""
    Write-Host "You need to install gettext to compile .po files." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Options:" -ForegroundColor White
    Write-Host "1. Install via Chocolatey: choco install gettext" -ForegroundColor Gray
    Write-Host "2. Download from: https://mlocati.github.io/articles/gettext-iconv-windows.html" -ForegroundColor Gray
    Write-Host "3. Use Poedit (has built-in compiler): https://poedit.net/" -ForegroundColor Gray
    Write-Host ""
    Write-Host "ALTERNATIVE: You can skip compilation and WordPress will work with .po files (slower)" -ForegroundColor Yellow
    exit 1
}

Write-Host "✓ Found msgfmt: $($msgfmtPath.Source)" -ForegroundColor Green
Write-Host ""

if (-not (Test-Path $poFile)) {
    Write-Host "ERROR: PO file not found: $poFile" -ForegroundColor Red
    exit 1
}

Write-Host "Compiling: $poFile" -ForegroundColor White
Write-Host "Output: $moFile" -ForegroundColor White
Write-Host ""

try {
    & msgfmt -o $moFile $poFile

    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Successfully compiled German translation!" -ForegroundColor Green
        Write-Host ""
        $moSize = (Get-Item $moFile).Length
        Write-Host "Generated: $moFile ($moSize bytes)" -ForegroundColor Gray
    } else {
        Write-Host "✗ Compilation failed!" -ForegroundColor Red
        exit 1
    }
} catch {
    Write-Host "✗ Error: $_" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "=== Done ===" -ForegroundColor Cyan
