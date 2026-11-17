<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Game
{
    public function __construct(private PDO $pdo) {}

    public function listar(string $busca = ''): array
    {
        $sql='SELECT g.*,COALESCE(c.nome,g.categoria) AS categoria_nome FROM games g LEFT JOIN categorias c ON c.id=g.categoria_id';
        $params=[];
        if ($busca!=='') { $sql.=' WHERE g.titulo LIKE :titulo OR g.descricao LIKE :descricao'; $params=[':titulo'=>'%'.$busca.'%',':descricao'=>'%'.$busca.'%']; }
        $sql.=' ORDER BY g.titulo'; $st=$this->pdo->prepare($sql); $st->execute($params); return $st->fetchAll();
    }

    public function buscar(int $id): array|false
    {
        $st=$this->pdo->prepare('SELECT g.*,COALESCE(c.nome,g.categoria) AS categoria_nome FROM games g LEFT JOIN categorias c ON c.id=g.categoria_id WHERE g.id=:id');
        $st->execute([':id'=>$id]); return $st->fetch();
    }

    public function criar(array $dados): bool
    {
        $categoria=$this->nomeCategoria((int)$dados['categoria_id']);
        return $this->pdo->prepare('INSERT INTO games (titulo,descricao,preco,estoque,categoria,categoria_id,imagem,created_at,updated_at) VALUES (:titulo,:descricao,:preco,:estoque,:categoria,:categoria_id,:imagem,NOW(),NOW())')->execute([
            ':titulo'=>trim($dados['titulo']), ':descricao'=>trim($dados['descricao']), ':preco'=>$dados['preco'], ':estoque'=>$dados['estoque'], ':categoria'=>$categoria, ':categoria_id'=>$dados['categoria_id'], ':imagem'=>$dados['imagem'] ?: null,
        ]);
    }

    public function atualizar(int $id, array $dados): bool
    {
        $categoria=$this->nomeCategoria((int)$dados['categoria_id']);
        return $this->pdo->prepare('UPDATE games SET titulo=:titulo,descricao=:descricao,preco=:preco,estoque=:estoque,categoria=:categoria,categoria_id=:categoria_id,imagem=:imagem,updated_at=NOW() WHERE id=:id')->execute([
            ':id'=>$id, ':titulo'=>trim($dados['titulo']), ':descricao'=>trim($dados['descricao']), ':preco'=>$dados['preco'], ':estoque'=>$dados['estoque'], ':categoria'=>$categoria, ':categoria_id'=>$dados['categoria_id'], ':imagem'=>$dados['imagem'] ?: null,
        ]);
    }

    public function excluir(int $id): bool
    {
        return $this->pdo->prepare('DELETE FROM games WHERE id=:id')->execute([':id'=>$id]);
    }

    private function nomeCategoria(int $id): string
    {
        $st=$this->pdo->prepare('SELECT nome FROM categorias WHERE id=:id'); $st->execute([':id'=>$id]);
        $nome=$st->fetchColumn(); if (!$nome) throw new \InvalidArgumentException('Selecione uma categoria válida.'); return (string)$nome;
    }
}
