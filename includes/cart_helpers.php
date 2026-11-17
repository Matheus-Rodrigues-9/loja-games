<?php

declare(strict_types=1);

/**
 * Lê o carrinho da sessão, remove produtos excluídos e ajusta quantidades
 * quando o estoque foi alterado no painel administrativo.
 *
 * @return array<int, array<string, mixed>>
 */
function carregarItensCarrinho(PDO $pdo): array
{
    $carrinho = $_SESSION['carrinho'] ?? [];
    if (!is_array($carrinho)) {
        $carrinho = [];
    }

    $consulta = $pdo->prepare(
        'SELECT id, titulo, descricao, preco, estoque, categoria, imagem
         FROM games WHERE id = :id LIMIT 1'
    );

    $itens = [];
    $houveAjuste = false;

    foreach ($carrinho as $idBruto => $quantidadeBruta) {
        $id = filter_var($idBruto, FILTER_VALIDATE_INT);
        $quantidade = filter_var($quantidadeBruta, FILTER_VALIDATE_INT);

        if (!$id || !$quantidade || $quantidade < 1) {
            unset($carrinho[$idBruto]);
            $houveAjuste = true;
            continue;
        }

        $consulta->execute([':id' => $id]);
        $jogo = $consulta->fetch();

        if (!$jogo || (int) $jogo['estoque'] < 1) {
            unset($carrinho[$idBruto]);
            $houveAjuste = true;
            continue;
        }

        $estoque = (int) $jogo['estoque'];
        if ($quantidade > $estoque) {
            $quantidade = $estoque;
            $carrinho[$id] = $quantidade;
            $houveAjuste = true;
        }

        $jogo['quantidade'] = $quantidade;
        $jogo['subtotal'] = (float) $jogo['preco'] * $quantidade;
        $itens[] = $jogo;
    }

    $_SESSION['carrinho'] = $carrinho;

    if ($houveAjuste) {
        definirFlash('aviso', 'O carrinho foi atualizado porque um jogo foi removido ou o estoque mudou.');
    }

    return $itens;
}

function totalCarrinho(array $itens): float
{
    $total = 0.0;
    foreach ($itens as $item) {
        $total += (float) $item['subtotal'];
    }
    return round($total, 2);
}

function rotuloStatusPedido(string $status): string
{
    return [
        'recebido' => 'Pedido recebido',
        'em_preparacao' => 'Em preparação',
        'enviado' => 'Enviado',
        'concluido' => 'Concluído',
        'cancelado' => 'Cancelado',
    ][$status] ?? ucfirst(str_replace('_', ' ', $status));
}
