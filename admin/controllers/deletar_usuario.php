<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/usuarios.php';

exigirAdmin('../views/gerenciar_games.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validarCsrf($_POST['csrf_token'] ?? null)) {
    definirFlash('erro', 'Solicitação inválida.');
    header('Location: ../views/ver_usuarios.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    definirFlash('erro', 'Usuário inválido.');
    header('Location: ../views/ver_usuarios.php');
    exit;
}

if ((int) $id === (int) ($_SESSION['usuario_id'] ?? 0)) {
    definirFlash('erro', 'Você não pode excluir sua própria conta enquanto está logado.');
    header('Location: ../views/ver_usuarios.php');
    exit;
}

$model = new Usuario();
$usuario = $model->buscarPorId($id);

if (!$usuario) {
    definirFlash('erro', 'Usuário não encontrado.');
    header('Location: ../views/ver_usuarios.php');
    exit;
}

if ($usuario['nivel'] === 'admin' && $model->contarAdmins() <= 1) {
    definirFlash('erro', 'Não é possível excluir o último administrador.');
    header('Location: ../views/ver_usuarios.php');
    exit;
}

try {
    $model->excluir($id);
    definirFlash('sucesso', 'Usuário excluído com sucesso.');
} catch (PDOException $e) {
    definirFlash('erro', 'Este usuário possui pedidos associados e não pode ser excluído para preservar o histórico de compras.');
}
header('Location: ../views/ver_usuarios.php');
exit;
