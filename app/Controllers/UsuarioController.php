<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Usuario;

class UsuarioController extends BaseController
{
    public function dispatch(string $action): void
    {
        $model=new Usuario($this->pdo);
        switch ($action) {
            case 'novo': $this->render('usuarios/form',['pageTitle'=>'Cadastrar usuário','usuario'=>null]); break;
            case 'salvar': $this->requireValidPost(); $d=$this->dados(true); if ($model->emailEmUso($d['email'])) throw new \RuntimeException('Este e-mail já está cadastrado.'); $model->criar($d); $this->flash('sucesso','Usuário cadastrado.'); $this->redirect('usuarios');
            case 'editar': $item=$model->buscar($this->positiveId()); if (!$item) throw new \RuntimeException('Usuário não encontrado.'); $this->render('usuarios/form',['pageTitle'=>'Editar usuário','usuario'=>$item]); break;
            case 'atualizar': $this->requireValidPost(); $id=$this->positiveId(); $d=$this->dados(false); if ($model->emailEmUso($d['email'],$id)) throw new \RuntimeException('Este e-mail já está cadastrado.'); $model->atualizar($id,$d); $this->flash('sucesso','Usuário atualizado.'); $this->redirect('usuarios');
            case 'excluir': $this->requireValidPost(); $id=$this->positiveId(); if ($id===(int)($_SESSION['usuario_id']??0)) throw new \RuntimeException('Você não pode excluir a própria conta.'); $model->excluir($id); $this->flash('sucesso','Usuário excluído.'); $this->redirect('usuarios');
            default: $busca=trim((string)($_GET['buscar']??'')); $nivel=trim((string)($_GET['nivel']??'')); $this->render('usuarios/index',['pageTitle'=>'Gerenciar usuários','usuarios'=>$model->listar($busca,$nivel),'busca'=>$busca,'nivel'=>$nivel]);
        }
    }
    private function dados(bool $senhaObrigatoria): array
    {
        $nome=trim((string)($_POST['nome']??'')); $email=strtolower(trim((string)($_POST['email']??''))); $senha=(string)($_POST['senha']??''); $nivel=(string)($_POST['nivel']??'operador');
        if ((function_exists('mb_strlen') ? mb_strlen($nome, 'UTF-8') : strlen($nome))<2 || !filter_var($email,FILTER_VALIDATE_EMAIL) || !in_array($nivel,['admin','operador','cliente'],true)) throw new \InvalidArgumentException('Informe nome, e-mail válido e nível de acesso.');
        if (($senhaObrigatoria && strlen($senha)<8) || ($senha!=='' && strlen($senha)<8)) throw new \InvalidArgumentException('A senha deve conter pelo menos 8 caracteres.');
        return ['nome'=>$nome,'email'=>$email,'senha'=>$senha,'nivel'=>$nivel];
    }
}
