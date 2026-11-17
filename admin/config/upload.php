<?php

declare(strict_types=1);

function salvarImagemJogo(array $arquivo, ?string $imagemAtual = null): ?string
{
    if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $imagemAtual;
    }

    if (($arquivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha no envio da imagem.');
    }

    if (($arquivo['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new RuntimeException('A imagem deve ter no máximo 2 MB.');
    }

    $tmp = $arquivo['tmp_name'] ?? '';
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('Arquivo de imagem inválido.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);

    $extensoes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($extensoes[$mime])) {
        throw new RuntimeException('Formato inválido. Use JPG, PNG ou WEBP.');
    }

    $diretorio = __DIR__ . '/../../public/imagens';
    if (!is_dir($diretorio) && !mkdir($diretorio, 0775, true) && !is_dir($diretorio)) {
        throw new RuntimeException('Não foi possível criar a pasta de imagens.');
    }

    $novoNome = 'game_' . bin2hex(random_bytes(8)) . '.' . $extensoes[$mime];
    $destino = $diretorio . '/' . $novoNome;

    if (!move_uploaded_file($tmp, $destino)) {
        throw new RuntimeException('Não foi possível salvar a imagem.');
    }

    if ($imagemAtual && $imagemAtual !== $novoNome) {
        removerImagemJogo($imagemAtual);
    }

    return $novoNome;
}

function removerImagemJogo(?string $nomeArquivo): void
{
    if (!$nomeArquivo) {
        return;
    }

    $nomeSeguro = basename($nomeArquivo);
    $arquivo = __DIR__ . '/../../public/imagens/' . $nomeSeguro;

    if (is_file($arquivo)) {
        @unlink($arquivo);
    }
}
