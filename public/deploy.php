<?php
if (($_GET['key'] ?? '') !== 'vakil-deploy-2026') {
    http_response_code(403);
    exit('Forbidden');
}

$base = '/home/u630483490/domains/vakil.chepti.com';
$php  = '/opt/alt/php83/usr/bin/php';

$cmds = [
    "git -C $base fetch origin 2>&1",
    "git -C $base reset --hard origin/main 2>&1",
    "$php $base/artisan migrate --force 2>&1",
    "$php $base/artisan config:clear 2>&1",
    "$php $base/artisan route:clear 2>&1",
    "$php $base/artisan view:clear 2>&1",
];

echo '<pre style="font-family:monospace;direction:ltr;text-align:left">';
foreach ($cmds as $cmd) {
    echo "\n\$ $cmd\n";
    exec($cmd, $out, $code);
    echo implode("\n", $out) . "\n";
    $out = [];
}
echo '</pre><p style="color:green;font-weight:bold">Deploy complete!</p>';
echo '<p><a href="/">Go to site</a></p>';
