<?php

declare(strict_types=1);

define('APP_DIR', __DIR__);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = APP_DIR . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

$configPath = APP_DIR . '/Config/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    exit('Configuração ausente: copie app/Config/config.example.php para app/Config/config.php e preencha as credenciais.');
}
$config = require $configPath;

date_default_timezone_set('America/Sao_Paulo');
error_reporting(E_ALL);
ini_set('display_errors', !empty($config['app']['debug']) ? '1' : '0');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => !empty($config['app']['force_https']),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_name('comissao360_sid');
session_start();

$timeout = $config['app']['session_timeout'] ?? 1800;
if (isset($_SESSION['ultimo_acesso']) && (time() - $_SESSION['ultimo_acesso']) > $timeout) {
    $_SESSION = [];
    session_destroy();
    session_start();
}
$_SESSION['ultimo_acesso'] = time();

App\Core\Database::configure($config['db']);

return $config;
