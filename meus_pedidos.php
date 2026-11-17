<?php

declare(strict_types=1);

require_once __DIR__ . '/admin/config/auth.php';
require_once __DIR__ . '/admin/config/conexao.php';
require_once __DIR__ . '/includes/cart_helpers.php';

if (!estaLogado()) {
    header('Location: login.php?redirect=meus_pedidos.php');
    exit;
}
if (($_SESSION['usuario_nivel'] ?? '') !== 'cliente') {
    header('Location: index.php');
    exit;
}

$sth = $pdo->prepare(
    'SELECT id, total, status, forma_pagamento, created_at
     FROM pedidos WHERE usuario_id = :usuario_id ORDER BY id DESC'
);
$sth->execute([':usuario_id' => (int) $_SESSION['usuario_id']]);
$pedidos = $sth->fetchAll();

$storeTitle = 'Meus pedidos';
require __DIR__ . '/includes/store_header.php';
?>

<main class="container page-content">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Sua conta</p>
            <h1>Meus pedidos</h1>
            <p class="muted">Acompanhe as compras registradas nesta conta.</p>
        </div>
        <a class="btn btn-primary" href="index.php#catalogo">Continuar comprando</a>
    </div>

    <?php if (!$pedidos): ?>
        <section class="panel empty-state large">
            <div class="empty-cart-icon">📦</div>
            <h2>Você ainda não fez pedidos</h2>
            <p>Quando finalizar uma compra, ela aparecerá aqui.</p>
            <a class="btn btn-primary" href="index.php#catalogo">Explorar catálogo</a>
        </section>
    <?php else: ?>
        <div class="order-list">
            <?php foreach ($pedidos as $pedido): ?>
                <article class="panel order-card">
                    <div>
                        <p class="eyebrow">Pedido #<?= (int) $pedido['id'] ?></p>
                        <h2><?= e(rotuloStatusPedido($pedido['status'])) ?></h2>
                        <p class="muted"><?= e(date('d/m/Y H:i', strtotime($pedido['created_at']))) ?> · Pagamento: <?= e(strtoupper($pedido['forma_pagamento'])) ?></p>
                    </div>
                    <div class="order-card-end">
                        <strong>R$ <?= number_format((float) $pedido['total'], 2, ',', '.') ?></strong>
                        <a class="btn btn-secondary" href="pedido.php?id=<?= (int) $pedido['id'] ?>">Ver pedido</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/includes/store_footer.php'; ?>
