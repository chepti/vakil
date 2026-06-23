# ──────────────────────────────────────────────────────────────
#  Full reliable deploy to shared hosting (Hostinger / cPanel)
#  1. Copy this file to deploy.ps1
#  2. Fill in your own values below
#  3. deploy.ps1 is in .gitignore — your credentials stay local
#
#  Usage:  .\deploy.ps1  "commit message"
# ──────────────────────────────────────────────────────────────
param(
    [string]$Message = "deploy $(Get-Date -Format 'yyyy-MM-dd HH:mm')"
)

$ErrorActionPreference = "Stop"
$ProjectRoot = "C:\path\to\your\project"         # ← שנה לנתיב המקומי שלך
$SshKey      = "C:\path\to\your\deploy_key"      # ← מפתח SSH פרטי לפריסה
$SshPort     = "22"                              # ← פורט SSH (Hostinger: 65002)
$SshTarget   = "username@your-server-ip"         # ← user@IP של השרת
$RemoteRoot  = "~/domains/your-domain.com"       # ← נתיב הפרויקט בשרת
$Php         = "/usr/bin/php"                    # ← נתיב PHP בשרת (Hostinger PHP 8.3: /opt/alt/php83/usr/bin/php)

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

Write-Host "=== 3/4  Deploying on server (git pull + sync + clear caches) ===" -ForegroundColor Cyan
$remoteScript = @"
cd $RemoteRoot
git checkout -- public/build 2>/dev/null || true
git clean -fd public/build
git pull origin main
cp -rf public/. public_html/
$Php artisan migrate --force
$Php artisan config:clear
$Php artisan route:clear
$Php artisan view:clear
$Php artisan storage:link 2>/dev/null || true
echo '--- code and assets synced, caches cleared ---'
"@
ssh -p $SshPort -i $SshKey $SshTarget $remoteScript
if ($LASTEXITCODE -ne 0) { Write-Host "Remote deploy FAILED" -ForegroundColor Red; exit 1 }

Write-Host "=== 4/4  Sanity check ===" -ForegroundColor Cyan
try {
    $r = Invoke-WebRequest -Uri "https://your-domain.com" -UseBasicParsing -TimeoutSec 20 -MaximumRedirection 0 -ErrorAction SilentlyContinue
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
