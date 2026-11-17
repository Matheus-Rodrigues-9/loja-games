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

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$consulta = $pdo->prepare(
    'SELECT * FROM pedidos WHERE id = :id AND usuario_id = :usuario_id LIMIT 1'
);
$consulta->execute([
    ':id' => (int) ($id ?: 0),
    ':usuario_id' => (int) $_SESSION['usuario_id'],
]);
$pedido = $consulta->fetch();

if (!$pedido) {
    http_response_code(404);
}

$itens = [];
if ($pedido) {
    $consultaItens = $pdo->prepare(
        'SELECT titulo_jogo, preco_unitario, quantidade, subtotal
         FROM itens_pedido WHERE pedido_id = :pedido_id ORDER BY id ASC'
    );
    $consultaItens->execute([':pedido_id' => (int) $pedido['id']]);
    $itens = $consultaItens->fetchAll();
}

$storeTitle = $pedido ? 'Pedido #' . (int) $pedido['id'] : 'Pedido não encontrado';
require __DIR__ . '/includes/store_header.php';
?>

<main class="container page-content">
    <?php if (!$pedido): ?>
        <section class="panel empty-state large">
            <h1>Pedido não encontrado</h1>
            <p>Esse pedido não existe ou não pertence à sua conta.</p>
            <a class="btn btn-primary" href="meus_pedidos.php">Voltar aos meus pedidos</a>
        </section>
    <?php else: ?>
        <div class="page-heading">
            <div>
                <p class="eyebrow">Compra registrada</p>
                <h1>Pedido #<?= (int) $pedido['id'] ?></h1>
                <p class="muted">Realizado em <?= e(date('d/m/Y H:i', strtotime($pedido['created_at']))) ?></p>
            </div>
            <span class="order-status"><?= e(rotuloStatusPedido($pedido['status'])) ?></span>
        </div>

        <div class="alert alert-sucesso">Seu pedido foi registrado. Esta versão é demonstrativa: o pagamento não é enviado a um banco ou operadora.</div>

        <div class="checkout-grid">
            <section class="panel">
                <h2>Itens do pedido</h2>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Jogo</th><th>Preço unitário</th><th>Qtd.</th><th>Subtotal</th></tr></thead>
                        <tbody>
                        <?php foreach ($itens as $item): ?>
                            <tr>
                                <td class="text-left"><?= e($item['titulo_jogo']) ?></td>
                                <td>R$ <?= number_format((float) $item['preco_unitario'], 2, ',', '.') ?></td>
                                <td><?= (int) $item['quantidade'] ?></td>
                                <td>R$ <?= number_format((float) $item['subtotal'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <aside class="panel cart-summary">
                <p class="eyebrow">Entrega e pagamento</p>
                <h2>Resumo</h2>
                <p><strong>Destinatário</strong><br><?= e($pedido['nome_destinatario']) ?></p>
                <p><strong>Endereço</strong><br><?= e($pedido['endereco']) ?><br><?= e($pedido['cidade']) ?> · CEP <?= e($pedido['cep']) ?></p>
                <p><strong>Forma de pagamento</strong><br><?= e(strtoupper($pedido['forma_pagamento'])) ?> (simulação)</p>
                <div class="summary-total"><span>Total</span><strong>R$ <?= number_format((float) $pedido['total'], 2, ',', '.') ?></strong></div>
                <a class="btn btn-primary btn-block" href="meus_pedidos.php">Meus pedidos</a>
                <a class="btn btn-ghost btn-block" href="index.php#catalogo">Continuar comprando</a>
            </aside>
        </div>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/includes/store_footer.php'; ?>
