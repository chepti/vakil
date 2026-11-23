<?php
/**
 * נקודת פריסה: GitHub Actions קורא לכתובת הזו אחרי כל דחיפה ל-main, והשרת מושך בעצמו את הקוד מ-GitHub
 * (Hostinger חוסם SSH מכתובות של GitHub Actions, ו-exec/shell_exec חסומים ב-PHP, לכן אין git pull מכאן:
 * מורידים zip מ-codeload ומעתיקים רק קבצים ששונו).
 *
 * POST /deploy.php   כותרת X-Deploy-Token: <DEPLOY_TOKEN מתוך .env>   גוף: sha=<40 תווי hex> (אופציונלי, אחרת main)
 *
 * מעדכן את האתר הראשי ואת אתרי-האח (vakil-demo, benartzi) אם קיימים; לכל אחד .env משלו עם אותו DEPLOY_TOKEN.
 * public/* בריפו מגיע ל-public_html/ בשרת. לעולם לא נוגע ב: .env*, storage/, vendor/, .git*, קבצי SQLite.
 * לא מוחק קבצים שהוסרו מהריפו. אחרי ההעתקה מנקה קאש מקומפל וקורא ל-/internal/deploy/migrate של כל אתר.
 * אם composer.json/composer.lock השתנו — מדווח composer_changed (vendor לא מתעדכן אוטומטית).
 */
header('Content-Type: application/json; charset=utf-8');

function reply($code, $arr) { http_response_code($code); echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR); exit; }
function env_value($file, $key) {
    if (!is_file($file)) return null;
    foreach (file($file, FILE_IGNORE_NEW_LINES) as $line) {
        if (preg_match('/^\s*' . preg_quote($key, '/') . '\s*=\s*(.*)$/', $line, $m)) return trim($m[1], " \t\"'");
    }
    return null;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') reply(405, ['error' => 'POST only']);

$mainRoot = dirname(__DIR__);                       // ~/domains/vakil.chepti.com (deploy.php יושב ב-public_html)
$token = env_value($mainRoot . '/.env', 'DEPLOY_TOKEN');
if (!$token || strlen($token) < 24) reply(503, ['error' => 'deploy not configured']);
$given = $_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? '';
if (!is_string($given) || !hash_equals($token, $given)) { usleep(400000); reply(401, ['error' => 'unauthorized']); }

const REPO = 'chepti/vakil';
$sha = (string)($_POST['sha'] ?? '');
$ref = preg_match('/^[0-9a-f]{40}$/', $sha) ? $sha : 'refs/heads/main';

$lock = fopen(sys_get_temp_dir() . '/vakil-deploy.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) reply(409, ['error' => 'deploy already running']);

set_time_limit(280);
$tmpZip = tempnam(sys_get_temp_dir(), 'vakildeploy');
$err = null; $zip = null;
for ($try = 1; $try <= 4; $try++) {                 // ה-zip של קומיט חדש יכול להתעכב כמה שניות
    $ch = curl_init('https://codeload.github.com/' . REPO . '/zip/' . $ref);
    $fh = fopen($tmpZip, 'wb');
    curl_setopt_array($ch, [CURLOPT_FILE => $fh, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 90,
                            CURLOPT_USERAGENT => 'vakil-deploy', CURLOPT_FAILONERROR => true]);
    $ok = curl_exec($ch); $http = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); fclose($fh);
    if ($ok && filesize($tmpZip) > 1000) { $zip = new ZipArchive(); if ($zip->open($tmpZip) === true) break; $zip = null; }
    $err = "download failed (HTTP $http)";
    sleep(3);
}
if (!$zip) { @unlink($tmpZip); reply(502, ['error' => $err ?: 'download failed']); }

$first = $zip->getNameIndex(0);
$prefix = substr($first, 0, strpos($first, '/') + 1);   // "vakil-<sha>/"
if ($prefix === '' || $prefix === '/') { @unlink($tmpZip); reply(500, ['error' => 'unexpected zip layout']); }

// האתרים: הראשי + אחים שקיימים בשרת
$domains = dirname($mainRoot);
$sites = [['name' => 'main', 'root' => $mainRoot, 'url' => 'https://vakil.chepti.com', 'seed' => true]];
foreach ([['vakil-demo.chepti.com', 'demo'], ['benartzi.chepti.com', 'benartzi']] as [$dir, $name]) {
    if (is_dir("$domains/$dir/public_html")) $sites[] = ['name' => $name, 'root' => "$domains/$dir", 'url' => "https://$dir", 'seed' => false];
}

$skip = function ($rel) {
    return $rel === '' || $rel[0] === '/' || strpos($rel, '..') !== false
        || preg_match('#(^|/)\.git(/|$)|(^|/)\.github/|^\.cursor/#', $rel)
        || preg_match('#^\.env#', $rel)
        || preg_match('#^(storage|vendor|node_modules)/#', $rel)
        || preg_match('#^public/(storage|hot)(/|$)#', $rel)
        || preg_match('#\.(sqlite|sqlite3|db|zip|ps1|bat)$#i', $rel);
};
$dest_of = function ($root, $rel) {
    return strpos($rel, 'public/') === 0 ? $root . '/public_html/' . substr($rel, 7) : $root . '/' . $rel;
};

// קבצים מהזיפ (פעם אחת)
$files = []; $skipped = 0;
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    if (substr($name, -1) === '/' || strpos($name, $prefix) !== 0) continue;
    $rel = substr($name, strlen($prefix));
    if ($skip($rel)) { $skipped++; continue; }
    $data = $zip->getFromIndex($i);
    if ($data !== false) $files[$rel] = $data;
}

// גיבוי (מחוץ לתיקיית האתר) ותכנית העתקה לכל אתר
$backupDir = null;
foreach ([getenv('HOME'), dirname($mainRoot, 2), dirname($mainRoot, 3)] as $h) {
    if ($h && is_dir($h . '/backups-private')) { $backupDir = $h . '/backups-private'; break; }
}
$plans = []; $total = 0;
foreach ($sites as $s) {
    $plans[$s['name']] = [];
    foreach ($files as $rel => $data) {
        $dest = $dest_of($s['root'], $rel);
        if (is_file($dest) && md5_file($dest) === md5($data)) continue;
        $plans[$s['name']][] = [$rel, $dest];
        $total++;
    }
}
$backupFile = null;
if ($total && $backupDir) {
    $backupFile = $backupDir . '/vakil-predeploy-' . date('Ymd-His') . '.zip';
    $bz = new ZipArchive();
    if ($bz->open($backupFile, ZipArchive::CREATE) === true) {
        foreach ($plans as $n => $plan) foreach ($plan as [$rel, $dest]) if (is_file($dest)) $bz->addFile($dest, "$n/$rel");
        $bz->close();
    } else { $backupFile = null; }
    $old = glob($backupDir . '/vakil-predeploy-*.zip'); sort($old);
    foreach (array_slice($old, 0, max(0, count($old) - 5)) as $f) @unlink($f);
}

$report = [];
foreach ($sites as $s) {
    $changed = [];
    foreach ($plans[$s['name']] as [$rel, $dest]) {
        $dir = dirname($dest);
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) reply(500, ['error' => "mkdir failed: {$s['name']}:$rel", 'done' => $report]);
        $tmp = $dest . '.deploytmp';
        if (file_put_contents($tmp, $files[$rel]) === false || !rename($tmp, $dest)) {
            @unlink($tmp);
            reply(500, ['error' => "write failed: {$s['name']}:$rel", 'done' => $report, 'backup' => $backupFile ? basename($backupFile) : null]);
        }
        $changed[] = $rel;
    }
    // ניקוי קאש מקומפל (במקום artisan optimize:clear, שדורש exec)
    foreach (array_merge(glob($s['root'] . '/bootstrap/cache/*.php') ?: [], glob($s['root'] . '/storage/framework/views/*.php') ?: []) as $f) {
        @unlink($f);
    }
    // מיגרציות וקאש דרך האפליקציה עצמה (בקשה נפרדת, אחרי שהקוד החדש במקום)
    $migrate = 'skipped (no DEPLOY_TOKEN in site .env)';
    $siteToken = env_value($s['root'] . '/.env', 'DEPLOY_TOKEN');
    if ($siteToken) {
        $ch = curl_init($s['url'] . '/internal/deploy/migrate');
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 90,
            CURLOPT_HTTPHEADER => ['X-Deploy-Token: ' . $siteToken], CURLOPT_USERAGENT => 'vakil-deploy',
            CURLOPT_POSTFIELDS => ['seed' => $s['seed'] ? '1' : '0']]);
        $out = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $cerr = curl_error($ch); curl_close($ch);
        $migrate = $code === 200 ? 'ok' : "HTTP $code" . ($cerr ? " ($cerr)" : '');
    }
    $report[$s['name']] = ['changed' => $changed, 'migrate' => $migrate,
        'composer_changed' => (bool)array_intersect($changed, ['composer.json', 'composer.lock'])];
}
$zip->close(); @unlink($tmpZip);

if ($backupDir) @file_put_contents($backupDir . '/vakil-last-deploy.json', json_encode(['at' => date('c'), 'ref' => $ref, 'sites' => $report], JSON_UNESCAPED_UNICODE));
reply(200, ['ok' => true, 'ref' => $ref, 'sites' => $report, 'skipped_protected' => $skipped,
            'backup' => $backupFile ? basename($backupFile) : null]);
