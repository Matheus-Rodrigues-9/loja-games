<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use RuntimeException;

class Usuario
{
    public function __construct(private PDO $pdo) {}

    public function listar(string $busca = '', string $nivel = ''): array
    {
        $sql = 'SELECT id, nome, email, nivel, created_at FROM usuarios WHERE 1=1';
        $params = [];
        if ($busca !== '') {
            $sql .= ' AND (nome LIKE :termo_nome OR email LIKE :termo_email)';
            $params[':termo_nome'] = '%' . $busca . '%';
            $params[':termo_email'] = '%' . $busca . '%';
        }
        if (in_array($nivel, ['admin', 'operador', 'cliente'], true)) {
            $sql .= ' AND nivel = :nivel';
            $params[':nivel'] = $nivel;
        }
        $sql .= ' ORDER BY nome';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function buscar(int $id): array|false
    {
        $st = $this->pdo->prepare('SELECT id, nome, email, nivel, created_at FROM usuarios WHERE id = :id');
        $st->execute([':id' => $id]);
        return $st->fetch();
    }

    public function emailEmUso(string $email, ?int $ignorarId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM usuarios WHERE email = :email';
        $params = [':email' => strtolower(trim($email))];
        if ($ignorarId !== null) {
            $sql .= ' AND id <> :ignorar_id';
            $params[':ignorar_id'] = $ignorarId;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return (int) $st->fetchColumn() > 0;
    }

    public function criar(array $dados): int
    {
        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare('INSERT INTO usuarios (nome,email,senha,nivel,created_at,updated_at) VALUES (:nome,:email,:senha,:nivel,NOW(),NOW())');
            $st->execute([
                ':nome' => trim($dados['nome']),
                ':email' => strtolower(trim($dados['email'])),
                ':senha' => password_hash($dados['senha'], PASSWORD_DEFAULT),
                ':nivel' => $dados['nivel'],
            ]);
            $id = (int) $this->pdo->lastInsertId();
            if ($dados['nivel'] === 'cliente') {
                $this->pdo->prepare('INSERT INTO clientes (usuario_id,created_at,updated_at) VALUES (:id,NOW(),NOW())')->execute([':id' => $id]);
            }
            $this->pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function atualizar(int $id, array $dados): bool
    {
        $this->pdo->beginTransaction();
        try {
            $sql = 'UPDATE usuarios SET nome=:nome,email=:email,nivel=:nivel,updated_at=NOW()';
            $params = [':id' => $id, ':nome' => trim($dados['nome']), ':email' => strtolower(trim($dados['email'])), ':nivel' => $dados['nivel']];
            if (!empty($dados['senha'])) {
                $sql .= ', senha=:senha';
                $params[':senha'] = password_hash($dados['senha'], PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id=:id';
            $ok = $this->pdo->prepare($sql)->execute($params);
            if ($dados['nivel'] === 'cliente') {
                $this->pdo->prepare('INSERT IGNORE INTO clientes (usuario_id,created_at,updated_at) VALUES (:id,NOW(),NOW())')->execute([':id' => $id]);
            } else {
                $this->pdo->prepare('DELETE FROM clientes WHERE usuario_id=:id')->execute([':id' => $id]);
            }
            $this->pdo->commit();
            return $ok;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function excluir(int $id): bool
    {
        $st = $this->pdo->prepare('SELECT nivel FROM usuarios WHERE id=:id');
        $st->execute([':id' => $id]);
        $nivel = $st->fetchColumn();
        if ($nivel === 'admin' && (int) $this->pdo->query("SELECT COUNT(*) FROM usuarios WHERE nivel='admin'")->fetchColumn() <= 1) {
            throw new RuntimeException('O último administrador não pode ser excluído.');
        }
        try {
            return $this->pdo->prepare('DELETE FROM usuarios WHERE id=:id')->execute([':id' => $id]);
        } catch (\PDOException $e) {
            throw new RuntimeException('Este usuário possui pedidos vinculados e não pode ser excluído.');
        }
    }
}
