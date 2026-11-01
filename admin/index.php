<?php require_once __DIR__ . '/../includes/layout.php';
admin_only();
$p = db();
$ok = "o.status NOT IN('cancelado','aguardando_pagamento')";
$v = (float) $p->query("SELECT COALESCE(SUM(i.quantidade*i.preco_unitario),0) FROM pedidos o JOIN itens_pedido i ON i.id_pedido=o.id_pedido WHERE $ok")->fetchColumn();
$orders = (int) $p->query('SELECT COUNT(*) FROM pedidos')->fetchColumn();
$products = (int) $p->query('SELECT COUNT(*) FROM produtos')->fetchColumn();
$clients = (int) $p->query("SELECT COUNT(*) FROM users WHERE tipo='cliente'")->fetchColumn();
$stock = $p->query('SELECT codigo,nome,estoque FROM produtos WHERE estoque<=5 ORDER BY estoque,nome LIMIT 5')->fetchAll();
$recent = $p->query("SELECT o.id_pedido,o.data_pedido,o.status,CONCAT(u.nome,' ',u.sobrenome) cliente,COALESCE(SUM(i.quantidade*i.preco_unitario),0) total FROM pedidos o JOIN users u ON u.id=o.id_usuario LEFT JOIN itens_pedido i ON i.id_pedido=o.id_pedido GROUP BY o.id_pedido ORDER BY o.data_pedido DESC LIMIT 5")->fetchAll();

/* vendas dos últimos 6 meses, calculadas do banco */
$meses = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
$serie = [];
for ($k = 5; $k >= 0; $k--) {
    $d = new DateTime('first day of this month');
    $d->modify("-$k month");
    $serie[$d->format('Y-m')] = ['label' => $meses[(int) $d->format('n')], 'total' => 0.0];
}
$r = $p->query("SELECT DATE_FORMAT(o.data_pedido,'%Y-%m') m,SUM(i.quantidade*i.preco_unitario) t FROM pedidos o JOIN itens_pedido i ON i.id_pedido=o.id_pedido WHERE $ok AND o.data_pedido>=DATE_SUB(DATE_FORMAT(NOW(),'%Y-%m-01'),INTERVAL 5 MONTH) GROUP BY m")->fetchAll();
foreach ($r as $x)
    if (isset($serie[$x['m']]))
        $serie[$x['m']]['total'] = (float) $x['t'];
$max = max(1, max(array_column($serie, 'total')));
$mesAtual = array_key_last($serie);

layout_head('Visão geral', 'home', 'Acompanhe o desempenho da sua loja.'); ?>
<section class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">R$</div>
        <div><span>Vendas (total)</span><strong><?= brl($v) ?></strong></div>
    </div>
    <a class="stat-card" href="pedidos.html" style="text-decoration:none;color:inherit">
        <div class="stat-icon">🛒</div>
        <div><span>Pedidos</span><strong><?= $orders ?></strong></div>
    </a>
    <a class="stat-card" href="produtos.html" style="text-decoration:none;color:inherit">
        <div class="stat-icon">📦</div>
        <div><span>Produtos</span><strong><?= $products ?></strong></div>
    </a>
    <a class="stat-card" href="clientes.html" style="text-decoration:none;color:inherit">
        <div class="stat-icon">👥</div>
        <div><span>Clientes</span><strong><?= $clients ?></strong></div>
    </a>
</section>

<section class="dashboard-grid">
    <div class="panel">
        <div class="panel-header">
            <div>
                <h2>Vendas</h2>
                <p>Últimos 6 meses (passe o mouse nas barras)</p>
            </div>
            <a href="relatorios.html">Ver relatório</a>
        </div>
        <div class="chart">
            <div class="chart-value"><?= brl($serie[$mesAtual]['total']) ?> <small class="muted">neste mês</small></div>
            <div class="bars">
                <?php foreach ($serie as $m => $s):
                    $h = max(3, round($s['total'] / $max * 100)); ?>
                    <div class="bar-container" style="--h:<?= $h ?>%">
                        <span class="tip"><?= brl($s['total']) ?></span>
                        <div class="bar<?= $m === $mesAtual ? ' highlight' : '' ?>" style="height:<?= $h ?>%"></div>
                        <span><?= e($s['label']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="panel">
        <div class="panel-header">
            <div>
                <h2>Estoque baixo</h2>
                <p>Produtos com até 5 unidades</p>
            </div><a href="produtos.html?baixo=1">Ver todos</a>
        </div>
        <div class="stock-list">
            <?php foreach ($stock as $x): ?>
                <a class="stock-item" href="produtos.html?baixo=1" style="text-decoration:none;color:inherit">
                    <div class="product-placeholder">📦</div>
                    <div class="stock-info"><strong><?= e($x['nome']) ?></strong><span><?= e($x['codigo']) ?></span></div>
                    <strong class="low-stock"><?= (int) $x['estoque'] ?> un.</strong>
                </a>
            <?php endforeach;
            if (!$stock)
                echo '<div class="empty">Nenhum produto com estoque baixo.</div>'; ?>
        </div>
        <div class="quick-links">
            <button type="button" class="primary-btn" onclick="location.href='produtos.html'">+ Produto</button>
            <button type="button" class="btn-secondary" onclick="location.href='categorias.html'">+ Categoria</button>
        </div>
    </div>
</section>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2>Pedidos recentes</h2>
            <p>Últimos pedidos realizados</p>
        </div><a href="pedidos.html">Ver todos</a>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr><th>Pedido</th><th>Cliente</th><th>Data</th><th>Total</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $x):
                    $st = STATUS_PEDIDO[$x['status']] ?? [$x['status'], 'pending']; ?>
                    <tr>
                        <td><a href="pedidos.html"><strong>#<?= (int) $x['id_pedido'] ?></strong></a></td>
                        <td><?= e($x['cliente']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($x['data_pedido'])) ?></td>
                        <td><?= brl($x['total']) ?></td>
                        <td><span class="status <?= e($st[1]) ?>"><?= e($st[0]) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recent): ?>
                    <tr><td class="empty" colspan="5">Nenhum pedido ainda.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php layout_foot();
