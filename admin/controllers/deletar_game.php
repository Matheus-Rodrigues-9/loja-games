<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/upload.php';
require_once __DIR__ . '/../models/games.php';

exigirLogin('../../login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validarCsrf($_POST['csrf_token'] ?? null)) {
    definirFlash('erro', 'Solicitação inválida.');
    header('Location: ../views/ver_games.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    definirFlash('erro', 'Jogo inválido.');
    header('Location: ../views/ver_games.php');
    exit;
}

$model = new Game();
$registro = $model->buscarGamePorId($id);

if (!$registro) {
    definirFlash('erro', 'Jogo não encontrado.');
    header('Location: ../views/ver_games.php');
    exit;
}

$game = new Game($registro);
$game->deleteGame();
removerImagemJogo($registro['imagem'] ?? null);

definirFlash('sucesso', 'Jogo excluído com sucesso.');
header('Location: ../views/ver_games.php');
exit;
