<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/upload.php';
require_once __DIR__ . '/../models/games.php';

exigirLogin();

$erros = [];
$dados = [
    'titulo' => '',
    'descricao' => '',
    'preco' => '',
    'estoque' => '0',
    'categoria' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCsrf($_POST['csrf_token'] ?? null)) {
        $erros[] = 'Sessão expirada ou formulário inválido. Atualize a página e tente novamente.';
    }

    foreach (array_keys($dados) as $campo) {
        $dados[$campo] = trim((string) ($_POST[$campo] ?? ''));
    }

    if ($dados['titulo'] === '') $erros[] = 'Informe o título.';
    if ($dados['descricao'] === '') $erros[] = 'Informe a descrição.';
    if ($dados['categoria'] === '') $erros[] = 'Informe a categoria.';
    if (!is_numeric(str_replace(',', '.', $dados['preco'])) || (float) str_replace(',', '.', $dados['preco']) < 0) {
        $erros[] = 'Informe um preço válido.';
    }
    if (filter_var($dados['estoque'], FILTER_VALIDATE_INT) === false || (int) $dados['estoque'] < 0) {
        $erros[] = 'Informe um estoque inteiro maior ou igual a zero.';
    }

    if (!$erros) {
        try {
            $imagem = salvarImagemJogo($_FILES['imagem'] ?? []);

            $game = new Game([
                'titulo' => $dados['titulo'],
                'descricao' => $dados['descricao'],
                'preco' => (float) str_replace(',', '.', $dados['preco']),
                'estoque' => (int) $dados['estoque'],
                'categoria' => $dados['categoria'],
                'imagem' => $imagem,
            ]);

            $game->cadastrarGames();
            definirFlash('sucesso', 'Jogo cadastrado com sucesso.');
            header('Location: ver_games.php');
            exit;
        } catch (Throwable $e) {
            $erros[] = $e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível cadastrar o jogo.';
        }
    }
}

$pageTitle = 'Cadastrar Jogo';
require __DIR__ . '/partials/header.php';
?>

<section class="panel form-panel">
    <?php if ($erros): ?>
        <div class="alert alert-erro">
            <strong>Corrija os campos abaixo:</strong>
            <ul>
                <?php foreach ($erros as $erro): ?><li><?= e($erro) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

        <div class="field span-2">
            <label for="titulo">Título *</label>
            <input id="titulo" name="titulo" maxlength="150" required value="<?= e($dados['titulo']) ?>">
        </div>

        <div class="field span-2">
            <label for="descricao">Descrição *</label>
            <textarea id="descricao" name="descricao" rows="5" maxlength="2000" required><?= e($dados['descricao']) ?></textarea>
        </div>

        <div class="field">
            <label for="preco">Preço (R$) *</label>
            <input id="preco" name="preco" inputmode="decimal" placeholder="199,90" required value="<?= e($dados['preco']) ?>">
        </div>

        <div class="field">
            <label for="estoque">Estoque *</label>
            <input id="estoque" type="number" name="estoque" min="0" step="1" required value="<?= e($dados['estoque']) ?>">
        </div>

        <div class="field">
            <label for="categoria">Categoria *</label>
            <input id="categoria" name="categoria" maxlength="100" placeholder="Ex.: Aventura" required value="<?= e($dados['categoria']) ?>">
        </div>

        <div class="field">
            <label for="imagem">Imagem</label>
            <input id="imagem" type="file" name="imagem" accept="image/jpeg,image/png,image/webp">
            <small>JPG, PNG ou WEBP, até 2 MB.</small>
        </div>

        <div class="form-actions span-2">
            <a class="btn btn-ghost" href="ver_games.php">Cancelar</a>
            <button class="btn btn-primary" type="submit">Salvar jogo</button>
        </div>
    </form>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
