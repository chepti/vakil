# ──────────────────────────────────────────────────────────────
#  Full reliable deploy to Hostinger
#  Usage:  .\deploy.ps1  "commit message"
# ──────────────────────────────────────────────────────────────
param(
    [string]$Message = "deploy $(Get-Date -Format 'yyyy-MM-dd HH:mm')"
)

$ErrorActionPreference = "Stop"
$ProjectRoot = "T:\laragon\www\vakil"
$SshKey      = "T:\.ssh\vakil_deploy"
$SshPort     = "65002"
$SshTarget   = "u630483490@46.17.175.26"
$RemoteRoot  = "~/domains/vakil.chepti.com"
$Php         = "/opt/alt/php83/usr/bin/php"

Set-Location $ProjectRoot

Write-Host "=== 1/5  Building assets ===" -ForegroundColor Cyan
npm run build
if ($LASTEXITCODE -ne 0) { Write-Host "build FAILED" -ForegroundColor Red; exit 1 }

Write-Host "=== 2/5  Pushing to GitHub ===" -ForegroundColor Cyan
git add -A
git diff --cached --quiet
if ($LASTEXITCODE -ne 0) {
    git commit -m $Message
    git push origin main
} else {
    Write-Host "No code changes to commit - continuing to asset deploy" -ForegroundColor Yellow
}

Write-Host "=== 3/5  Uploading build to server (public/build) ===" -ForegroundColor Cyan
# Laravel reads the manifest from public/build via public_path()
scp -P $SshPort -i $SshKey -r "$ProjectRoot\public\build" "${SshTarget}:${RemoteRoot}/public/"
if ($LASTEXITCODE -ne 0) { Write-Host "SCP FAILED" -ForegroundColor Red; exit 1 }

Write-Host "=== 4/5  Syncing code + build on server ===" -ForegroundColor Cyan
# git pull for PHP code, cp to public_html (web root), clear caches
$remoteScript = @"
cd $RemoteRoot
git pull origin main
cp -rf public/build/. public_html/build/
cp -rf public/. public_html/
$Php artisan migrate --force
$Php artisan config:clear
$Php artisan route:clear
$Php artisan view:clear
$Php artisan storage:link 2>/dev/null || true
echo '--- caches cleared, code and assets synced ---'
"@
ssh -p $SshPort -i $SshKey $SshTarget $remoteScript
if ($LASTEXITCODE -ne 0) { Write-Host "Remote deploy FAILED" -ForegroundColor Red; exit 1 }

Write-Host "=== 5/5  Sanity check ===" -ForegroundColor Cyan
try {
    $r = Invoke-WebRequest -Uri "https://vakil.chepti.com" -UseBasicParsing -TimeoutSec 20 -MaximumRedirection 0 -ErrorAction SilentlyContinue
    Write-Host "Site responds: $($r.StatusCode)" -ForegroundColor Green
} catch {
    $code = $_.Exception.Response.StatusCode.value__
    if ($code -ge 200 -and $code -lt 400) {
        Write-Host "Site responds: $code" -ForegroundColor Green
    } else {
        Write-Host "WARNING - site returned: $code" -ForegroundColor Yellow
    }
}

Write-Host "`nDEPLOY COMPLETE!" -ForegroundColor Green
