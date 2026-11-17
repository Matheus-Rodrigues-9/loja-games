<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/usuarios.php';

exigirAdmin();

$model = new Usuario();
$busca = trim((string) ($_GET['buscar'] ?? ''));
$nivel = trim((string) ($_GET['nivel'] ?? ''));
$usuarios = $model->filtrar($busca, $nivel);

$pageTitle = 'Gerenciar usuários';
require __DIR__ . '/partials/header.php';
?>

<section class="panel crud-banner">
    <div>
        <p class="eyebrow">Equipe e clientes</p>
        <h2>Usuários do sistema</h2>
        <p>Gerencie as contas da equipe e os cadastros de clientes da loja.</p>
    </div>

</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Usuários cadastrados</h2>
            <p><?= count($usuarios) ?> resultado(s) encontrado(s).</p>
        </div>
        <a class="btn btn-primary" href="cadastrar_usuario.php">+ Cadastrar usuário</a>
    </div>

    <form class="filters" method="get">
        <div class="field grow">
            <label for="buscar">Buscar usuário</label>
            <input id="buscar" type="search" name="buscar" placeholder="Nome ou e-mail" value="<?= e($busca) ?>">
        </div>
        <div class="field">
            <label for="nivel">Nível</label>
            <select id="nivel" name="nivel">
                <option value="">Todos</option>
                <option value="admin" <?= $nivel === 'admin' ? 'selected' : '' ?>>Administrador</option>
                <option value="operador" <?= $nivel === 'operador' ? 'selected' : '' ?>>Operador</option>
                <option value="cliente" <?= $nivel === 'cliente' ? 'selected' : '' ?>>Cliente</option>
            </select>
        </div>
        <div class="filter-actions">
            <button class="btn btn-primary" type="submit">Filtrar</button>
            <a class="btn btn-ghost" href="ver_usuarios.php">Limpar</a>
        </div>
    </form>

    <?php if (!$usuarios): ?>
        <div class="empty-state">Nenhum usuário corresponde aos filtros.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Nível</th>
                    <th>Cadastrado em</th>
                    <th>Ações</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= (int) $u['id'] ?></td>
                        <td class="text-left"><strong><?= e($u['nome']) ?></strong></td>
                        <td><?= e($u['email']) ?></td>
                        <td><span class="badge <?= $u['nivel'] === 'admin' ? 'badge-info' : ($u['nivel'] === 'cliente' ? 'badge-success' : 'badge-neutral') ?>"><?= e(['admin' => 'Administrador', 'operador' => 'Operador', 'cliente' => 'Cliente'][$u['nivel']] ?? $u['nivel']) ?></span></td>
                        <td><?= !empty($u['created_at']) ? e(date('d/m/Y H:i', strtotime($u['created_at']))) : '—' ?></td>
                        <td>
                            <div class="row-actions">
                                <a class="icon-btn" href="editar_usuario.php?id=<?= (int) $u['id'] ?>" title="Editar">✏️</a>
                                <?php if ((int) $u['id'] !== (int) ($_SESSION['usuario_id'] ?? 0)): ?>
                                    <form method="post" action="../controllers/deletar_usuario.php" class="inline-form js-confirm-delete" data-item="<?= e($u['nome']) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                        <button class="icon-btn danger" type="submit" title="Excluir">🗑️</button>
                                    </form>
                                <?php else: ?>
                                    <span class="muted" title="Você não pode excluir sua própria conta">Conta atual</span>
                                <?php endif; ?>
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
