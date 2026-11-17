<?php

declare(strict_types=1);

require_once __DIR__ . '/admin/config/auth.php';
require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/admin/models/games.php';
require_once __DIR__ . '/includes/cart_helpers.php';

global $pdo;
$itens = carregarItensCarrinho($pdo);
$total = totalCarrinho($itens);
$erros = [];
$endereco = trim((string) ($_POST['endereco'] ?? ''));
$cidade = trim((string) ($_POST['cidade'] ?? ''));
$cep = trim((string) ($_POST['cep'] ?? ''));
$formaPagamento = (string) ($_POST['forma_pagamento'] ?? 'pix');
$formasPermitidas = ['pix', 'cartao', 'boleto'];
$quantidadeItens = array_sum(array_map(static fn(array $item): int => (int) $item['quantidade'], $itens));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && estaLogado() && ($_SESSION['usuario_nivel'] ?? '') === 'cliente' && $itens) {
    if (!validarCsrf($_POST['csrf_token'] ?? null)) {
        $erros[] = 'Sua sessão expirou. Atualize a página e tente novamente.';
    }
    if ($endereco === '' || (function_exists('mb_strlen') ? mb_strlen($endereco, 'UTF-8') : strlen($endereco)) < 5) {
        $erros[] = 'Informe um endereço de entrega válido.';
    }
    if ($cidade === '') {
        $erros[] = 'Informe a cidade e o estado.';
    }
    if (!preg_match('/^[0-9A-Za-z -]{5,12}$/', $cep)) {
        $erros[] = 'Informe um CEP válido (5 a 12 caracteres).';
    }
    if (!in_array($formaPagamento, $formasPermitidas, true)) {
        $erros[] = 'Selecione uma forma de pagamento válida.';
    }

    if (!$erros) {
        try {
            $pdo->beginTransaction();

            // Confere novamente preço e estoque dentro da transação.
            $consultaJogo = $pdo->prepare(
                'SELECT id, titulo, preco, estoque FROM games WHERE id = :id FOR UPDATE'
            );
            $itensConfirmados = [];
            $carrinhoSessao = $_SESSION['carrinho'] ?? [];

            foreach ($carrinhoSessao as $idBruto => $quantidadeBruta) {
                $id = filter_var($idBruto, FILTER_VALIDATE_INT);
                $quantidade = filter_var($quantidadeBruta, FILTER_VALIDATE_INT);
                if (!$id || !$quantidade || $quantidade < 1) {
                    throw new RuntimeException('Um item do carrinho é inválido. Revise seu carrinho.');
                }

                $consultaJogo->execute([':id' => $id]);
                $jogo = $consultaJogo->fetch();

                if (!$jogo) {
                    throw new RuntimeException('Um jogo do carrinho não está mais disponível. Revise seu carrinho.');
                }
                if ((int) $jogo['estoque'] < $quantidade) {
                    throw new RuntimeException('Estoque insuficiente para "' . $jogo['titulo'] . '". Atualize as quantidades no carrinho.');
                }

                $preco = (float) $jogo['preco'];
                $linha = round($preco * $quantidade, 2);
                $itensConfirmados[] = [
                    'id' => (int) $jogo['id'],
                    'titulo' => $jogo['titulo'],
                    'preco' => $preco,
                    'quantidade' => (int) $quantidade,
                    'subtotal' => $linha,
                ];
            }

            if (!$itensConfirmados) {
                throw new RuntimeException('Seu carrinho está vazio. Adicione um jogo para continuar.');
            }

            // A camada Model centraliza a criação do pedido, os itens e a baixa de estoque.
            $pedidoModel = new \App\Models\Pedido($pdo);
            $pedidoId = $pedidoModel->criar([
                'usuario_id' => (int) $_SESSION['usuario_id'],
                'nome_destinatario' => (string) ($_SESSION['usuario_nome'] ?? ''),
                'endereco' => $endereco,
                'cidade' => $cidade,
                'cep' => $cep,
                'forma_pagamento' => $formaPagamento,
                'frete' => 0,
            ], array_map(static fn(array $item): array => [
                'game_id' => (int) $item['id'],
                'quantidade' => (int) $item['quantidade'],
            ], $itensConfirmados));

            $pdo->commit();
            $_SESSION['carrinho'] = [];
            header('Location: pedido.php?id=' . $pedidoId);
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // Registra detalhes técnicos no log do servidor sem expor SQL ao cliente.
            error_log('[Loja Games] Falha ao finalizar pedido: ' . $e->getMessage());
            $erros[] = ($e instanceof RuntimeException && !($e instanceof PDOException))
                ? $e->getMessage()
                : 'Não foi possível registrar seu pedido agora. Tente novamente em instantes.';
            $itens = carregarItensCarrinho($pdo);
            $total = totalCarrinho($itens);
            $quantidadeItens = array_sum(array_map(static fn(array $item): int => (int) $item['quantidade'], $itens));
        }
    }
}

$storeTitle = 'Finalizar compra';
require __DIR__ . '/includes/store_header.php';
?>

<main class="container page-content checkout-page">
    <div class="page-heading checkout-heading">
        <div>
            <p class="eyebrow">Finalização do pedido</p>
            <h1>Finalize sua compra</h1>
            <p class="muted">Confira os produtos, informe a entrega e revise o total antes de confirmar.</p>
        </div>
        <a class="btn btn-ghost" href="carrinho.php">← Voltar ao carrinho</a>
    </div>

    <nav class="checkout-steps" aria-label="Etapas da compra">
        <div class="checkout-step is-complete"><span class="checkout-step-number">1</span><span>Carrinho</span></div>
        <span class="checkout-step-line is-complete" aria-hidden="true"></span>
        <div class="checkout-step is-active"><span class="checkout-step-number">2</span><span>Entrega e pagamento</span></div>
        <span class="checkout-step-line" aria-hidden="true"></span>
        <div class="checkout-step"><span class="checkout-step-number">3</span><span>Confirmação</span></div>
    </nav>

    <?php if ($erros): ?>
        <div class="alert alert-erro checkout-error" role="alert">
            <strong>Não foi possível concluir o pedido</strong>
            <ul><?php foreach ($erros as $erro): ?><li><?= e($erro) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <?php if (!$itens): ?>
        <section class="panel empty-state large checkout-empty">
            <div class="empty-cart-icon">🛒</div>
            <h2>Seu carrinho está vazio</h2>
            <p>Explore o catálogo e adicione seus jogos favoritos para continuar.</p>
            <a class="btn btn-primary" href="index.php#catalogo">Explorar catálogo</a>
        </section>
    <?php elseif (!estaLogado()): ?>
        <div class="checkout-grid checkout-grid-premium">
            <section class="panel checkout-panel checkout-gate">
                <div class="checkout-section-heading">
                    <span class="checkout-section-number">01</span>
                    <div><h2>Entre para continuar</h2><p>Use sua conta ou crie uma nova em poucos passos.</p></div>
                </div>
                <div class="checkout-account-prompt">
                    <span class="checkout-account-icon">👤</span>
                    <div><strong>Seus pedidos em um só lugar</strong><p>Acesse o histórico de compras e acompanhe o status dos seus pedidos.</p></div>
                </div>
                <div class="stack-actions">
                    <a class="btn btn-primary btn-block" href="login.php?redirect=checkout.php">Entrar na minha conta</a>
                    <a class="btn btn-secondary btn-block" href="cadastro.php?redirect=checkout.php">Criar conta gratuitamente</a>
                </div>
            </section>
            <aside class="panel cart-summary checkout-summary">
                <div class="checkout-summary-heading"><div><p class="eyebrow">Resumo</p><h2>Seu pedido</h2></div><span class="checkout-item-count"><?= $quantidadeItens ?> <?= $quantidadeItens === 1 ? 'item' : 'itens' ?></span></div>
                <?php foreach ($itens as $item): ?>
                    <?php $imagemResumo = (string) ($item['imagem'] ?? ''); $temImagemResumo = $imagemResumo !== '' && is_file(__DIR__ . '/public/imagens/' . basename($imagemResumo)); ?>
                    <div class="checkout-summary-item">
                        <div class="checkout-summary-thumb"><?php if ($temImagemResumo): ?><img src="public/imagens/<?= e(basename($imagemResumo)) ?>" alt=""><?php else: ?><span>🎮</span><?php endif; ?></div>
                        <div class="checkout-summary-product"><strong><?= e($item['titulo']) ?></strong><span><?= (int) $item['quantidade'] ?> × R$ <?= number_format((float) $item['preco'], 2, ',', '.') ?></span></div>
                        <strong class="checkout-summary-subtotal">R$ <?= number_format((float) $item['subtotal'], 2, ',', '.') ?></strong>
                    </div>
                <?php endforeach; ?>
                <div class="checkout-total-line"><span>Entrega</span><strong class="success-text">Grátis</strong></div>
                <div class="checkout-grand-total"><span>Total do pedido</span><strong>R$ <?= number_format($total, 2, ',', '.') ?></strong></div>
            </aside>
        </div>
    <?php elseif (($_SESSION['usuario_nivel'] ?? '') !== 'cliente'): ?>
        <section class="panel checkout-panel checkout-gate">
            <div class="checkout-section-heading"><span class="checkout-section-number">01</span><div><h2>Entre com uma conta de cliente</h2><p>Esta sessão está conectada à área administrativa.</p></div></div>
            <p class="muted">Para realizar uma compra, saia da conta da equipe e entre ou crie uma conta de cliente.</p>
            <div class="stack-actions"><a class="btn btn-primary" href="admin/controllers/logout.php">Sair da área da equipe</a><a class="btn btn-ghost" href="index.php">Voltar à loja</a></div>
        </section>
    <?php else: ?>
        <div class="checkout-grid checkout-grid-premium">
            <section class="panel checkout-panel">
                <div class="checkout-section-heading">
                    <span class="checkout-section-number">01</span>
                    <div><h2>Dados de entrega</h2><p>Informe onde seu pedido deve ser entregue.</p></div>
                </div>
                <div class="checkout-account-strip"><span class="checkout-account-icon">✓</span><div><small>Pedido associado à conta</small><strong><?= e($_SESSION['usuario_email'] ?? '') ?></strong></div></div>

                <form method="post" class="checkout-form checkout-form-premium">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <div class="field">
                        <label for="endereco">Endereço completo</label>
                        <input id="endereco" name="endereco" autocomplete="street-address" maxlength="255" required value="<?= e($endereco) ?>" placeholder="Rua, número e complemento">
                    </div>
                    <div class="checkout-form-row">
                        <div class="field">
                            <label for="cidade">Cidade / Estado</label>
                            <input id="cidade" name="cidade" autocomplete="address-level2" maxlength="120" required value="<?= e($cidade) ?>" placeholder="Ex.: Montes Claros - MG">
                        </div>
                        <div class="field">
                            <label for="cep">CEP</label>
                            <input id="cep" name="cep" autocomplete="postal-code" maxlength="12" required value="<?= e($cep) ?>" placeholder="39400-000">
                        </div>
                    </div>

                    <div class="checkout-payment-section">
                        <div class="checkout-section-heading checkout-payment-heading">
                            <span class="checkout-section-number">02</span>
                            <div><h2>Forma de pagamento</h2><p>Selecione como prefere pagar.</p></div>
                        </div>
                        <div class="checkout-payment-options">
                            <label class="payment-option <?= $formaPagamento === 'pix' ? 'is-selected' : '' ?>">
                                <input type="radio" name="forma_pagamento" value="pix" <?= $formaPagamento === 'pix' ? 'checked' : '' ?> required>
                                <span class="payment-option-symbol">✦</span><span class="payment-option-copy"><strong>Pix</strong><small>Pagamento instantâneo</small></span><span class="payment-option-indicator"></span>
                            </label>
                            <label class="payment-option <?= $formaPagamento === 'cartao' ? 'is-selected' : '' ?>">
                                <input type="radio" name="forma_pagamento" value="cartao" <?= $formaPagamento === 'cartao' ? 'checked' : '' ?> required>
                                <span class="payment-option-symbol">▤</span><span class="payment-option-copy"><strong>Cartão</strong><small>Crédito ou débito</small></span><span class="payment-option-indicator"></span>
                            </label>
                            <label class="payment-option <?= $formaPagamento === 'boleto' ? 'is-selected' : '' ?>">
                                <input type="radio" name="forma_pagamento" value="boleto" <?= $formaPagamento === 'boleto' ? 'checked' : '' ?> required>
                                <span class="payment-option-symbol">▥</span><span class="payment-option-copy"><strong>Boleto</strong><small>Pagamento bancário</small></span><span class="payment-option-indicator"></span>
                            </label>
                        </div>
                        <p class="checkout-payment-note"><span aria-hidden="true">ⓘ</span> O pagamento online ainda não está integrado. Não informe dados de cartão nesta etapa.</p>
                    </div>

                    <div class="checkout-submit-area">
                        <button class="btn btn-primary btn-block checkout-submit" type="submit"><span>Confirmar pedido</span><strong>R$ <?= number_format($total, 2, ',', '.') ?></strong></button>
                        <p>Ao confirmar, seu pedido será registrado e o estoque dos produtos será atualizado.</p>
                    </div>
                </form>
            </section>

            <aside class="panel cart-summary checkout-summary">
                <div class="checkout-summary-heading"><div><p class="eyebrow">Resumo da compra</p><h2>Seu pedido</h2></div><span class="checkout-item-count"><?= $quantidadeItens ?> <?= $quantidadeItens === 1 ? 'item' : 'itens' ?></span></div>
                <div class="checkout-summary-items">
                    <?php foreach ($itens as $item): ?>
                        <?php $imagemResumo = (string) ($item['imagem'] ?? ''); $temImagemResumo = $imagemResumo !== '' && is_file(__DIR__ . '/public/imagens/' . basename($imagemResumo)); ?>
                        <div class="checkout-summary-item">
                            <div class="checkout-summary-thumb"><?php if ($temImagemResumo): ?><img src="public/imagens/<?= e(basename($imagemResumo)) ?>" alt="Capa de <?= e($item['titulo']) ?>"><?php else: ?><span>🎮</span><?php endif; ?></div>
                            <div class="checkout-summary-product"><strong><?= e($item['titulo']) ?></strong><span><?= (int) $item['quantidade'] ?> × R$ <?= number_format((float) $item['preco'], 2, ',', '.') ?></span></div>
                            <strong class="checkout-summary-subtotal">R$ <?= number_format((float) $item['subtotal'], 2, ',', '.') ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="checkout-total-line"><span>Subtotal</span><strong>R$ <?= number_format($total, 2, ',', '.') ?></strong></div>
                <div class="checkout-total-line"><span>Entrega</span><strong class="success-text">Grátis</strong></div>
                <div class="checkout-grand-total"><span>Total do pedido</span><strong>R$ <?= number_format($total, 2, ',', '.') ?></strong></div>
                <div class="checkout-summary-footer"><span class="checkout-summary-footer-icon">✓</span><span>Você poderá acompanhar o andamento em <strong>Meus pedidos</strong>.</span></div>
                <a class="btn btn-ghost btn-block checkout-edit-cart" href="carrinho.php">Editar carrinho</a>
            </aside>
        </div>
    <?php endif; ?>
</main>

<script>
document.querySelectorAll('.payment-option input[type="radio"]').forEach(function (input) {
    input.addEventListener('change', function () {
        document.querySelectorAll('.payment-option').forEach(function (option) {
            option.classList.toggle('is-selected', option.querySelector('input').checked);
        });
    });
});
</script>

<?php require __DIR__ . '/includes/store_footer.php'; ?>
