<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

abstract class BaseController
{
    public function __construct(protected PDO $pdo)
    {
    }

    protected function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require __DIR__ . '/../Views/layout/header.php';
        require __DIR__ . '/../Views/' . $view . '.php';
        require __DIR__ . '/../Views/layout/footer.php';
    }

    protected function redirect(string $entity, string $action = 'listar', array $params = []): void
    {
        $query = array_merge(['entidade' => $entity, 'acao' => $action], $params);
        header('Location: index.php?' . http_build_query($query));
        exit;
    }

    protected function flash(string $type, string $message): void
    {
        \definirFlash($type, $message);
    }

    protected function requireValidPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !\validarCsrf($_POST['csrf_token'] ?? null)) {
            throw new \RuntimeException('Solicitação inválida ou sessão expirada. Atualize a página e tente novamente.');
        }
    }

    protected function positiveId(): int
    {
        $id = filter_var($_POST['id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            throw new \InvalidArgumentException('Identificador inválido.');
        }
        return (int) $id;
    }

    protected function uploadImage(?string $current = null): ?string
    {
        if (empty($_FILES['imagem']['name'])) {
            return $current;
        }
        if (!isset($_FILES['imagem']['tmp_name']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Não foi possível receber a imagem enviada.');
        }
        if ((int) $_FILES['imagem']['size'] > 3 * 1024 * 1024) {
            throw new \RuntimeException('A imagem deve ter no máximo 3 MB.');
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['imagem']['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        if (!isset($extensions[$mime])) {
            throw new \RuntimeException('Formato inválido. Envie JPG, PNG, WEBP ou GIF.');
        }
        $directory = dirname(__DIR__, 2) . '/public/imagens';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Não foi possível preparar a pasta de imagens.');
        }
        $filename = 'game_' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $directory . '/' . $filename)) {
            throw new \RuntimeException('Não foi possível salvar a imagem.');
        }
        return $filename;
    }
}
