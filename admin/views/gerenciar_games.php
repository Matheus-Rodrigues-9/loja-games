<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/games.php';
require_once __DIR__ . '/../models/usuarios.php';

exigirLogin();

$gameModel = new Game();
$resumo = $gameModel->obterResumo();
$ultimosJogos = array_slice($gameModel->filtrar('', '', 'recentes'), 0, 5);
$totalPedidos = (int) $pdo->query('SELECT COUNT(*) FROM pedidos')->fetchColumn();
$pedidosRecebidos = (int) $pdo->query("SELECT COUNT(*) FROM pedidos WHERE status = 'recebido'")->fetchColumn();

$totalUsuarios = null;
if (($_SESSION['usuario_nivel'] ?? '') === 'admin') {
    try {
        $totalUsuarios = (new Usuario())->contar();
    } catch (Throwable $e) {
        $totalUsuarios = null;
    }
}

$pageTitle = 'Dashboard';
require __DIR__ . '/partials/header.php';
?>

<section class="stats-grid">
    <article class="stat-card">
        <span class="stat-label">Jogos cadastrados</span>
        <strong><?= (int) $resumo['total_games'] ?></strong>
    </article>
    <article class="stat-card">
        <span class="stat-label">Unidades em estoque</span>
        <strong><?= (int) $resumo['total_estoque'] ?></strong>
    </article>
    <article class="stat-card">
        <span class="stat-label">Estoque baixo (≤ 5)</span>
        <strong><?= (int) $resumo['estoque_baixo'] ?></strong>
    </article>
    <article class="stat-card">
        <span class="stat-label">Preço médio</span>
        <strong>R$ <?= number_format((float) $resumo['preco_medio'], 2, ',', '.') ?></strong>
    </article>
    <article class="stat-card">
        <span class="stat-label">Pedidos registrados</span>
        <strong><?= $totalPedidos ?></strong>
    </article>
    <article class="stat-card">
        <span class="stat-label">Pedidos recebidos</span>
        <strong><?= $pedidosRecebidos ?></strong>
    </article>
    <?php if ($totalUsuarios !== null): ?>
        <article class="stat-card">
            <span class="stat-label">Usuários do sistema</span>
            <strong><?= $totalUsuarios ?></strong>
        </article>
    <?php endif; ?>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Estrutura do trabalho</p>
            <h2>Gestão da loja</h2>
            <p>Gerencie o catálogo, o estoque e as contas de acesso em um só lugar.</p>
        </div>
    </div>

    <div class="crud-grid">
        <article class="crud-card">
            <span class="crud-icon">🎮</span>
            <div>
                <p class="eyebrow">Catálogo e estoque</p>
                <h3>Games</h3>
                <p>Cadastre e mantenha os jogos, preços, categorias, capas e disponibilidade.</p>
            </div>
            <p class="feature-line">Cadastrar · consultar · editar · excluir</p>
            <div class="quick-actions">
                <a class="btn btn-primary" href="cadastrar_game.php">+ Cadastrar jogo</a>
                <a class="btn btn-secondary" href="ver_games.php">Gerenciar jogos</a>
            </div>
        </article>

        <?php if (($_SESSION['usuario_nivel'] ?? '') === 'admin'): ?>
            <article class="crud-card">
                <span class="crud-icon">👥</span>
                <div>
                    <p class="eyebrow">Contas de acesso</p>
                    <h3>Usuários</h3>
                    <p>Gerencie administradores, operadores e contas de clientes.</p>
                </div>
                <p class="feature-line">Cadastrar · consultar · editar · excluir</p>
                <div class="quick-actions">
                    <a class="btn btn-primary" href="cadastrar_usuario.php">+ Cadastrar usuário</a>
                    <a class="btn btn-secondary" href="ver_usuarios.php">Gerenciar usuários</a>
                </div>
            </article>
        <?php endif; ?>
    </div>
</section>

<section class="panel panel-order-shortcut">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Vendas</p>
            <h2>Acompanhe os pedidos</h2>
            <p>Consulte compras registradas e atualize o andamento dos pedidos.</p>
        </div>
        <a class="btn btn-primary" href="ver_pedidos.php">Gerenciar pedidos</a>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Cadastros recentes</h2>
            <p>Últimos jogos inseridos na base.</p>
        </div>
        <a href="ver_games.php">Ver todos</a>
    </div>

    <?php if (!$ultimosJogos): ?>
        <div class="empty-state">Nenhum jogo cadastrado ainda.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Categoria</th>
                    <th>Preço</th>
                    <th>Estoque</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ultimosJogos as $g): ?>
                    <tr>
                        <td><?= (int) $g['id'] ?></td>
                        <td class="text-left"><strong><?= e($g['titulo']) ?></strong></td>
                        <td><?= e($g['categoria']) ?></td>
                        <td>R$ <?= number_format((float) $g['preco'], 2, ',', '.') ?></td>
                        <td><?= (int) $g['estoque'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
