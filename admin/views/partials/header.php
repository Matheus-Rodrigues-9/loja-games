<?php
$pageTitle = $pageTitle ?? 'Painel Administrativo';
$flash = obterFlash();
$usuarioLogado = usuarioAtual();
$paginaAtual = basename((string) ($_SERVER['PHP_SELF'] ?? ''));
$navJogos = in_array($paginaAtual, ['ver_games.php', 'cadastrar_game.php', 'editar_game.php'], true);
$navUsuarios = in_array($paginaAtual, ['ver_usuarios.php', 'cadastrar_usuario.php', 'editar_usuario.php'], true);
$navPedidos = $paginaAtual === 'ver_pedidos.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#070914">
    <title><?= e($pageTitle) ?> | Loja Games</title>
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body class="admin-page">
<header class="admin-header">
    <div class="container header-inner">
        <a class="brand" href="gerenciar_games.php"><span class="brand-mark">🎮</span> Loja Games</a>
        <nav class="admin-nav" aria-label="Navegação administrativa">
            <a class="<?= $paginaAtual === 'gerenciar_games.php' ? 'active' : '' ?>" href="gerenciar_games.php">Dashboard</a>
            <a class="<?= $navJogos ? 'active' : '' ?>" href="ver_games.php">Jogos</a>
            <a href="cadastrar_game.php">+ Jogo</a>
            <a class="<?= $navPedidos ? 'active' : '' ?>" href="ver_pedidos.php">Pedidos</a>
            <?php if (($usuarioLogado['nivel'] ?? '') === 'admin'): ?>
                <a class="<?= $navUsuarios ? 'active' : '' ?>" href="ver_usuarios.php">Usuários</a>
                <a href="cadastrar_usuario.php">+ Usuário</a>
            <?php endif; ?>
            <?php if (($usuarioLogado['nivel'] ?? '') === 'admin'): ?><a href="../../admin/mvc/index.php">Painel MVC</a><?php endif; ?>
            <a href="../../index.php" target="_blank" rel="noopener">Loja ↗</a>
            <a href="../controllers/logout.php">Sair</a>
        </nav>
    </div>
</header>

<main class="container page-content">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Central de comando</p>
            <h1><?= e($pageTitle) ?></h1>
        </div>
        <div class="user-chip">
            <?= e($usuarioLogado['nome']) ?> · <?= e($usuarioLogado['nivel']) ?>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['tipo']) ?>" role="alert">
            <?= e($flash['mensagem']) ?>
        </div>
    <?php endif; ?>
