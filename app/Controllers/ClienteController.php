<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Cliente;

class ClienteController extends BaseController
{
    public function dispatch(string $action): void
    {
        $model=new Cliente($this->pdo);
        switch ($action) {
            case 'novo': $this->render('clientes/form',['pageTitle'=>'Cadastrar cliente','cliente'=>null]); break;
            case 'salvar': $this->requireValidPost(); $d=$this->dados(true); $model->criar($d); $this->flash('sucesso','Cliente cadastrado e conta de acesso criada.'); $this->redirect('clientes');
            case 'editar': $item=$model->buscar($this->positiveId()); if (!$item) throw new \RuntimeException('Cliente não encontrado.'); $this->render('clientes/form',['pageTitle'=>'Editar cliente','cliente'=>$item]); break;
            case 'atualizar': $this->requireValidPost(); $model->atualizar($this->positiveId(),$this->dados(false)); $this->flash('sucesso','Cadastro do cliente atualizado.'); $this->redirect('clientes');
            case 'excluir': $this->requireValidPost(); $model->excluir($this->positiveId()); $this->flash('sucesso','Cliente excluído.'); $this->redirect('clientes');
            default: $busca=trim((string)($_GET['buscar']??'')); $this->render('clientes/index',['pageTitle'=>'Gerenciar clientes','clientes'=>$model->listar($busca),'busca'=>$busca]);
        }
    }
    private function dados(bool $senhaObrigatoria): array
    {
        $d=['nome'=>trim((string)($_POST['nome']??'')),'email'=>strtolower(trim((string)($_POST['email']??''))),'telefone'=>trim((string)($_POST['telefone']??'')),'endereco'=>trim((string)($_POST['endereco']??'')),'cidade'=>trim((string)($_POST['cidade']??'')),'cep'=>trim((string)($_POST['cep']??'')),'senha'=>(string)($_POST['senha']??'')];
        if ((function_exists('mb_strlen') ? mb_strlen($d['nome'], 'UTF-8') : strlen($d['nome']))<2 || !filter_var($d['email'],FILTER_VALIDATE_EMAIL)) throw new \InvalidArgumentException('Informe nome e e-mail válido.');
        if ($senhaObrigatoria && strlen(preg_replace('/\D+/', '', $d['telefone']) ?? '') < 10) throw new \InvalidArgumentException('Informe telefone com DDD para o novo cliente.');
        if (($senhaObrigatoria && strlen($d['senha'])<8) || ($d['senha']!=='' && strlen($d['senha'])<8)) throw new \InvalidArgumentException('A senha deve conter pelo menos 8 caracteres.');
        return $d;
    }
}
