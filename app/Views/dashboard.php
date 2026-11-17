<section class="stats-grid">
    <?php foreach (['games'=>'Jogos','categorias'=>'Categorias','usuarios'=>'Usuários','clientes'=>'Clientes','pedidos'=>'Pedidos'] as $chave=>$rotulo): ?>
        <article class="stat-card"><span class="stat-label"><?= e($rotulo) ?> cadastrados</span><strong><?= (int)$totais[$chave] ?></strong></article>
    <?php endforeach; ?>
</section>
<section class="stats-grid"><article class="stat-card"><span class="stat-label">Vendas registradas (não canceladas)</span><strong>R$ <?= number_format((float)$totais['faturamento'],2,',','.') ?></strong></article></section>
<section class="panel">
    <div class="panel-header"><div><p class="eyebrow">Projeto final · PHP 8 + MySQL</p><h2>Gerenciamento por módulos</h2><p>Os cinco módulos abaixo possuem operações de cadastro, consulta, edição e exclusão com regras de integridade.</p></div></div>
    <div class="crud-grid">
        <?php foreach ([['games','🎮','Games','Catálogo, preço, estoque e imagens.'],['categorias','🏷️','Categorias','Organização do catálogo e vínculo com jogos.'],['usuarios','👤','Usuários','Contas administrativas, operadores e clientes.'],['clientes','🛍️','Clientes','Dados de contato e entrega dos compradores.'],['pedidos','📦','Pedidos','Registro, consulta, atualização de status e cancelamento.']] as [$slug,$icon,$title,$description]): ?>
            <article class="crud-card"><span class="crud-icon"><?= $icon ?></span><div><p class="eyebrow">Módulo MVC</p><h3><?= e($title) ?></h3><p><?= e($description) ?></p></div><p class="feature-line">Criar · consultar · editar · excluir</p><div class="quick-actions"><a class="btn btn-primary" href="index.php?entidade=<?= e($slug) ?>">Abrir módulo</a><a class="btn btn-secondary" href="index.php?entidade=<?= e($slug) ?>&acao=novo">Novo cadastro</a></div></article>
        <?php endforeach; ?>
    </div>
</section>
<section class="panel"><div class="panel-header"><div><h2>Arquitetura do projeto</h2><p>Controllers recebem as ações, Models aplicam as regras e Views apresentam os dados.</p></div><a href="../../README.md" target="_blank" rel="noopener">README do projeto</a></div><div class="table-wrap"><table><thead><tr><th>Camada</th><th>Diretório</th><th>Responsabilidade</th></tr></thead><tbody><tr><td>Models</td><td><code>app/Models</code></td><td>Regras de negócio e acesso PDO ao banco.</td></tr><tr><td>Views</td><td><code>app/Views</code></td><td>Interface HTML renderizada pelo sistema.</td></tr><tr><td>Controllers</td><td><code>app/Controllers</code></td><td>Validação das entradas e coordenação do fluxo.</td></tr><tr><td>Core</td><td><code>app/Core</code></td><td>Funções compartilhadas e renderização.</td></tr></tbody></table></div></section>
