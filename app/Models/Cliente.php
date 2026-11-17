<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use RuntimeException;

class Cliente
{
    public function __construct(private PDO $pdo) {}

    public function listar(string $busca = ''): array
    {
        $sql = "SELECT c.id,c.usuario_id,c.telefone,c.endereco,c.cidade,c.cep,c.created_at,u.nome,u.email
                FROM clientes c INNER JOIN usuarios u ON u.id=c.usuario_id WHERE u.nivel='cliente'";
        $params = [];
        if ($busca !== '') {
            $sql .= ' AND (u.nome LIKE :nome OR u.email LIKE :email OR c.telefone LIKE :telefone)';
            $term = '%' . $busca . '%';
            $params = [':nome' => $term, ':email' => $term, ':telefone' => $term];
        }
        $sql .= ' ORDER BY u.nome';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function listarParaSelect(): array
    {
        return $this->pdo->query("SELECT c.id,c.usuario_id,u.nome,u.email FROM clientes c JOIN usuarios u ON u.id=c.usuario_id WHERE u.nivel='cliente' ORDER BY u.nome")->fetchAll();
    }

    public function buscar(int $id): array|false
    {
        $st = $this->pdo->prepare('SELECT c.*,u.nome,u.email FROM clientes c JOIN usuarios u ON u.id=c.usuario_id WHERE c.id=:id');
        $st->execute([':id' => $id]);
        return $st->fetch();
    }

    public function criar(array $dados): int
    {
        if ($this->emailEmUso($dados['email'])) {
            throw new RuntimeException('Já existe uma conta com esse e-mail.');
        }
        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare("INSERT INTO usuarios (nome,email,senha,nivel,created_at,updated_at) VALUES (:nome,:email,:senha,'cliente',NOW(),NOW())");
            $st->execute([':nome'=>trim($dados['nome']), ':email'=>strtolower(trim($dados['email'])), ':senha'=>password_hash($dados['senha'], PASSWORD_DEFAULT)]);
            $usuarioId = (int) $this->pdo->lastInsertId();
            $perfil = $this->pdo->prepare('INSERT INTO clientes (usuario_id,telefone,endereco,cidade,cep,created_at,updated_at) VALUES (:usuario_id,:telefone,:endereco,:cidade,:cep,NOW(),NOW())');
            $perfil->execute([':usuario_id'=>$usuarioId, ':telefone'=>$dados['telefone'] ?: null, ':endereco'=>$dados['endereco'] ?: null, ':cidade'=>$dados['cidade'] ?: null, ':cep'=>$dados['cep'] ?: null]);
            $id = (int) $this->pdo->lastInsertId();
            $this->pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function atualizar(int $id, array $dados): bool
    {
        $cliente = $this->buscar($id);
        if (!$cliente) throw new RuntimeException('Cliente não encontrado.');
        if ($this->emailEmUso($dados['email'], (int) $cliente['usuario_id'])) throw new RuntimeException('Este e-mail já está em uso.');
        $this->pdo->beginTransaction();
        try {
            $sql = 'UPDATE usuarios SET nome=:nome,email=:email,updated_at=NOW()';
            $params = [':id'=>$cliente['usuario_id'], ':nome'=>trim($dados['nome']), ':email'=>strtolower(trim($dados['email']))];
            if (!empty($dados['senha'])) { $sql .= ',senha=:senha'; $params[':senha'] = password_hash($dados['senha'], PASSWORD_DEFAULT); }
            $sql .= ' WHERE id=:id';
            $this->pdo->prepare($sql)->execute($params);
            $this->pdo->prepare('UPDATE clientes SET telefone=:telefone,endereco=:endereco,cidade=:cidade,cep=:cep,updated_at=NOW() WHERE id=:id')->execute([
                ':id'=>$id, ':telefone'=>$dados['telefone'] ?: null, ':endereco'=>$dados['endereco'] ?: null, ':cidade'=>$dados['cidade'] ?: null, ':cep'=>$dados['cep'] ?: null,
            ]);
            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function excluir(int $id): bool
    {
        $cliente = $this->buscar($id);
        if (!$cliente) return false;
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM pedidos WHERE usuario_id=:usuario_id');
        $st->execute([':usuario_id'=>$cliente['usuario_id']]);
        if ((int) $st->fetchColumn() > 0) throw new RuntimeException('Este cliente possui histórico de pedidos. Preserve o cadastro para manter o histórico.');
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('DELETE FROM clientes WHERE id=:id')->execute([':id'=>$id]);
            $this->pdo->prepare('DELETE FROM usuarios WHERE id=:id')->execute([':id'=>$cliente['usuario_id']]);
            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function sincronizarPerfil(int $usuarioId): void
    {
        $st = $this->pdo->prepare('INSERT IGNORE INTO clientes (usuario_id,created_at,updated_at) VALUES (:id,NOW(),NOW())');
        $st->execute([':id'=>$usuarioId]);
    }

    private function emailEmUso(string $email, ?int $ignorarUsuarioId = null): bool
    {
        $sql='SELECT COUNT(*) FROM usuarios WHERE email=:email'; $params=[':email'=>strtolower(trim($email))];
        if ($ignorarUsuarioId !== null) { $sql.=' AND id<>:id'; $params[':id']=$ignorarUsuarioId; }
        $st=$this->pdo->prepare($sql); $st->execute($params); return (int)$st->fetchColumn()>0;
    }
}
