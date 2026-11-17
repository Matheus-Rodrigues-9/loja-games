<?php

declare(strict_types=1);

require_once __DIR__ . '/admin/config/auth.php';
require_once __DIR__ . '/admin/models/games.php';
require_once __DIR__ . '/includes/cart_helpers.php';

$model = new Game();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCsrf($_POST['csrf_token'] ?? null)) {
        definirFlash('erro', 'Sessão expirada. Atualize a página e tente novamente.');
        header('Location: carrinho.php');
        exit;
    }

    $acao = (string) ($_POST['acao'] ?? '');
    $carrinho = $_SESSION['carrinho'] ?? [];
    if (!is_array($carrinho)) {
        $carrinho = [];
    }

    if ($acao === 'adicionar') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $quantidade = filter_var($_POST['quantidade'] ?? 1, FILTER_VALIDATE_INT);
        $jogo = $id ? $model->buscarGamePorId((int) $id) : false;

        if (!$jogo) {
            definirFlash('erro', 'Esse jogo não foi encontrado.');
        } elseif ((int) $jogo['estoque'] < 1) {
            definirFlash('erro', 'Esse jogo está esgotado no momento.');
        } else {
            $quantidade = max(1, (int) $quantidade);
            $atual = (int) ($carrinho[(int) $id] ?? 0);
            $novaQuantidade = $atual + $quantidade;
            $estoque = (int) $jogo['estoque'];

            if ($novaQuantidade > $estoque) {
                definirFlash('aviso', 'Quantidade ajustada ao estoque disponível de ' . $estoque . ' unidade(s).');
                $novaQuantidade = $estoque;
            } else {
                definirFlash('sucesso', 'Jogo adicionado ao carrinho.');
            }

            $carrinho[(int) $id] = $novaQuantidade;
            $_SESSION['carrinho'] = $carrinho;
        }
    } elseif ($acao === 'atualizar') {
        $quantidades = $_POST['quantidades'] ?? [];
        if (is_array($quantidades)) {
            foreach ($quantidades as $idBruto => $qtdBruta) {
                $id = filter_var($idBruto, FILTER_VALIDATE_INT);
                $qtd = filter_var($qtdBruta, FILTER_VALIDATE_INT);
                if (!$id) {
                    continue;
                }

                if (!$qtd || $qtd < 1) {
                    unset($carrinho[$id]);
                    continue;
                }

                $jogo = $model->buscarGamePorId((int) $id);
                if (!$jogo || (int) $jogo['estoque'] < 1) {
                    unset($carrinho[$id]);
                    continue;
                }

                $estoque = (int) $jogo['estoque'];
                if ($qtd > $estoque) {
                    $qtd = $estoque;
                    definirFlash('aviso', 'Algumas quantidades foram limitadas ao estoque disponível.');
                }

                $carrinho[$id] = $qtd;
            }
        }
        $_SESSION['carrinho'] = $carrinho;
        if (!isset($_SESSION['flash'])) {
            definirFlash('sucesso', 'Carrinho atualizado.');
        }
    } elseif ($acao === 'remover') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id) {
            unset($carrinho[(int) $id]);
        }
        $_SESSION['carrinho'] = $carrinho;
        definirFlash('sucesso', 'Jogo removido do carrinho.');
    } elseif ($acao === 'limpar') {
        $_SESSION['carrinho'] = [];
        definirFlash('sucesso', 'Carrinho esvaziado.');
    } else {
        definirFlash('erro', 'Ação não reconhecida.');
    }

    header('Location: carrinho.php');
    exit;
}

global $pdo;
$itens = carregarItensCarrinho($pdo);
$total = totalCarrinho($itens);
$storeTitle = 'Meu carrinho';
require __DIR__ . '/includes/store_header.php';
?>

<main class="container page-content">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Sua seleção</p>
            <h1>Meu carrinho</h1>
            <p class="muted">Confira os jogos e as quantidades antes de continuar.</p>
        </div>
        <a class="btn btn-ghost" href="index.php#catalogo">← Continuar comprando</a>
    </div>

    <?php if (!$itens): ?>
        <section class="panel empty-state large">
            <div class="empty-cart-icon">🛒</div>
            <h2>Seu carrinho está vazio</h2>
            <p>Explore o catálogo e adicione seus próximos jogos favoritos.</p>
            <a class="btn btn-primary" href="index.php#catalogo">Explorar jogos</a>
        </section>
    <?php else: ?>
        <div class="cart-layout">
            <section class="panel">
                <form method="post" action="carrinho.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="acao" value="atualizar">
                    <div class="cart-items">
                        <?php foreach ($itens as $item): ?>
                            <?php
                            $imagem = basename((string) ($item['imagem'] ?? ''));
                            $temImagem = $imagem !== '' && is_file(__DIR__ . '/public/imagens/' . $imagem);
                            ?>
                            <article class="cart-row">
                                <div class="cart-thumb">
                                    <?php if ($temImagem): ?>
                                        <img src="public/imagens/<?= e($imagem) ?>" alt="">
                                    <?php else: ?>
                                        <span>🎮</span>
                                    <?php endif; ?>
                                </div>
                                <div class="cart-product">
                                    <span class="badge badge-info"><?= e($item['categoria']) ?></span>
                                    <h2><?= e($item['titulo']) ?></h2>
                                    <p class="muted">R$ <?= number_format((float) $item['preco'], 2, ',', '.') ?> por unidade</p>
                                    <div class="cart-row-actions">
                                        <label class="quantity-field">
                                            <span>Quantidade</span>
                                            <input type="number" name="quantidades[<?= (int) $item['id'] ?>]" min="1" max="<?= (int) $item['estoque'] ?>" value="<?= (int) $item['quantidade'] ?>">
                                        </label>
                                        <button class="text-button danger-text" type="submit" form="remove-<?= (int) $item['id'] ?>">Remover</button>
                                    </div>
                                </div>
                                <strong class="cart-line-total">R$ <?= number_format((float) $item['subtotal'], 2, ',', '.') ?></strong>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <div class="cart-bottom-actions">
                        <button class="btn btn-secondary" type="submit">Atualizar quantidades</button>
                    </div>
                </form>

                <div class="cart-clear-row">
                    <?php foreach ($itens as $item): ?>
                        <form id="remove-<?= (int) $item['id'] ?>" method="post" action="carrinho.php">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="acao" value="remover">
                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                        </form>
                    <?php endforeach; ?>
                    <form method="post" action="carrinho.php" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="acao" value="limpar">
                        <button class="text-button danger-text" type="submit">Esvaziar carrinho</button>
                    </form>
                </div>
            </section>

            <aside class="panel cart-summary">
                <p class="eyebrow">Resumo do pedido</p>
                <h2>Subtotal</h2>
                <div class="summary-line"><span><?= array_sum(array_column($itens, 'quantidade')) ?> item(ns)</span><strong>R$ <?= number_format($total, 2, ',', '.') ?></strong></div>
                <div class="summary-line"><span>Entrega</span><strong class="success-text">Grátis</strong></div>
                <div class="summary-total"><span>Total</span><strong>R$ <?= number_format($total, 2, ',', '.') ?></strong></div>
                <a class="btn btn-primary btn-block" href="checkout.php">Continuar para compra →</a>
                <p class="muted small-note">O estoque será conferido novamente ao finalizar o pedido.</p>
            </aside>
        </div>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/includes/store_footer.php'; ?>
