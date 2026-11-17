<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/conexao.php';

class Game
{
    public ?int $id = null;
    public string $titulo = '';
    public string $descricao = '';
    public float $preco = 0;
    public int $estoque = 0;
    public string $categoria = '';
    public ?string $imagem = null;

    private PDO $pdo;

    public function __construct(array $atrib = [])
    {
        global $pdo;
        $this->pdo = $pdo;

        if ($atrib) {
            $this->id = isset($atrib['id']) ? (int) $atrib['id'] : null;
            $this->titulo = (string) ($atrib['titulo'] ?? '');
            $this->descricao = (string) ($atrib['descricao'] ?? '');
            $this->preco = (float) ($atrib['preco'] ?? 0);
            $this->estoque = (int) ($atrib['estoque'] ?? 0);
            $this->categoria = (string) ($atrib['categoria'] ?? '');
            $this->imagem = isset($atrib['imagem']) && $atrib['imagem'] !== '' ? (string) $atrib['imagem'] : null;
        }
    }

    public function listarGames(): array
    {
        return $this->filtrar();
    }

    public function filtrar(string $busca = '', string $categoria = '', string $ordem = 'titulo_asc'): array
    {
        $sql = 'SELECT * FROM games WHERE 1=1';
        $params = [];

        if ($busca !== '') {
            $sql .= ' AND (titulo LIKE :busca_titulo OR descricao LIKE :busca_descricao)';
            $params[':busca_titulo'] = '%' . $busca . '%';
            $params[':busca_descricao'] = '%' . $busca . '%';
        }

        if ($categoria !== '') {
            $sql .= ' AND categoria = :categoria';
            $params[':categoria'] = $categoria;
        }

        $ordenacoes = [
            'titulo_asc' => 'titulo ASC',
            'titulo_desc' => 'titulo DESC',
            'preco_asc' => 'preco ASC',
            'preco_desc' => 'preco DESC',
            'estoque_asc' => 'estoque ASC',
            'estoque_desc' => 'estoque DESC',
            'recentes' => 'id DESC',
        ];

        $sql .= ' ORDER BY ' . ($ordenacoes[$ordem] ?? $ordenacoes['titulo_asc']);

        $sth = $this->pdo->prepare($sql);
        $sth->execute($params);

        return $sth->fetchAll();
    }

    public function buscarPorNome(string $nome): array
    {
        return $this->filtrar($nome);
    }

    public function listarCategorias(): array
    {
        $sth = $this->pdo->query("SELECT DISTINCT categoria FROM games WHERE categoria IS NOT NULL AND categoria <> '' ORDER BY categoria ASC");
        return $sth->fetchAll(PDO::FETCH_COLUMN);
    }

    public function cadastrarGames(): bool
    {
        $categoriaId = $this->obterCategoriaId();
        $sth = $this->pdo->prepare(
            'INSERT INTO games (titulo, descricao, preco, estoque, categoria, categoria_id, imagem, created_at, updated_at)
             VALUES (:titulo, :descricao, :preco, :estoque, :categoria, :categoria_id, :imagem, NOW(), NOW())'
        );

        return $sth->execute([
            ':titulo' => $this->titulo,
            ':descricao' => $this->descricao,
            ':preco' => $this->preco,
            ':estoque' => $this->estoque,
            ':categoria' => $this->categoria,
            ':categoria_id' => $categoriaId,
            ':imagem' => $this->imagem,
        ]);
    }

    public function editarGame(): bool
    {
        $categoriaId = $this->obterCategoriaId();
        $sth = $this->pdo->prepare(
            'UPDATE games SET
                titulo = :titulo,
                descricao = :descricao,
                preco = :preco,
                estoque = :estoque,
                categoria = :categoria,
                categoria_id = :categoria_id,
                imagem = :imagem,
                updated_at = NOW()
             WHERE id = :id'
        );

        return $sth->execute([
            ':id' => $this->id,
            ':titulo' => $this->titulo,
            ':descricao' => $this->descricao,
            ':preco' => $this->preco,
            ':estoque' => $this->estoque,
            ':categoria' => $this->categoria,
            ':categoria_id' => $categoriaId,
            ':imagem' => $this->imagem,
        ]);
    }

    public function deleteGame(): bool
    {
        $sth = $this->pdo->prepare('DELETE FROM games WHERE id = :id');
        return $sth->execute([':id' => $this->id]);
    }

    public function buscarGamePorId(int $id): array|false
    {
        $sth = $this->pdo->prepare('SELECT * FROM games WHERE id = :id');
        $sth->execute([':id' => $id]);
        return $sth->fetch();
    }

    public function obterResumo(): array
    {
        $sth = $this->pdo->query(
            'SELECT
                COUNT(*) AS total_games,
                COALESCE(SUM(estoque), 0) AS total_estoque,
                COALESCE(SUM(CASE WHEN estoque <= 5 THEN 1 ELSE 0 END), 0) AS estoque_baixo,
                COALESCE(AVG(preco), 0) AS preco_medio
             FROM games'
        );

        return $sth->fetch() ?: [
            'total_games' => 0,
            'total_estoque' => 0,
            'estoque_baixo' => 0,
            'preco_medio' => 0,
        ];
    }
    private function obterCategoriaId(): int
    {
        $nome = trim($this->categoria);
        $consulta = $this->pdo->prepare('SELECT id FROM categorias WHERE nome = :nome');
        $consulta->execute([':nome' => $nome]);
        $id = $consulta->fetchColumn();
        if ($id) {
            return (int) $id;
        }
        $inserir = $this->pdo->prepare('INSERT INTO categorias (nome, created_at, updated_at) VALUES (:nome, NOW(), NOW())');
        $inserir->execute([':nome' => $nome]);
        return (int) $this->pdo->lastInsertId();
    }

}
