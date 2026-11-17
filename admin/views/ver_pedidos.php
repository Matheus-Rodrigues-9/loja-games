<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../../includes/cart_helpers.php';

exigirLogin();

$consulta = $pdo->query(
    'SELECT p.id, p.nome_destinatario, p.cidade, p.total, p.status, p.forma_pagamento, p.created_at,
            u.nome AS nome_cliente, u.email AS email_cliente,
            (SELECT COALESCE(SUM(ip.quantidade), 0) FROM itens_pedido ip WHERE ip.pedido_id = p.id) AS unidades
     FROM pedidos p
     INNER JOIN usuarios u ON u.id = p.usuario_id
     ORDER BY p.id DESC'
);
$pedidos = $consulta->fetchAll();

$pageTitle = 'Pedidos de clientes';
require __DIR__ . '/partials/header.php';
?>

<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Vendas da loja</p>
            <h2>Pedidos recebidos</h2>
            <p><?= count($pedidos) ?> pedido(s) registrado(s).</p>
        </div>
    </div>

    <?php if (!$pedidos): ?>
        <div class="empty-state">Ainda não existem pedidos. Quando um cliente finalizar uma compra, ela aparecerá aqui.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Pedido</th>
                    <th>Cliente</th>
                    <th>Data</th>
                    <th>Unidades</th>
                    <th>Pagamento</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Atualizar</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pedidos as $pedido): ?>
                    <tr>
                        <td><strong>#<?= (int) $pedido['id'] ?></strong></td>
                        <td class="text-left"><?= e($pedido['nome_cliente']) ?><br><small class="muted"><?= e($pedido['email_cliente']) ?></small></td>
                        <td><?= e(date('d/m/Y H:i', strtotime($pedido['created_at']))) ?></td>
                        <td><?= (int) $pedido['unidades'] ?></td>
                        <td><?= e(strtoupper($pedido['forma_pagamento'])) ?></td>
                        <td>R$ <?= number_format((float) $pedido['total'], 2, ',', '.') ?></td>
                        <td><span class="order-status"><?= e(rotuloStatusPedido($pedido['status'])) ?></span></td>
                        <td>
                            <?php if ($pedido['status'] === 'cancelado'): ?>
                                <span class="muted">Cancelado</span>
                            <?php else: ?>
                                <form method="post" action="../controllers/atualizar_pedido.php" class="order-status-form">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $pedido['id'] ?>">
                                    <?php
                                    $opcoesStatus = match ($pedido['status']) {
                                        'recebido' => ['recebido' => 'Recebido', 'em_preparacao' => 'Em preparação', 'cancelado' => 'Cancelar'],
                                        'em_preparacao' => ['em_preparacao' => 'Em preparação', 'enviado' => 'Enviado', 'cancelado' => 'Cancelar'],
                                        'enviado' => ['enviado' => 'Enviado', 'concluido' => 'Concluído'],
                                        'concluido' => ['concluido' => 'Concluído'],
                                        default => [$pedido['status'] => rotuloStatusPedido($pedido['status'])],
                                    };
                                    ?>
                                    <select name="status" aria-label="Status do pedido #<?= (int) $pedido['id'] ?>">
                                        <?php foreach ($opcoesStatus as $valorStatus => $rotulo): ?>
                                            <option value="<?= e($valorStatus) ?>" <?= $pedido['status'] === $valorStatus ? 'selected' : '' ?>><?= e($rotulo) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-small btn-secondary" type="submit">Salvar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="muted small-note">Ao cancelar um pedido, as unidades dos jogos são devolvidas automaticamente ao estoque.</p>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
