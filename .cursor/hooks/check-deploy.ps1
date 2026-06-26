# Deploy gate — runs on agent stop hook
$ErrorActionPreference = 'SilentlyContinue'
$ProjectRoot = 'T:\laragon\www\vakil'

if (-not (Test-Path $ProjectRoot)) {
    Write-Output '{}'
    exit 0
}

Set-Location $ProjectRoot
$reasons = @()

$porcelain = git status --porcelain 2>$null
$relevant = $null
if ($porcelain) {
    $relevant = $porcelain | Where-Object {
        $_ -match '(^.. resources/|^.. app/|^.. public/build|^.. package\.json|^.. composer\.json|^.. database/migrations/)'
    }
    if ($relevant) { $reasons += 'uncommitted code or build changes' }
}

git fetch origin main 2>$null
$local  = (git rev-parse HEAD 2>$null)
$remote = (git rev-parse origin/main 2>$null)
if ($local -and $remote -and ($local -ne $remote)) {
    $reasons += 'unpushed commits'
}

$markerFile = Join-Path $ProjectRoot '.cursor\last-deployed'
$deployed = ''
if (Test-Path $markerFile) { $deployed = (Get-Content $markerFile -Raw).Trim() }
if ($local -and $deployed -ne $local -and -not $relevant) {
    $reasons += 'pushed to GitHub but not deployed to server'
}

if ($reasons.Count -eq 0) {
    Write-Output '{}'
    exit 0
}

$msg = 'Full deploy required before finishing. Reasons: ' + ($reasons -join '; ') + '. Run: cd T:\laragon\www\vakil; .\deploy.ps1 "auto deploy after agent". Do not stop until deploy succeeds.'

@{ followup_message = $msg } | ConvertTo-Json -Compress
exit 0
