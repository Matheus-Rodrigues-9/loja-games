<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use RuntimeException;

class Pedido
{
    private const STATUS = ['recebido','em_preparacao','enviado','concluido','cancelado'];

    public function __construct(private PDO $pdo) {}

    public function listar(string $busca = '', string $status = ''): array
    {
        $sql='SELECT p.*,u.nome AS nome_cliente,u.email AS email_cliente,(SELECT COALESCE(SUM(ip.quantidade),0) FROM itens_pedido ip WHERE ip.pedido_id=p.id) AS unidades FROM pedidos p JOIN usuarios u ON u.id=p.usuario_id WHERE 1=1';
        $params=[];
        if ($busca!=='') { $sql.=' AND (CAST(p.id AS CHAR) LIKE :pedido_id OR u.nome LIKE :cliente OR u.email LIKE :email)'; $term='%'.$busca.'%'; $params=[':pedido_id'=>$term,':cliente'=>$term,':email'=>$term]; }
        if (in_array($status,self::STATUS,true)) { $sql.=' AND p.status=:status'; $params[':status']=$status; }
        $sql.=' ORDER BY p.id DESC'; $st=$this->pdo->prepare($sql); $st->execute($params); return $st->fetchAll();
    }

    public function buscar(int $id): array|false
    {
        $st=$this->pdo->prepare('SELECT p.*,u.nome AS nome_cliente,u.email AS email_cliente FROM pedidos p JOIN usuarios u ON u.id=p.usuario_id WHERE p.id=:id');
        $st->execute([':id'=>$id]); $pedido=$st->fetch();
        if (!$pedido) return false;
        $itens=$this->pdo->prepare('SELECT * FROM itens_pedido WHERE pedido_id=:pedido_id ORDER BY id'); $itens->execute([':pedido_id'=>$id]);
        $pedido['itens']=$itens->fetchAll(); return $pedido;
    }

    /** Cria um pedido e baixa o estoque dentro de uma transação. Cada item deve conter game_id e quantidade. */
    public function criar(array $dados, array $itens): int
    {
        if (!$itens) throw new RuntimeException('Adicione pelo menos um jogo ao pedido.');
        $pagamento = (string)($dados['forma_pagamento'] ?? 'pix');
        if (!in_array($pagamento, ['pix','cartao','boleto'], true)) throw new RuntimeException('Forma de pagamento inválida.');
        if ((int)($dados['usuario_id'] ?? 0) < 1 || trim((string)($dados['nome_destinatario'] ?? '')) === '' || trim((string)($dados['endereco'] ?? '')) === '' || trim((string)($dados['cidade'] ?? '')) === '' || trim((string)($dados['cep'] ?? '')) === '') throw new RuntimeException('Informe cliente e dados de entrega válidos.');
        $iniciouTransacao=!$this->pdo->inTransaction();
        if ($iniciouTransacao) $this->pdo->beginTransaction();
        try {
            $confirmados=[]; $subtotal=0.0;
            $buscar=$this->pdo->prepare('SELECT id,titulo,preco,estoque FROM games WHERE id=:id FOR UPDATE');
            foreach ($itens as $item) {
                $gameId=(int)($item['game_id'] ?? $item['id'] ?? 0); $quantidade=(int)($item['quantidade'] ?? 0);
                if ($gameId<1 || $quantidade<1) throw new RuntimeException('Item ou quantidade inválida.');
                $buscar->execute([':id'=>$gameId]); $game=$buscar->fetch();
                if (!$game) throw new RuntimeException('Um dos jogos não está mais disponível.');
                if ((int)$game['estoque']<$quantidade) throw new RuntimeException('Estoque insuficiente para '.$game['titulo'].'.');
                $linha=round((float)$game['preco']*$quantidade,2); $subtotal+=$linha;
                $confirmados[]=['id'=>$gameId,'titulo'=>$game['titulo'],'preco'=>(float)$game['preco'],'quantidade'=>$quantidade,'subtotal'=>$linha];
            }
            $subtotal=round($subtotal,2); $frete=(float)($dados['frete'] ?? 0);
            $insert=$this->pdo->prepare("INSERT INTO pedidos (usuario_id,nome_destinatario,endereco,cidade,cep,forma_pagamento,status,subtotal,frete,total,created_at,updated_at) VALUES (:usuario_id,:nome_destinatario,:endereco,:cidade,:cep,:forma_pagamento,'recebido',:subtotal,:frete,:total,NOW(),NOW())");
            $insert->execute([
                ':usuario_id'=>(int)$dados['usuario_id'], ':nome_destinatario'=>trim($dados['nome_destinatario']), ':endereco'=>trim($dados['endereco']), ':cidade'=>trim($dados['cidade']), ':cep'=>trim($dados['cep']), ':forma_pagamento'=>$pagamento, ':subtotal'=>$subtotal, ':frete'=>$frete, ':total'=>round($subtotal+$frete,2),
            ]);
            $pedidoId=(int)$this->pdo->lastInsertId();
            $insertItem=$this->pdo->prepare('INSERT INTO itens_pedido (pedido_id,game_id,titulo_jogo,preco_unitario,quantidade,subtotal) VALUES (:pedido_id,:game_id,:titulo,:preco,:quantidade,:subtotal)');
            $baixar=$this->pdo->prepare('UPDATE games SET estoque=estoque-:qtd_baixa,updated_at=NOW() WHERE id=:id AND estoque>=:qtd_minima');
            foreach ($confirmados as $item) {
                $insertItem->execute([':pedido_id'=>$pedidoId,':game_id'=>$item['id'],':titulo'=>$item['titulo'],':preco'=>$item['preco'],':quantidade'=>$item['quantidade'],':subtotal'=>$item['subtotal']]);
                $baixar->execute([':qtd_baixa'=>$item['quantidade'],':id'=>$item['id'],':qtd_minima'=>$item['quantidade']]);
                if ($baixar->rowCount()!==1) throw new RuntimeException('O estoque foi alterado durante a compra. Tente novamente.');
            }
            if ($iniciouTransacao) $this->pdo->commit();
            return $pedidoId;
        } catch (\Throwable $e) {
            if ($iniciouTransacao && $this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function atualizarStatus(int $id, string $novoStatus): bool
    {
        if (!in_array($novoStatus,self::STATUS,true)) throw new RuntimeException('Status inválido.');
        $this->pdo->beginTransaction();
        try {
            $st=$this->pdo->prepare('SELECT id,status FROM pedidos WHERE id=:id FOR UPDATE'); $st->execute([':id'=>$id]); $pedido=$st->fetch();
            if (!$pedido) throw new RuntimeException('Pedido não encontrado.');
            $transicoes=['recebido'=>['recebido','em_preparacao','cancelado'],'em_preparacao'=>['em_preparacao','enviado','cancelado'],'enviado'=>['enviado','concluido'],'concluido'=>['concluido'],'cancelado'=>['cancelado']];
            if (!in_array($novoStatus,$transicoes[$pedido['status']] ?? [],true)) throw new RuntimeException('A mudança de status não é permitida.');
            if ($pedido['status']!=='cancelado' && $novoStatus==='cancelado') {
                $itens=$this->pdo->prepare('SELECT game_id,quantidade FROM itens_pedido WHERE pedido_id=:pedido_id'); $itens->execute([':pedido_id'=>$id]);
                $repor=$this->pdo->prepare('UPDATE games SET estoque=estoque+:qtd,updated_at=NOW() WHERE id=:game_id');
                foreach ($itens->fetchAll() as $item) if ($item['game_id']!==null) $repor->execute([':qtd'=>(int)$item['quantidade'],':game_id'=>(int)$item['game_id']]);
            }
            $this->pdo->prepare('UPDATE pedidos SET status=:status,updated_at=NOW() WHERE id=:id')->execute([':status'=>$novoStatus,':id'=>$id]);
            $this->pdo->commit(); return true;
        } catch (\Throwable $e) { if ($this->pdo->inTransaction()) $this->pdo->rollBack(); throw $e; }
    }

    /** Exclusão física é permitida somente para pedidos já cancelados. */
    public function excluir(int $id): bool
    {
        $st=$this->pdo->prepare('SELECT status FROM pedidos WHERE id=:id'); $st->execute([':id'=>$id]); $status=$st->fetchColumn();
        if (!$status) return false;
        if ($status!=='cancelado') throw new RuntimeException('Cancele o pedido antes de excluí-lo.');
        return $this->pdo->prepare('DELETE FROM pedidos WHERE id=:id')->execute([':id'=>$id]);
    }
}
