<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/games.php';

exigirLogin();

$gameModel = new Game();
$busca = trim((string) ($_GET['buscar'] ?? ''));
$categoria = trim((string) ($_GET['categoria'] ?? ''));
$ordem = (string) ($_GET['ordem'] ?? 'titulo_asc');

$games = $gameModel->filtrar($busca, $categoria, $ordem);
$categorias = $gameModel->listarCategorias();

$pageTitle = 'Gerenciar jogos';
require __DIR__ . '/partials/header.php';
?>

<section class="panel crud-banner">
    <div>
        <p class="eyebrow">Catálogo e estoque</p>
        <h2>Games</h2>
        <p>Cadastre, consulte, edite e exclua jogos do catálogo da loja.</p>
    </div>

</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Catálogo administrativo</h2>
            <p><?= count($games) ?> resultado(s) encontrado(s).</p>
        </div>
        <a class="btn btn-primary" href="cadastrar_game.php">+ Cadastrar novo jogo</a>
    </div>

    <form class="filters" method="get">
        <div class="field grow">
            <label for="buscar">Buscar</label>
            <input id="buscar" type="search" name="buscar" placeholder="Título ou descrição" value="<?= e($busca) ?>">
        </div>
        <div class="field">
            <label for="categoria">Categoria</label>
            <select id="categoria" name="categoria">
                <option value="">Todas</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= $categoria === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="ordem">Ordenar</label>
            <select id="ordem" name="ordem">
                <option value="titulo_asc" <?= $ordem === 'titulo_asc' ? 'selected' : '' ?>>Título A–Z</option>
                <option value="titulo_desc" <?= $ordem === 'titulo_desc' ? 'selected' : '' ?>>Título Z–A</option>
                <option value="preco_asc" <?= $ordem === 'preco_asc' ? 'selected' : '' ?>>Menor preço</option>
                <option value="preco_desc" <?= $ordem === 'preco_desc' ? 'selected' : '' ?>>Maior preço</option>
                <option value="estoque_asc" <?= $ordem === 'estoque_asc' ? 'selected' : '' ?>>Menor estoque</option>
                <option value="estoque_desc" <?= $ordem === 'estoque_desc' ? 'selected' : '' ?>>Maior estoque</option>
                <option value="recentes" <?= $ordem === 'recentes' ? 'selected' : '' ?>>Mais recentes</option>
            </select>
        </div>
        <div class="filter-actions">
            <button class="btn btn-primary" type="submit">Filtrar</button>
            <a class="btn btn-ghost" href="ver_games.php">Limpar</a>
        </div>
    </form>

    <?php if (!$games): ?>
        <div class="empty-state">Nenhum jogo corresponde aos filtros.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Imagem</th>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Descrição</th>
                    <th>Preço</th>
                    <th>Estoque</th>
                    <th>Categoria</th>
                    <th>Ações</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($games as $g): ?>
                    <?php
                    $nomeImagem = basename((string) ($g['imagem'] ?? ''));
                    $arquivoImagem = __DIR__ . '/../../public/imagens/' . $nomeImagem;
                    $temImagem = $nomeImagem !== '' && is_file($arquivoImagem);
                    ?>
                    <tr>
                        <td>
                            <?php if ($temImagem): ?>
                                <img class="thumb" src="../../public/imagens/<?= e($nomeImagem) ?>" alt="Capa de <?= e($g['titulo']) ?>">
                            <?php else: ?>
                                <span class="image-placeholder" title="<?= e($nomeImagem ?: 'Sem imagem') ?>">🎮</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int) $g['id'] ?></td>
                        <td class="text-left"><strong><?= e($g['titulo']) ?></strong></td>
                        <td class="text-left description-cell"><?= e($g['descricao']) ?></td>
                        <td>R$ <?= number_format((float) $g['preco'], 2, ',', '.') ?></td>
                        <td>
                            <span class="badge <?= (int) $g['estoque'] <= 5 ? 'badge-warning' : 'badge-success' ?>">
                                <?= (int) $g['estoque'] ?>
                            </span>
                        </td>
                        <td><?= e($g['categoria']) ?></td>
                        <td>
                            <div class="row-actions">
                                <a class="icon-btn" href="editar_game.php?id=<?= (int) $g['id'] ?>" title="Editar">✏️</a>
                                <form method="post" action="../controllers/deletar_game.php" class="inline-form js-confirm-delete" data-item="<?= e($g['titulo']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
                                    <button class="icon-btn danger" type="submit" title="Excluir">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
