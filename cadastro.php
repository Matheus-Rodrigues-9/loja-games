<?php

declare(strict_types=1);

require_once __DIR__ . '/admin/config/auth.php';
require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/admin/models/usuarios.php';

$destinosPermitidos = ['checkout.php', 'carrinho.php', 'meus_pedidos.php', 'index.php'];
$destino = (string) ($_POST['redirect'] ?? $_GET['redirect'] ?? '');
if (!in_array($destino, $destinosPermitidos, true)) {
    $destino = 'index.php';
}

if (estaLogado()) {
    if (($_SESSION['usuario_nivel'] ?? '') === 'cliente') {
        header('Location: ' . $destino);
    } else {
        header('Location: admin/views/gerenciar_games.php');
    }
    exit;
}

$erros = [];
$nome = '';
$email = '';
$telefone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCsrf($_POST['csrf_token'] ?? null)) {
        $erros[] = 'Sessão expirada. Atualize a página e tente novamente.';
    }

    $nome = trim((string) ($_POST['nome'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $telefone = trim((string) ($_POST['telefone'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');
    $confirmarSenha = (string) ($_POST['confirmar_senha'] ?? '');

    if ($nome === '' || (function_exists('mb_strlen') ? mb_strlen($nome, 'UTF-8') : strlen($nome)) < 2) {
        $erros[] = 'Informe seu nome completo.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    }
    if (strlen(preg_replace('/\D+/', '', $telefone) ?? '') < 10) {
        $erros[] = 'Informe um telefone válido com DDD.';
    }
    if (strlen($senha) < 8) {
        $erros[] = 'A senha deve ter pelo menos 8 caracteres.';
    }
    if ($senha !== $confirmarSenha) {
        $erros[] = 'As senhas não coincidem.';
    }

    $model = new Usuario();
    if (!$erros && $model->emailEmUso($email)) {
        $erros[] = 'Este e-mail já possui cadastro. Entre na sua conta.';
    }

    if (!$erros) {
        try {
            $model->criar($nome, $email, $senha, 'cliente');
            $usuario = $model->buscarPorEmail($email);
            if ($usuario) {
                $clienteModel = new \App\Models\Cliente($pdo);
                $clienteModel->sincronizarPerfil((int) $usuario['id']);
                $pdo->prepare('UPDATE clientes SET telefone = :telefone WHERE usuario_id = :usuario_id')->execute([
                    ':telefone' => $telefone,
                    ':usuario_id' => (int) $usuario['id'],
                ]);
            }

            if (!$usuario) {
                throw new RuntimeException('Não foi possível carregar a conta recém-criada.');
            }

            session_regenerate_id(true);
            $_SESSION['usuario_id'] = (int) $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['usuario_nivel'] = 'cliente';

            definirFlash('sucesso', 'Conta criada com sucesso. Você já pode continuar sua compra.');
            header('Location: ' . $destino);
            exit;
        } catch (Throwable $e) {
            $erros[] = 'Não foi possível criar sua conta. Verifique os dados e tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#070914">
    <title>Criar conta | Loja Games</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body class="auth-page">
<main class="auth-card">
    <a class="brand auth-brand" href="index.php"><span class="brand-mark">🎮</span> Loja Games</a>
    <p class="eyebrow">Bem-vindo à comunidade</p>
    <h1>Crie sua conta</h1>
    <p class="muted">Cadastre-se para finalizar compras e acompanhar seus pedidos. É grátis.</p>

    <?php if ($erros): ?>
        <div class="alert alert-erro" role="alert">
            <strong>Confira os dados:</strong>
            <ul><?php foreach ($erros as $erro): ?><li><?= e($erro) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="post" class="auth-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="redirect" value="<?= e($destino) ?>">
        <div class="field">
            <label for="nome">Nome completo</label>
            <input id="nome" name="nome" autocomplete="name" maxlength="150" required value="<?= e($nome) ?>">
        </div>
        <div class="field">
            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" autocomplete="email" maxlength="190" required value="<?= e($email) ?>">
        </div>
        <div class="field">
            <label for="telefone">Telefone com DDD</label>
            <input id="telefone" name="telefone" type="tel" autocomplete="tel" maxlength="30" required value="<?= e($telefone) ?>" placeholder="(38) 99999-9999">
        </div>
        <div class="field">
            <label for="senha">Crie uma senha</label>
            <input id="senha" type="password" name="senha" autocomplete="new-password" minlength="8" required>
            <small>Use pelo menos 8 caracteres.</small>
        </div>
        <div class="field">
            <label for="confirmar_senha">Confirme a senha</label>
            <input id="confirmar_senha" type="password" name="confirmar_senha" autocomplete="new-password" minlength="8" required>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Criar conta e continuar</button>
    </form>

    <div class="auth-links">
        <a href="login.php?redirect=<?= e(rawurlencode($destino)) ?>">Já tenho conta</a>
        <a href="index.php">Voltar à loja</a>
    </div>
</main>
</body>
</html>
