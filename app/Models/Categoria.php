<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use RuntimeException;

class Categoria
{
    public function __construct(private PDO $pdo) {}

    public function listar(string $busca = ''): array
    {
        $sql = 'SELECT c.id,c.nome,c.descricao,c.created_at,COUNT(g.id) AS total_games FROM categorias c LEFT JOIN games g ON g.categoria_id=c.id';
        $params = [];
        if ($busca !== '') { $sql .= ' WHERE c.nome LIKE :nome'; $params[':nome'] = '%' . $busca . '%'; }
        $sql .= ' GROUP BY c.id,c.nome,c.descricao,c.created_at ORDER BY c.nome';
        $st = $this->pdo->prepare($sql); $st->execute($params); return $st->fetchAll();
    }

    public function listarParaSelect(): array
    {
        return $this->pdo->query('SELECT id,nome FROM categorias ORDER BY nome')->fetchAll();
    }

    public function buscar(int $id): array|false
    {
        $st=$this->pdo->prepare('SELECT * FROM categorias WHERE id=:id'); $st->execute([':id'=>$id]); return $st->fetch();
    }

    public function criar(array $dados): bool
    {
        return $this->pdo->prepare('INSERT INTO categorias (nome,descricao,created_at,updated_at) VALUES (:nome,:descricao,NOW(),NOW())')->execute([
            ':nome'=>trim($dados['nome']), ':descricao'=>trim($dados['descricao'] ?? ''),
        ]);
    }

    public function atualizar(int $id, array $dados): bool
    {
        $categoria=$this->buscar($id); if (!$categoria) throw new RuntimeException('Categoria não encontrada.');
        $this->pdo->beginTransaction();
        try {
            $st=$this->pdo->prepare('UPDATE categorias SET nome=:nome,descricao=:descricao,updated_at=NOW() WHERE id=:id');
            $st->execute([':id'=>$id, ':nome'=>trim($dados['nome']), ':descricao'=>trim($dados['descricao'] ?? '')]);
            $sync=$this->pdo->prepare('UPDATE games SET categoria=:nome,updated_at=NOW() WHERE categoria_id=:id');
            $sync->execute([':nome'=>trim($dados['nome']), ':id'=>$id]);
            $this->pdo->commit(); return true;
        } catch (\Throwable $e) { if ($this->pdo->inTransaction()) $this->pdo->rollBack(); throw $e; }
    }

    public function excluir(int $id): bool
    {
        $st=$this->pdo->prepare('SELECT COUNT(*) FROM games WHERE categoria_id=:id'); $st->execute([':id'=>$id]);
        if ((int)$st->fetchColumn()>0) throw new RuntimeException('Não é possível excluir: existem jogos vinculados a esta categoria.');
        return $this->pdo->prepare('DELETE FROM categorias WHERE id=:id')->execute([':id'=>$id]);
    }
}
