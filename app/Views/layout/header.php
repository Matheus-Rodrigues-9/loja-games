<?php
$pageTitle = $pageTitle ?? 'Painel MVC';
$flashMessage = obterFlash();
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
        <a class="brand" href="index.php"><span class="brand-mark">🎮</span> Loja Games <small style="opacity:.65">MVC</small></a>
        <nav class="admin-nav" aria-label="Navegação administrativa">
            <a href="index.php">Visão geral</a>
            <a href="index.php?entidade=games">Games</a>
            <a href="index.php?entidade=categorias">Categorias</a>
            <a href="index.php?entidade=usuarios">Usuários</a>
            <a href="index.php?entidade=clientes">Clientes</a>
            <a href="index.php?entidade=pedidos">Pedidos</a>
            <a href="../../index.php" target="_blank" rel="noopener">Loja ↗</a>
            <a href="../controllers/logout.php">Sair</a>
        </nav>
    </div>
</header>
<main class="container page-content">
    <div class="page-heading">
        <div><p class="eyebrow">Arquitetura MVC · Administração</p><h1><?= e($pageTitle) ?></h1></div>
        <span class="user-chip"><?= e($_SESSION['usuario_nome'] ?? 'Administrador') ?> · <?= e($_SESSION['usuario_nivel'] ?? '') ?></span>
    </div>
    <?php if ($flashMessage): ?>
        <div class="alert alert-<?= e($flashMessage['tipo']) ?>" role="alert"><?= e($flashMessage['mensagem']) ?></div>
    <?php endif; ?>
