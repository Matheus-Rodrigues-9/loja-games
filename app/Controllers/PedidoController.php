<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Pedido;
use App\Models\Cliente;
use App\Models\Game;

class PedidoController extends BaseController
{
    public function dispatch(string $action): void
    {
        $model=new Pedido($this->pdo);
        switch ($action) {
            case 'novo':
                $this->render('pedidos/form',['pageTitle'=>'Registrar pedido','clientes'=>(new Cliente($this->pdo))->listarParaSelect(),'games'=>(new Game($this->pdo))->listar()]); break;
            case 'salvar':
                $this->requireValidPost();
                $clienteId=filter_var($_POST['usuario_id']??null,FILTER_VALIDATE_INT); $gameId=filter_var($_POST['game_id']??null,FILTER_VALIDATE_INT); $qtd=filter_var($_POST['quantidade']??null,FILTER_VALIDATE_INT);
                if (!$clienteId || !$gameId || !$qtd || $qtd<1) throw new \InvalidArgumentException('Selecione um cliente, um jogo e uma quantidade válida.');
                $clienteSt=$this->pdo->prepare('SELECT c.usuario_id,u.nome FROM clientes c JOIN usuarios u ON u.id=c.usuario_id WHERE c.id=:id'); $clienteSt->execute([':id'=>$clienteId]); $cliente=$clienteSt->fetch();
                if (!$cliente) throw new \InvalidArgumentException('Cliente não encontrado.');
                $pedidoId=$model->criar(['usuario_id'=>$cliente['usuario_id'],'nome_destinatario'=>$cliente['nome'],'endereco'=>trim((string)($_POST['endereco']??'')),'cidade'=>trim((string)($_POST['cidade']??'')),'cep'=>trim((string)($_POST['cep']??'')),'forma_pagamento'=>(string)($_POST['forma_pagamento']??'pix')],[['game_id'=>$gameId,'quantidade'=>$qtd]]);
                $this->flash('sucesso','Pedido registrado com sucesso.'); $this->redirect('pedidos','detalhe',['id'=>$pedidoId]);
            case 'detalhe': $pedido=$model->buscar($this->positiveId()); if (!$pedido) throw new \RuntimeException('Pedido não encontrado.'); $this->render('pedidos/detalhe',['pageTitle'=>'Detalhes do pedido #'.$pedido['id'],'pedido'=>$pedido]); break;
            case 'atualizar': $this->requireValidPost(); $model->atualizarStatus($this->positiveId(),(string)($_POST['status']??'')); $this->flash('sucesso','Status do pedido atualizado.'); $this->redirect('pedidos');
            case 'excluir': $this->requireValidPost(); $model->excluir($this->positiveId()); $this->flash('sucesso','Pedido cancelado excluído do histórico administrativo.'); $this->redirect('pedidos');
            default: $busca=trim((string)($_GET['buscar']??'')); $status=trim((string)($_GET['status']??'')); $this->render('pedidos/index',['pageTitle'=>'Gerenciar pedidos','pedidos'=>$model->listar($busca,$status),'busca'=>$busca,'status'=>$status]);
        }
    }
}
