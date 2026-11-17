<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/conexao.php';

class Usuario
{
    private PDO $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function listar(): array
    {
        return $this->filtrar();
    }

    public function filtrar(string $busca = '', string $nivel = ''): array
    {
        $sql = 'SELECT id, nome, email, nivel, created_at, updated_at FROM usuarios WHERE 1=1';
        $params = [];

        if ($busca !== '') {
            $sql .= ' AND (nome LIKE :busca_nome OR email LIKE :busca_email)';
            $params[':busca_nome'] = '%' . $busca . '%';
            $params[':busca_email'] = '%' . $busca . '%';
        }

        if (in_array($nivel, ['admin', 'operador', 'cliente'], true)) {
            $sql .= ' AND nivel = :nivel';
            $params[':nivel'] = $nivel;
        }

        $sql .= ' ORDER BY nome ASC';

        $sth = $this->pdo->prepare($sql);
        $sth->execute($params);

        return $sth->fetchAll();
    }

    public function buscarPorId(int $id): array|false
    {
        $sth = $this->pdo->prepare('SELECT id, nome, email, nivel, created_at, updated_at FROM usuarios WHERE id = :id');
        $sth->execute([':id' => $id]);
        return $sth->fetch();
    }

    public function buscarPorEmail(string $email): array|false
    {
        $sth = $this->pdo->prepare('SELECT * FROM usuarios WHERE email = :email LIMIT 1');
        $sth->execute([':email' => strtolower(trim($email))]);
        return $sth->fetch();
    }

    public function criar(string $nome, string $email, string $senha, string $nivel = 'operador'): bool
    {
        $this->pdo->beginTransaction();
        try {
            $sth = $this->pdo->prepare(
                'INSERT INTO usuarios (nome, email, senha, nivel, created_at, updated_at)
                 VALUES (:nome, :email, :senha, :nivel, NOW(), NOW())'
            );
            $ok = $sth->execute([
                ':nome' => trim($nome),
                ':email' => strtolower(trim($email)),
                ':senha' => password_hash($senha, PASSWORD_DEFAULT),
                ':nivel' => $nivel,
            ]);
            if ($nivel === 'cliente') {
                $this->pdo->prepare('INSERT INTO clientes (usuario_id,created_at,updated_at) VALUES (:id,NOW(),NOW())')->execute([':id' => (int)$this->pdo->lastInsertId()]);
            }
            $this->pdo->commit();
            return $ok;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function editar(int $id, string $nome, string $email, string $nivel, ?string $novaSenha = null): bool
    {
        $this->pdo->beginTransaction();
        try {
            $sql = 'UPDATE usuarios SET nome = :nome, email = :email, nivel = :nivel, updated_at = NOW()';
            $params = [':id' => $id, ':nome' => trim($nome), ':email' => strtolower(trim($email)), ':nivel' => $nivel];
            if ($novaSenha !== null && $novaSenha !== '') {
                $sql .= ', senha = :senha';
                $params[':senha'] = password_hash($novaSenha, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id = :id';
            $ok = $this->pdo->prepare($sql)->execute($params);
            if ($nivel === 'cliente') {
                $this->pdo->prepare('INSERT IGNORE INTO clientes (usuario_id,created_at,updated_at) VALUES (:id,NOW(),NOW())')->execute([':id' => $id]);
            } else {
                $this->pdo->prepare('DELETE FROM clientes WHERE usuario_id = :id')->execute([':id' => $id]);
            }
            $this->pdo->commit();
            return $ok;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function excluir(int $id): bool
    {
        $sth = $this->pdo->prepare('DELETE FROM usuarios WHERE id = :id');
        return $sth->execute([':id' => $id]);
    }

    public function emailEmUso(string $email, ?int $ignorarId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM usuarios WHERE email = :email';
        $params = [':email' => strtolower(trim($email))];

        if ($ignorarId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $ignorarId;
        }

        $sth = $this->pdo->prepare($sql);
        $sth->execute($params);

        return (int) $sth->fetchColumn() > 0;
    }

    public function autenticar(string $email, string $senha): array|false
    {
        $usuario = $this->buscarPorEmail($email);

        if (!$usuario || !password_verify($senha, $usuario['senha'])) {
            return false;
        }

        return $usuario;
    }

    public function contarAdmins(): int
    {
        $sth = $this->pdo->query("SELECT COUNT(*) FROM usuarios WHERE nivel = 'admin'");
        return (int) $sth->fetchColumn();
    }

    public function contar(): int
    {
        $sth = $this->pdo->query('SELECT COUNT(*) FROM usuarios');
        return (int) $sth->fetchColumn();
    }
}
