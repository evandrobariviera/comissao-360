<?php

declare(strict_types=1);

// Copie este arquivo para config.php (fora do git) e preencha com as credenciais reais.
// Em produção (cPanel): force_https=true, debug=false.

return [
    'app' => [
        'debug' => true,
        'force_https' => false,
        'session_timeout' => 1800, // segundos de inatividade até expirar a sessão
        'base_url' => 'http://comissao-360.test',
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'comissao360',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
];
