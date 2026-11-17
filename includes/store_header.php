<?php
require_once __DIR__ . '/../admin/config/auth.php';
$storeTitle = $storeTitle ?? 'Loja Games';
$storeFlash = obterFlash();
$storeUser = usuarioAtual();
$storeCart = $_SESSION['carrinho'] ?? [];
$storeCartCount = 0;
if (is_array($storeCart)) {
    foreach ($storeCart as $quantidade) {
        $storeCartCount += max(0, (int) $quantidade);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#070914">
    <title><?= e($storeTitle) ?> | Loja Games</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
<header class="store-header">
    <div class="container header-inner">
        <a class="brand" href="index.php"><span class="brand-mark">🎮</span> Loja Games</a>
        <nav class="store-nav" aria-label="Navegação principal">
            <a href="index.php#catalogo">Catálogo</a>
            <a class="cart-link" href="carrinho.php">🛒 Carrinho <span class="cart-count"><?= $storeCartCount ?></span></a>
            <?php if (estaLogado() && ($storeUser['nivel'] ?? '') === 'cliente'): ?>
                <span class="store-greeting">Olá, <?= e($storeUser['nome']) ?></span>
                <a href="meus_pedidos.php">Meus pedidos</a>
                <a href="admin/controllers/logout.php">Sair</a>
            <?php elseif (estaLogado()): ?>
                <a class="btn btn-small btn-primary" href="admin/views/gerenciar_games.php">Painel da equipe</a>
                <a href="admin/controllers/logout.php">Sair</a>
            <?php else: ?>
                <a href="login.php?redirect=checkout.php">Entrar</a>
                <a class="btn btn-small btn-secondary" href="cadastro.php">Criar conta</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<?php if ($storeFlash): ?>
    <div class="container store-flash">
        <div class="alert alert-<?= e($storeFlash['tipo']) ?>" role="status"><?= e($storeFlash['mensagem']) ?></div>
    </div>
<?php endif; ?>
