<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

exigirLogin('../../login.php');
if (($_SESSION['usuario_nivel'] ?? '') !== 'admin') {
    definirFlash('erro', 'A área MVC é restrita a administradores.');
    header('Location: ../views/gerenciar_games.php');
    exit;
}

$entidade = (string)($_GET['entidade'] ?? 'inicio');
$acao = (string)($_GET['acao'] ?? 'listar');
$controladores = [
    'games' => App\Controllers\GameController::class,
    'categorias' => App\Controllers\CategoriaController::class,
    'usuarios' => App\Controllers\UsuarioController::class,
    'clientes' => App\Controllers\ClienteController::class,
    'pedidos' => App\Controllers\PedidoController::class,
];

try {
    if ($entidade === 'inicio') {
        $pageTitle = 'Painel administrativo MVC';
        $totais = [
            'games' => (int)$pdo->query('SELECT COUNT(*) FROM games')->fetchColumn(),
            'categorias' => (int)$pdo->query('SELECT COUNT(*) FROM categorias')->fetchColumn(),
            'usuarios' => (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn(),
            'clientes' => (int)$pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn(),
            'pedidos' => (int)$pdo->query('SELECT COUNT(*) FROM pedidos')->fetchColumn(),
            'faturamento' => (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM pedidos WHERE status <> 'cancelado'")->fetchColumn(),
        ];
        require __DIR__ . '/../../app/Views/layout/header.php';
        require __DIR__ . '/../../app/Views/dashboard.php';
        require __DIR__ . '/../../app/Views/layout/footer.php';
        exit;
    }
    if (!isset($controladores[$entidade])) {
        throw new InvalidArgumentException('Módulo não encontrado.');
    }
    $controller = new $controladores[$entidade]($pdo);
    $controller->dispatch($acao);
} catch (Throwable $e) {
    error_log('[Loja Games MVC] ' . $e->getMessage());
    definirFlash('erro', $e instanceof InvalidArgumentException || $e instanceof RuntimeException ? $e->getMessage() : 'Ocorreu um erro ao processar a operação.');
    $destino = in_array($entidade, array_keys($controladores), true) ? $entidade : 'inicio';
    header('Location: index.php' . ($destino === 'inicio' ? '' : '?entidade=' . urlencode($destino)));
    exit;
}
