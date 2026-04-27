<?php

declare(strict_types=1);

define('CPANEL_PUBLIC_BASE_PATH', dirname(__DIR__).'/gacov_app');

$_SERVER['SCRIPT_FILENAME'] = CPANEL_PUBLIC_BASE_PATH.'/public/install.php';
$_SERVER['DOCUMENT_ROOT'] = __DIR__;

chdir(CPANEL_PUBLIC_BASE_PATH.'/public');
require CPANEL_PUBLIC_BASE_PATH.'/public/install.php';
