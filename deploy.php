<?php
/**
 * GitHub Webhook Auto-Deploy
 * NIS2Compliance.io - BALTUM Bureau
 */

define('SECRET', 'nis2compliance_deploy_2026');
define('REPO',   'kyproo/nis2compliance-io');
define('BRANCH', 'main');
define('DIR',    __DIR__);
define('LOG',    __DIR__ . '/deploy.log');

function log_msg($msg) {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    file_put_contents(LOG, $line, FILE_APPEND);
}

$payload = file_get_contents('php://input');

if (SECRET !== '') {
    $sig = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    $expected = 'sha256=' . hash_hmac('sha256', $payload, SECRET);
    if (!hash_equals($expected, $sig)) {
        http_response_code(403);
        log_msg('ERROR: Invalid signature');
        exit('Forbidden');
    }
}

$data = json_decode($payload, true);
$ref  = $data['ref'] ?? '';

if ($ref !== 'refs/heads/' . BRANCH) {
    log_msg('Skipped: branch ' . $ref);
    echo 'Skipped';
    exit;
}

$output = [];
$cmd = 'cd ' . escapeshellarg(DIR) . ' && git pull origin ' . BRANCH . ' 2>&1';
exec($cmd, $output, $code);

$log = implode("\n", $output);
log_msg('Deploy exit=' . $code . ': ' . $log);

if ($code === 0) {
    http_response_code(200);
    echo 'OK';
} else {
    http_response_code(500);
    echo 'Error';
}
