<?php

declare(strict_types=1);

$baseCandidates = [
    dirname(__DIR__, 2).'/gacov_app',
    dirname(__DIR__, 2).'/gacov_app/gacov_app',
    dirname(__DIR__).'/gacov_app',
    dirname(__DIR__).'/gacov_app/gacov_app',
];

$basePath = null;

foreach ($baseCandidates as $candidate) {
    if (is_file($candidate.'/public/install.php')) {
        $basePath = $candidate;
        break;
    }
}

if ($basePath === null) {
    http_response_code(500);
    exit('GACOV deploy error: installer backend path not found.');
}

define('CPANEL_PUBLIC_BASE_PATH', $basePath);

$_SERVER['SCRIPT_FILENAME'] = CPANEL_PUBLIC_BASE_PATH.'/public/install.php';
$_SERVER['DOCUMENT_ROOT'] = __DIR__;

chdir(CPANEL_PUBLIC_BASE_PATH.'/public');
require CPANEL_PUBLIC_BASE_PATH.'/public/install.php';
