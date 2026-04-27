<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

define('CPANEL_INSTALLER', true);
define('INSTALLER_BASE_PATH', dirname(__DIR__));
define('INSTALLER_LOCK_FILE', INSTALLER_BASE_PATH.'/storage/app/install.lock');

if (file_exists(INSTALLER_LOCK_FILE)) {
    http_response_code(403);
    echo renderPage(
        'Instalador bloqueado',
        '<p>La instalación ya fue ejecutada. Elimina <code>public/install.php</code> y conserva el archivo de bloqueo.</p>'
    );
    exit;
}

$errors = [];
$messages = [];
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = [
        'app_name' => trim((string) ($_POST['app_name'] ?? 'GACOV Inventarios')),
        'app_url' => trim((string) ($_POST['app_url'] ?? '')),
        'db_host' => trim((string) ($_POST['db_host'] ?? 'localhost')),
        'db_port' => trim((string) ($_POST['db_port'] ?? '3306')),
        'db_database' => trim((string) ($_POST['db_database'] ?? '')),
        'db_username' => trim((string) ($_POST['db_username'] ?? '')),
        'db_password' => (string) ($_POST['db_password'] ?? ''),
        'mail_from_address' => trim((string) ($_POST['mail_from_address'] ?? 'noreply@gacov.com.co')),
        'mail_from_name' => trim((string) ($_POST['mail_from_name'] ?? 'GACOV Inventarios')),
        'session_secure_cookie' => isset($_POST['session_secure_cookie']) ? 'true' : 'false',
        'local_ocr_enabled' => isset($_POST['local_ocr_enabled']) ? 'true' : 'false',
    ];

    $errors = validateInput($input);

    if ($errors === []) {
        try {
            ensureWritableDirectories();

            $envContent = buildEnvContent($input);
            file_put_contents(INSTALLER_BASE_PATH.'/.env', $envContent);

            require_once INSTALLER_BASE_PATH.'/vendor/autoload.php';

            /** @var \Illuminate\Foundation\Application $app */
            $app = require INSTALLER_BASE_PATH.'/bootstrap/app.php';
            $kernel = $app->make(Kernel::class);
            $kernel->bootstrap();

            runArtisanCommand('optimize:clear', $messages);
            runArtisanCommand('migrate', $messages, ['--force' => true]);
            runArtisanCommand('db:seed', $messages, ['--force' => true]);
            runArtisanCommand('storage:link', $messages, [], true);
            runArtisanCommand('config:cache', $messages, [], true);
            runArtisanCommand('route:cache', $messages, [], true);
            runArtisanCommand('view:cache', $messages, [], true);

            $lockPayload = [
                'installed_at' => date(DATE_ATOM),
                'app_url' => $input['app_url'],
                'db_database' => $input['db_database'],
            ];

            file_put_contents(
                INSTALLER_LOCK_FILE,
                json_encode($lockPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            $result = [
                'success' => true,
                'messages' => $messages,
                'credentials' => [
                    ['label' => 'Super admin', 'email' => 'superadmin@gacov.com.co', 'password' => 'SuperGacov2026!$'],
                    ['label' => 'Admin', 'email' => 'admin@gacov.com.co', 'password' => 'AdminGacov2026!'],
                ],
            ];
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }
    }
}

$defaultHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$defaultScheme = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$scriptDirectory = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
$scriptDirectory = $scriptDirectory === '/' ? '' : rtrim($scriptDirectory, '/');
$defaultUrl = $defaultScheme.'://'.$defaultHost.$scriptDirectory;

echo renderPage(
    'Instalador GACOV para cPanel',
    renderInstaller($errors, $messages, $result, $defaultUrl)
);

function validateInput(array $input): array
{
    $errors = [];

    if ($input['app_name'] === '') {
        $errors[] = 'El nombre de la aplicación es obligatorio.';
    }

    if ($input['app_url'] === '' || filter_var($input['app_url'], FILTER_VALIDATE_URL) === false) {
        $errors[] = 'La URL principal debe ser válida, por ejemplo https://tudominio.com.';
    }

    if ($input['db_host'] === '' || $input['db_database'] === '' || $input['db_username'] === '') {
        $errors[] = 'Host, base de datos y usuario MySQL son obligatorios.';
    }

    if (! ctype_digit($input['db_port'])) {
        $errors[] = 'El puerto MySQL debe ser numérico.';
    }

    if ($input['mail_from_address'] !== '' && filter_var($input['mail_from_address'], FILTER_VALIDATE_EMAIL) === false) {
        $errors[] = 'El correo remitente no es válido.';
    }

    return $errors;
}

function ensureWritableDirectories(): void
{
    $directories = [
        INSTALLER_BASE_PATH,
        INSTALLER_BASE_PATH.'/bootstrap/cache',
        INSTALLER_BASE_PATH.'/storage',
        INSTALLER_BASE_PATH.'/storage/app',
        INSTALLER_BASE_PATH.'/storage/framework',
        INSTALLER_BASE_PATH.'/storage/framework/cache',
        INSTALLER_BASE_PATH.'/storage/framework/cache/data',
        INSTALLER_BASE_PATH.'/storage/framework/sessions',
        INSTALLER_BASE_PATH.'/storage/framework/views',
        INSTALLER_BASE_PATH.'/storage/logs',
    ];

    foreach ($directories as $directory) {
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (! is_writable($directory)) {
            throw new RuntimeException('No hay permisos de escritura en: '.$directory);
        }
    }
}

function buildEnvContent(array $input): string
{
    $appKey = 'base64:'.base64_encode(random_bytes(32));
    $appUrl = rtrim($input['app_url'], '/');
    $sessionDomain = parse_url($appUrl, PHP_URL_HOST) ?: '';

    $lines = [
        'APP_NAME="'.addcslashes($input['app_name'], '"').'"',
        'APP_ENV=production',
        'APP_KEY='.$appKey,
        'APP_DEBUG=false',
        'APP_URL='.$appUrl,
        'FRONTEND_URL='.$appUrl,
        'ASSET_URL='.$appUrl,
        '',
        'APP_LOCALE=es_CO',
        'APP_FALLBACK_LOCALE=es',
        'APP_FAKER_LOCALE=es_CO',
        '',
        'APP_MAINTENANCE_DRIVER=file',
        'BCRYPT_ROUNDS=12',
        '',
        'LOG_CHANNEL=stack',
        'LOG_STACK=single',
        'LOG_DEPRECATIONS_CHANNEL=null',
        'LOG_LEVEL=error',
        '',
        'DB_CONNECTION=mysql',
        'DB_HOST='.$input['db_host'],
        'DB_PORT='.$input['db_port'],
        'DB_DATABASE='.$input['db_database'],
        'DB_USERNAME='.$input['db_username'],
        'DB_PASSWORD="'.addcslashes($input['db_password'], '"').'"',
        '',
        'SESSION_DRIVER=database',
        'SESSION_LIFETIME=120',
        'SESSION_ENCRYPT=false',
        'SESSION_PATH=/',
        'SESSION_DOMAIN='.$sessionDomain,
        'SESSION_SECURE_COOKIE='.$input['session_secure_cookie'],
        'SESSION_HTTP_ONLY=true',
        'SESSION_SAME_SITE=lax',
        '',
        'BROADCAST_CONNECTION=log',
        'FILESYSTEM_DISK=local',
        'QUEUE_CONNECTION=database',
        '',
        'CACHE_STORE=database',
        '',
        'MAIL_MAILER=log',
        'MAIL_SCHEME=null',
        'MAIL_HOST=127.0.0.1',
        'MAIL_PORT=2525',
        'MAIL_USERNAME=null',
        'MAIL_PASSWORD=null',
        'MAIL_FROM_ADDRESS='.$input['mail_from_address'],
        'MAIL_FROM_NAME="'.addcslashes($input['mail_from_name'], '"').'"',
        '',
        'LOCAL_OCR_ENABLED='.$input['local_ocr_enabled'],
        'LOCAL_OCR_STRICT=true',
        'LOCAL_OCR_ENDPOINT=http://127.0.0.1:8011',
        'LOCAL_OCR_TIMEOUT=180',
        'LOCAL_OCR_CONNECT_TIMEOUT=5',
        'LOCAL_OCR_ALLOWED_ORIGINS='.$appUrl,
        'LOCAL_OCR_OLLAMA_TIMEOUT=300',
        'LOCAL_OCR_IMAGE_MAX_SIDE=1280',
        'LOCAL_OCR_IMAGE_QUALITY=78',
        '',
        'GEMINI_API_KEY=',
        'OPENAI_API_KEY=',
        'AWS_ACCESS_KEY_ID=',
        'AWS_SECRET_ACCESS_KEY=',
        'AWS_DEFAULT_REGION=us-east-1',
        'AWS_BUCKET=',
        'AWS_USE_PATH_STYLE_ENDPOINT=false',
        '',
        'VITE_APP_NAME="${APP_NAME}"',
    ];

    return implode(PHP_EOL, $lines).PHP_EOL;
}

function runArtisanCommand(string $command, array &$messages, array $parameters = [], bool $allowFailure = false): void
{
    $exitCode = Artisan::call($command, $parameters);
    $output = trim(Artisan::output());

    $messages[] = [
        'command' => $command,
        'exit_code' => $exitCode,
        'output' => $output === '' ? 'Sin salida adicional.' : $output,
    ];

    if ($exitCode !== 0 && ! $allowFailure) {
        throw new RuntimeException("El comando '{$command}' falló: ".$output);
    }
}

function renderInstaller(array $errors, array $messages, ?array $result, string $defaultUrl): string
{
    $html = '<div class="card">';
    $html .= '<p>Este instalador deja el proyecto listo en cPanel sin usar terminal. Al finalizar ejecuta migraciones, seeders, genera <code>.env</code> y crea el archivo de bloqueo.</p>';
    $html .= '<p><strong>Antes de continuar:</strong> crea la base MySQL y el usuario desde cPanel, y sube el paquete completo a sus carpetas respectivas.</p>';
    $html .= '</div>';

    if ($errors !== []) {
        $html .= '<div class="card error"><h2>Errores</h2><ul>';
        foreach ($errors as $error) {
            $html .= '<li>'.escapeHtml($error).'</li>';
        }
        $html .= '</ul></div>';
    }

    if ($result !== null && ($result['success'] ?? false) === true) {
        $html .= '<div class="card success"><h2>Instalación completada</h2><p>La base quedó inicializada. Entra al sistema, cambia contraseñas y elimina <code>public/install.php</code>.</p><h3>Credenciales iniciales</h3><ul>';
        foreach ($result['credentials'] as $credential) {
            $html .= '<li><strong>'.escapeHtml($credential['label']).':</strong> '.escapeHtml($credential['email']).' / '.escapeHtml($credential['password']).'</li>';
        }
        $html .= '</ul></div>';
    }

    if ($messages !== []) {
        $html .= '<div class="card"><h2>Bitácora</h2>';
        foreach ($messages as $message) {
            $html .= '<div class="log">';
            $html .= '<strong>'.escapeHtml($message['command']).'</strong> · salida '.(int) $message['exit_code'];
            $html .= '<pre>'.escapeHtml($message['output']).'</pre>';
            $html .= '</div>';
        }
        $html .= '</div>';
    }

    if ($result === null) {
        $html .= '<form method="post" class="card form-grid">';
        $postedAppName = escapeHtml((string) ($_POST['app_name'] ?? 'GACOV Inventarios'));
        $postedAppUrl = escapeHtml((string) ($_POST['app_url'] ?? $defaultUrl));
        $postedDbHost = escapeHtml((string) ($_POST['db_host'] ?? 'localhost'));
        $postedDbPort = escapeHtml((string) ($_POST['db_port'] ?? '3306'));
        $postedDbDatabase = escapeHtml((string) ($_POST['db_database'] ?? ''));
        $postedDbUsername = escapeHtml((string) ($_POST['db_username'] ?? ''));
        $postedMailFromAddress = escapeHtml((string) ($_POST['mail_from_address'] ?? 'noreply@gacov.com.co'));
        $postedMailFromName = escapeHtml((string) ($_POST['mail_from_name'] ?? 'GACOV Inventarios'));
        $secureCookieChecked = ($_SERVER['REQUEST_METHOD'] === 'POST' && ! isset($_POST['session_secure_cookie'])) ? '' : ' checked';
        $ocrChecked = isset($_POST['local_ocr_enabled']) ? ' checked' : '';

        $html .= '<label><span>Nombre de la app</span><input name="app_name" value="'.$postedAppName.'" required></label>';
        $html .= '<label><span>URL principal</span><input name="app_url" type="url" value="'.$postedAppUrl.'" required></label>';
        $html .= '<label><span>Host MySQL</span><input name="db_host" value="'.$postedDbHost.'" required></label>';
        $html .= '<label><span>Puerto MySQL</span><input name="db_port" value="'.$postedDbPort.'" required></label>';
        $html .= '<label><span>Base de datos</span><input name="db_database" value="'.$postedDbDatabase.'" required></label>';
        $html .= '<label><span>Usuario MySQL</span><input name="db_username" value="'.$postedDbUsername.'" required></label>';
        $html .= '<label><span>Contraseña MySQL</span><input name="db_password" type="password"></label>';
        $html .= '<label><span>Correo remitente</span><input name="mail_from_address" type="email" value="'.$postedMailFromAddress.'"></label>';
        $html .= '<label><span>Nombre remitente</span><input name="mail_from_name" value="'.$postedMailFromName.'"></label>';
        $html .= '<label class="checkbox"><input type="checkbox" name="session_secure_cookie"'.$secureCookieChecked.'> <span>Forzar cookie segura HTTPS</span></label>';
        $html .= '<label class="checkbox"><input type="checkbox" name="local_ocr_enabled"'.$ocrChecked.'> <span>Activar OCR local</span></label>';
        $html .= '<button type="submit">Instalar GACOV</button>';
        $html .= '</form>';
    }

    return $html;
}

function renderPage(string $title, string $content): string
{
    $safeTitle = escapeHtml($title);

    return <<<HTML
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$safeTitle}</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #0f172a;
            --surface: #ffffff;
            --text: #0f172a;
            --muted: #475569;
            --border: #dbe3ef;
            --primary: #0284c7;
            --error: #b91c1c;
            --success: #166534;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, Arial, sans-serif;
            background: linear-gradient(160deg, #e0f2fe 0%, #f8fafc 45%, #dbeafe 100%);
            color: var(--text);
        }
        .wrap {
            max-width: 920px;
            margin: 0 auto;
            padding: 40px 20px 56px;
        }
        h1, h2, h3 { margin-top: 0; }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.08);
        }
        .error { border-color: rgba(185, 28, 28, 0.25); }
        .success { border-color: rgba(22, 101, 52, 0.25); }
        .form-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        }
        label { display: grid; gap: 8px; font-weight: 600; }
        label span { color: var(--muted); font-size: 14px; }
        input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 15px;
        }
        .checkbox {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checkbox input { width: auto; }
        button {
            grid-column: 1 / -1;
            border: 0;
            border-radius: 12px;
            padding: 14px 18px;
            background: var(--primary);
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }
        ul { margin-bottom: 0; }
        pre {
            background: #0f172a;
            color: #e2e8f0;
            padding: 14px;
            border-radius: 12px;
            overflow-x: auto;
            white-space: pre-wrap;
        }
        code {
            background: #eff6ff;
            color: #1d4ed8;
            padding: 2px 6px;
            border-radius: 6px;
        }
        .log + .log { margin-top: 16px; }
    </style>
</head>
<body>
    <main class="wrap">
        <h1>{$safeTitle}</h1>
        {$content}
    </main>
</body>
</html>
HTML;
}

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
