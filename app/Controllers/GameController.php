<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Game;
use App\Models\Categoria;

class GameController extends BaseController
{
    public function dispatch(string $action): void
    {
        $model=new Game($this->pdo);
        switch ($action) {
            case 'novo': $this->render('games/form',['pageTitle'=>'Cadastrar jogo','game'=>null,'categorias'=>(new Categoria($this->pdo))->listarParaSelect()]); break;
            case 'salvar':
                $this->requireValidPost(); $dados=$this->dados(); $dados['imagem']=$this->uploadImage(); $model->criar($dados); $this->flash('sucesso','Jogo cadastrado com sucesso.'); $this->redirect('games');
            case 'editar':
                $game=$model->buscar($this->positiveId()); if (!$game) throw new \RuntimeException('Jogo não encontrado.');
                $this->render('games/form',['pageTitle'=>'Editar jogo','game'=>$game,'categorias'=>(new Categoria($this->pdo))->listarParaSelect()]); break;
            case 'atualizar':
                $this->requireValidPost(); $id=$this->positiveId(); $atual=$model->buscar($id); if (!$atual) throw new \RuntimeException('Jogo não encontrado.');
                $dados=$this->dados(); $dados['imagem']=$this->uploadImage($atual['imagem']); $model->atualizar($id,$dados); $this->flash('sucesso','Jogo atualizado com sucesso.'); $this->redirect('games');
            case 'excluir':
                $this->requireValidPost(); $model->excluir($this->positiveId()); $this->flash('sucesso','Jogo excluído.'); $this->redirect('games');
            default:
                $busca=trim((string)($_GET['buscar']??'')); $this->render('games/index',['pageTitle'=>'Gerenciar games','games'=>$model->listar($busca),'busca'=>$busca]);
        }
    }

    private function dados(): array
    {
        $titulo=trim((string)($_POST['titulo']??'')); $descricao=trim((string)($_POST['descricao']??''));
        $preco=filter_var(str_replace(',','.',(string)($_POST['preco']??'')),FILTER_VALIDATE_FLOAT);
        $estoque=filter_var($_POST['estoque']??null,FILTER_VALIDATE_INT); $categoria=filter_var($_POST['categoria_id']??null,FILTER_VALIDATE_INT);
        if ((function_exists('mb_strlen') ? mb_strlen($titulo, 'UTF-8') : strlen($titulo))<2 || $descricao==='' || $preco===false || $preco<0 || $estoque===false || $estoque<0 || !$categoria) throw new \InvalidArgumentException('Preencha título, descrição, preço, estoque e categoria com valores válidos.');
        return ['titulo'=>$titulo,'descricao'=>$descricao,'preco'=>$preco,'estoque'=>$estoque,'categoria_id'=>$categoria];
    }
}
