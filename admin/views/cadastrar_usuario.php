<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/usuarios.php';

exigirAdmin();

$model = new Usuario();
$erros = [];
$dados = [
    'nome' => '',
    'email' => '',
    'nivel' => 'operador',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCsrf($_POST['csrf_token'] ?? null)) {
        $erros[] = 'Sessão expirada ou formulário inválido.';
    }

    $dados['nome'] = trim((string) ($_POST['nome'] ?? ''));
    $dados['email'] = trim((string) ($_POST['email'] ?? ''));
    $dados['nivel'] = in_array($_POST['nivel'] ?? '', ['admin', 'operador', 'cliente'], true) ? $_POST['nivel'] : 'operador';
    $senha = (string) ($_POST['senha'] ?? '');
    $confirmar = (string) ($_POST['confirmar_senha'] ?? '');

    if ($dados['nome'] === '') $erros[] = 'Informe o nome.';
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'Informe um e-mail válido.';
    if (strlen($senha) < 8) $erros[] = 'A senha deve ter pelo menos 8 caracteres.';
    if ($senha !== $confirmar) $erros[] = 'As senhas não coincidem.';
    if (!$erros && $model->emailEmUso($dados['email'])) $erros[] = 'Este e-mail já está em uso.';

    if (!$erros) {
        $model->criar($dados['nome'], $dados['email'], $senha, $dados['nivel']);
        definirFlash('sucesso', 'Usuário cadastrado com sucesso.');
        header('Location: ver_usuarios.php');
        exit;
    }
}

$pageTitle = 'Cadastrar Usuário';
require __DIR__ . '/partials/header.php';
?>

<section class="panel form-panel">
    <?php if ($erros): ?>
        <div class="alert alert-erro"><ul><?php foreach ($erros as $erro): ?><li><?= e($erro) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

        <div class="field span-2">
            <label for="nome">Nome *</label>
            <input id="nome" name="nome" maxlength="150" required value="<?= e($dados['nome']) ?>">
        </div>

        <div class="field span-2">
            <label for="email">E-mail *</label>
            <input id="email" type="email" name="email" maxlength="190" required value="<?= e($dados['email']) ?>">
        </div>

        <div class="field">
            <label for="senha">Senha *</label>
            <input id="senha" type="password" name="senha" minlength="8" required>
        </div>

        <div class="field">
            <label for="confirmar_senha">Confirmar senha *</label>
            <input id="confirmar_senha" type="password" name="confirmar_senha" minlength="8" required>
        </div>

        <div class="field">
            <label for="nivel">Nível *</label>
            <select id="nivel" name="nivel">
                <option value="operador" <?= $dados['nivel'] === 'operador' ? 'selected' : '' ?>>Operador</option>
                <option value="admin" <?= $dados['nivel'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                <option value="cliente" <?= $dados['nivel'] === 'cliente' ? 'selected' : '' ?>>Cliente</option>
            </select>
        </div>

        <div class="form-actions span-2">
            <a class="btn btn-ghost" href="ver_usuarios.php">Cancelar</a>
            <button class="btn btn-primary" type="submit">Salvar usuário</button>
        </div>
    </form>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
