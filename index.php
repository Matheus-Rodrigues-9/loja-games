<?php

declare(strict_types=1);

require_once __DIR__ . '/admin/config/auth.php';
require_once __DIR__ . '/admin/models/games.php';

$model = new Game();
$busca = trim((string) ($_GET['buscar'] ?? ''));
$categoria = trim((string) ($_GET['categoria'] ?? ''));
$ordem = (string) ($_GET['ordem'] ?? 'titulo_asc');
$games = $model->filtrar($busca, $categoria, $ordem);
$categorias = $model->listarCategorias();
$totalDisponiveis = count(array_filter($games, static fn(array $g): bool => (int) $g['estoque'] > 0));
$storeTitle = 'Catálogo';
require __DIR__ . '/includes/store_header.php';
?>

<section class="hero">
    <div class="container hero-content">
        <div class="hero-copy">
            <p class="eyebrow">Seu próximo jogo começa aqui</p>
            <h1>Descubra o próximo universo para jogar.</h1>
            <p>Explore aventuras, compare preços e adicione seus jogos favoritos ao carrinho. Sua próxima jornada está a poucos cliques.</p>
            <div class="hero-badges" aria-label="Resumo do catálogo">
                <span class="hero-badge">🎮 <?= count($games) ?> título(s)</span>
                <span class="hero-badge">⚡ <?= $totalDisponiveis ?> disponível(is)</span>
                <span class="hero-badge">✦ <?= count($categorias) ?> categoria(s)</span>
            </div>
            <div class="hero-actions">
                <a class="btn btn-primary" href="#catalogo">Explorar catálogo</a>
                <a class="btn btn-secondary" href="carrinho.php">Ver carrinho</a>
            </div>
        </div>
        <div class="hero-art" aria-hidden="true">
            <div class="hero-glow"></div>
            <div class="hero-console"></div>
        </div>
    </div>
</section>

<main class="container page-content" id="catalogo">
    <section class="panel catalog-panel" aria-label="Filtros do catálogo">
        <form class="filters" method="get">
            <div class="field grow">
                <label for="buscar">Buscar jogo</label>
                <input id="buscar" type="search" name="buscar" placeholder="Título ou descrição..." value="<?= e($busca) ?>">
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
                <label for="ordem">Ordenar por</label>
                <select id="ordem" name="ordem">
                    <option value="titulo_asc" <?= $ordem === 'titulo_asc' ? 'selected' : '' ?>>Título A–Z</option>
                    <option value="preco_asc" <?= $ordem === 'preco_asc' ? 'selected' : '' ?>>Menor preço</option>
                    <option value="preco_desc" <?= $ordem === 'preco_desc' ? 'selected' : '' ?>>Maior preço</option>
                    <option value="recentes" <?= $ordem === 'recentes' ? 'selected' : '' ?>>Mais recentes</option>
                </select>
            </div>
            <div class="filter-actions">
                <button class="btn btn-primary" type="submit">Aplicar filtros</button>
                <a class="btn btn-ghost" href="index.php#catalogo">Limpar</a>
            </div>
        </form>
    </section>

    <div class="catalog-intro">
        <div>
            <p class="eyebrow">Biblioteca</p>
            <h2>Jogos disponíveis</h2>
            <p><?= count($games) ?> resultado(s) encontrado(s).</p>
        </div>
    </div>

    <?php if (!$games): ?>
        <div class="empty-state large">Nenhum jogo encontrado com os filtros informados.</div>
    <?php else: ?>
        <section class="game-grid" aria-label="Catálogo de jogos">
            <?php foreach ($games as $g): ?>
                <?php
                $nomeImagem = basename((string) ($g['imagem'] ?? ''));
                $arquivoImagem = __DIR__ . '/public/imagens/' . $nomeImagem;
                $temImagem = $nomeImagem !== '' && is_file($arquivoImagem);
                ?>
                <article class="game-card">
                    <div class="game-cover">
                        <?php if ($temImagem): ?>
                            <img src="public/imagens/<?= e($nomeImagem) ?>" alt="Capa de <?= e($g['titulo']) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="cover-placeholder">🎮</div>
                        <?php endif; ?>
                    </div>
                    <div class="game-card-body">
                        <div class="card-meta">
                            <span class="badge badge-info"><?= e($g['categoria']) ?></span>
                            <span class="badge <?= (int) $g['estoque'] > 0 ? 'badge-success' : 'badge-danger' ?>">
                                <?= (int) $g['estoque'] > 0 ? (int) $g['estoque'] . ' em estoque' : 'Esgotado' ?>
                            </span>
                        </div>
                        <h2><?= e($g['titulo']) ?></h2>
                        <p><?= e($g['descricao']) ?></p>
                        <div class="game-card-footer">
                            <strong>R$ <?= number_format((float) $g['preco'], 2, ',', '.') ?></strong>
                            <span class="muted">#<?= (int) $g['id'] ?></span>
                        </div>
                        <form class="add-cart-form" method="post" action="carrinho.php">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="acao" value="adicionar">
                            <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
                            <input type="hidden" name="quantidade" value="1">
                            <button class="btn btn-primary btn-block" type="submit" <?= (int) $g['estoque'] <= 0 ? 'disabled' : '' ?>>
                                <?= (int) $g['estoque'] > 0 ? '🛒 Adicionar ao carrinho' : 'Indisponível' ?>
                            </button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/includes/store_footer.php'; ?>
