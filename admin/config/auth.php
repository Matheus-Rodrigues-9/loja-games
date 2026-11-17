<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

function estaLogado(): bool
{
    return isset($_SESSION['usuario_id']);
}

function usuarioAtual(): array
{
    return [
        'id' => $_SESSION['usuario_id'] ?? null,
        'nome' => $_SESSION['usuario_nome'] ?? 'Visitante',
        'email' => $_SESSION['usuario_email'] ?? null,
        'nivel' => $_SESSION['usuario_nivel'] ?? null,
    ];
}

function exigirLogin(string $loginPath = '../../login.php'): void
{
    if (!estaLogado()) {
        header('Location: ' . $loginPath);
        exit;
    }

    // Contas de clientes não podem acessar o painel da equipe.
    if (($_SESSION['usuario_nivel'] ?? '') === 'cliente') {
        header('Location: ../../index.php');
        exit;
    }
}

function exigirAdmin(string $fallback = 'gerenciar_games.php'): void
{
    exigirLogin();

    if (($_SESSION['usuario_nivel'] ?? '') !== 'admin') {
        definirFlash('erro', 'Acesso restrito a administradores.');
        header('Location: ' . $fallback);
        exit;
    }
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validarCsrf(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function definirFlash(string $tipo, string $mensagem): void
{
    $_SESSION['flash'] = [
        'tipo' => $tipo,
        'mensagem' => $mensagem,
    ];
}

function obterFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
