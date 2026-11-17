<?php

declare(strict_types=1);

// XAMPP padrão: localhost / loja_games / root / senha vazia.
// Em outro ambiente, defina DB_HOST, DB_NAME, DB_USER e DB_PASS nas variáveis do servidor.
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'loja_games');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') !== false ? (string)getenv('DB_PASS') : '');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log('[Loja Games] Erro de conexão: ' . $e->getMessage());
    http_response_code(500);
    exit('Não foi possível conectar ao banco de dados. Inicie o MySQL e confira a configuração em admin/config/conexao.php.');
}
