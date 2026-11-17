<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';

exigirLogin('../../login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validarCsrf($_POST['csrf_token'] ?? null)) {
    definirFlash('erro', 'Solicitação inválida.');
    header('Location: ../views/ver_pedidos.php');
    exit;
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$status = (string) ($_POST['status'] ?? '');
$permitidos = ['recebido', 'em_preparacao', 'enviado', 'concluido', 'cancelado'];

if (!$id || !in_array($status, $permitidos, true)) {
    definirFlash('erro', 'Pedido ou status inválido.');
    header('Location: ../views/ver_pedidos.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $consulta = $pdo->prepare('SELECT id, status FROM pedidos WHERE id = :id FOR UPDATE');
    $consulta->execute([':id' => $id]);
    $pedido = $consulta->fetch();

    if (!$pedido) {
        throw new RuntimeException('Pedido não encontrado.');
    }

    if ($pedido['status'] === 'cancelado') {
        throw new RuntimeException('Um pedido cancelado não pode ser reaberto por esta tela.');
    }

    $transicoesPermitidas = [
        'recebido' => ['recebido', 'em_preparacao', 'cancelado'],
        'em_preparacao' => ['em_preparacao', 'enviado', 'cancelado'],
        'enviado' => ['enviado', 'concluido'],
        'concluido' => ['concluido'],
    ];

    if (!in_array($status, $transicoesPermitidas[$pedido['status']] ?? [], true)) {
        throw new RuntimeException('Essa mudança de status não é permitida para o pedido.');
    }

    if ($status === 'cancelado') {
        $consultaItens = $pdo->prepare('SELECT game_id, quantidade FROM itens_pedido WHERE pedido_id = :pedido_id');
        $consultaItens->execute([':pedido_id' => $id]);
        $itens = $consultaItens->fetchAll();

        $reporEstoque = $pdo->prepare('UPDATE games SET estoque = estoque + :quantidade, updated_at = NOW() WHERE id = :id');
        foreach ($itens as $item) {
            if ($item['game_id'] !== null) {
                $reporEstoque->execute([
                    ':quantidade' => (int) $item['quantidade'],
                    ':id' => (int) $item['game_id'],
                ]);
            }
        }
    }

    $atualizar = $pdo->prepare('UPDATE pedidos SET status = :status, updated_at = NOW() WHERE id = :id');
    $atualizar->execute([':status' => $status, ':id' => $id]);
    $pdo->commit();

    definirFlash('sucesso', $status === 'cancelado'
        ? 'Pedido cancelado e estoque reposto.'
        : 'Status do pedido atualizado.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    definirFlash('erro', $e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível atualizar o pedido.');
}

header('Location: ../views/ver_pedidos.php');
exit;
