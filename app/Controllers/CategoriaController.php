<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Categoria;

class CategoriaController extends BaseController
{
    public function dispatch(string $action): void
    {
        $model=new Categoria($this->pdo);
        switch ($action) {
            case 'novo': $this->render('categorias/form',['pageTitle'=>'Cadastrar categoria','categoria'=>null]); break;
            case 'salvar': $this->requireValidPost(); $d=$this->dados(); $model->criar($d); $this->flash('sucesso','Categoria cadastrada.'); $this->redirect('categorias');
            case 'editar': $item=$model->buscar($this->positiveId()); if (!$item) throw new \RuntimeException('Categoria não encontrada.'); $this->render('categorias/form',['pageTitle'=>'Editar categoria','categoria'=>$item]); break;
            case 'atualizar': $this->requireValidPost(); $model->atualizar($this->positiveId(),$this->dados()); $this->flash('sucesso','Categoria atualizada.'); $this->redirect('categorias');
            case 'excluir': $this->requireValidPost(); $model->excluir($this->positiveId()); $this->flash('sucesso','Categoria excluída.'); $this->redirect('categorias');
            default: $busca=trim((string)($_GET['buscar']??'')); $this->render('categorias/index',['pageTitle'=>'Gerenciar categorias','categorias'=>$model->listar($busca),'busca'=>$busca]);
        }
    }
    private function dados(): array
    {
        $nome=trim((string)($_POST['nome']??'')); $descricao=trim((string)($_POST['descricao']??''));
        if ((function_exists('mb_strlen') ? mb_strlen($nome, 'UTF-8') : strlen($nome))<2) throw new \InvalidArgumentException('Informe um nome de categoria com pelo menos 2 caracteres.');
        return ['nome'=>$nome,'descricao'=>$descricao];
    }
}
