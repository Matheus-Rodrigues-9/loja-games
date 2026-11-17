<?php

declare(strict_types=1);

require_once __DIR__ . '/admin/config/auth.php';
require_once __DIR__ . '/admin/models/usuarios.php';

$destinosPermitidos = ['checkout.php', 'carrinho.php', 'meus_pedidos.php', 'index.php'];
$destino = (string) ($_POST['redirect'] ?? $_GET['redirect'] ?? '');
if (!in_array($destino, $destinosPermitidos, true)) {
    $destino = '';
}

if (estaLogado()) {
    if (($_SESSION['usuario_nivel'] ?? '') === 'cliente') {
        header('Location: ' . ($destino ?: 'index.php'));
    } else {
        header('Location: admin/views/gerenciar_games.php');
    }
    exit;
}

$erro = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCsrf($_POST['csrf_token'] ?? null)) {
        $erro = 'Sessão expirada. Atualize a página e tente novamente.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $senha = (string) ($_POST['senha'] ?? '');

        try {
            $usuario = (new Usuario())->autenticar($email, $senha);

            if ($usuario) {
                session_regenerate_id(true);
                $_SESSION['usuario_id'] = (int) $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['usuario_nivel'] = $usuario['nivel'];

                if ($usuario['nivel'] === 'cliente') {
                    header('Location: ' . ($destino ?: 'index.php'));
                } else {
                    header('Location: admin/views/gerenciar_games.php');
                }
                exit;
            }

            $erro = 'E-mail ou senha inválidos.';
        } catch (PDOException $e) {
            $erro = 'Não foi possível acessar as contas. Verifique a configuração da conexão com o banco de dados.';
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
    <title>Entrar | Loja Games</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body class="auth-page">
<main class="auth-card auth-card-wide">
    <div class="auth-login-grid">
        <section>
            <a class="brand auth-brand" href="index.php"><span class="brand-mark">🎮</span> Loja Games</a>
            <p class="eyebrow">Sua conta gamer</p>
            <h1>Entre para continuar</h1>
            <p class="muted">Acesse sua conta para finalizar compras e acompanhar seus pedidos. A equipe também pode entrar por aqui.</p>

            <?php if ($erro): ?>
                <div class="alert alert-erro" role="alert"><?= e($erro) ?></div>
            <?php endif; ?>

            <form method="post" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="redirect" value="<?= e($destino) ?>">
                <div class="field">
                    <label for="email">E-mail</label>
                    <input id="email" type="email" name="email" autocomplete="username" placeholder="voce@exemplo.com" required value="<?= e($email) ?>">
                </div>
                <div class="field">
                    <label for="senha">Senha</label>
                    <input id="senha" type="password" name="senha" autocomplete="current-password" placeholder="Sua senha" required>
                </div>
                <button class="btn btn-primary btn-block" type="submit">Entrar</button>
            </form>

            <div class="auth-register-callout">
                <strong>Ainda não tem cadastro?</strong>
                <p>Crie sua conta grátis para comprar e acompanhar seus pedidos.</p>
                <a class="btn btn-secondary btn-block" href="cadastro.php?redirect=<?= e(rawurlencode($destino ?: 'index.php')) ?>">Criar minha conta</a>
            </div>

            <div class="auth-links">
                <a href="index.php">← Voltar para a loja</a>
            </div>
        </section>

        <aside class="auth-demo">
            <p class="eyebrow">Acesso da equipe</p>
            <h2>Área administrativa</h2>
            <p class="muted">Use uma conta da equipe para gerenciar jogos, estoque e usuários.</p>
            <div class="credentials-box">
                <strong>Acesso restrito à equipe</strong>
                <span>Entre com o e-mail e a senha da sua conta autorizada.</span>
                <small>Por segurança, as credenciais não são exibidas nesta página.</small>
            </div>
            <div class="auth-feature-list">
                <div><span>🎮</span><strong>Catálogo e estoque</strong><small>Gerencie jogos, preços, categorias e capas.</small></div>
                <div><span>🛒</span><strong>Compras e pedidos</strong><small>Clientes podem comprar e consultar seus pedidos.</small></div>
                <div><span>👥</span><strong>Contas e acessos</strong><small>Administradores gerenciam usuários da equipe.</small></div>
            </div>
        </aside>
    </div>
</main>
</body>
</html>
