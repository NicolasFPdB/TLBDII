<?php require_once __DIR__ . '/../includes/layout.php';
admin_only();
$p = db();

/* meses disponíveis + mês escolhido */
$meses = $p->query("SELECT DISTINCT DATE_FORMAT(data_pedido,'%Y-%m') m FROM pedidos ORDER BY m DESC")->fetchAll(PDO::FETCH_COLUMN);
$nomes = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
$sel = $_GET['mes'] ?? 'todos';
if ($sel !== 'todos' && !preg_match('/^\d{4}-\d{2}$/', $sel))
    $sel = 'todos';
$filtro = $sel === 'todos' ? '' : ' AND DATE_FORMAT(o.data_pedido,\'%Y-%m\')=:mes';
$bind = $sel === 'todos' ? [] : [':mes' => $sel];
$valido = "o.status NOT IN('cancelado','aguardando_pagamento')";

$q = $p->prepare("SELECT COALESCE(SUM(i.quantidade*i.preco_unitario),0) fat,COUNT(DISTINCT o.id_pedido) n,COALESCE(SUM(i.quantidade),0) un FROM pedidos o JOIN itens_pedido i ON i.id_pedido=o.id_pedido WHERE $valido$filtro");
$q->execute($bind);
$t = $q->fetch();
$fat = (float) $t['fat'];
$n = (int) $t['n'];
$u = (int) $t['un'];
$ticket = $n ? $fat / $n : 0;

/* categorias: só pedidos válidos entram nas somas (corrige contagem de cancelados) */
$q = $p->prepare("SELECT c.nome,COALESCE(SUM(x.quantidade),0) unidades,COUNT(DISTINCT x.id_pedido) pedidos,COALESCE(SUM(x.quantidade*x.preco_unitario),0) faturamento
    FROM categorias c
    LEFT JOIN (SELECT i.id_pedido,i.quantidade,i.preco_unitario,pr.id_categoria FROM itens_pedido i JOIN produtos pr ON pr.id_produto=i.id_produto JOIN pedidos o ON o.id_pedido=i.id_pedido WHERE $valido$filtro) x ON x.id_categoria=c.id_categoria
    GROUP BY c.id_categoria ORDER BY faturamento DESC");
$q->execute($bind);
$cats = $q->fetchAll();
$maxFat = max(1, (float) max(array_column($cats, 'faturamento') ?: [0]));

$rotulo = $sel === 'todos' ? 'Todo o período' : $nomes[(int) substr($sel, 5)] . ' de ' . substr($sel, 0, 4);
layout_head('Relatórios', 'reports', 'Indicadores calculados diretamente do banco.',
    '<button type="button" class="btn-secondary" data-export-csv="#tbl-cat" data-filename="relatorio-categorias">⬇ Exportar CSV</button>'); ?>

<div class="toolbar">
    <select class="select-input" onchange="location.href='relatorios.html?mes='+this.value" aria-label="Período">
        <option value="todos" <?= $sel === 'todos' ? 'selected' : '' ?>>Todo o período</option>
        <?php foreach ($meses as $m): ?>
            <option value="<?= e($m) ?>" <?= $sel === $m ? 'selected' : '' ?>><?= e($nomes[(int) substr($m, 5)] . '/' . substr($m, 0, 4)) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<section class="report-grid" style="margin-bottom:20px">
    <div class="report-card"><h3>Faturamento</h3><strong><?= brl($fat) ?></strong><p><?= e($rotulo) ?></p></div>
    <div class="report-card"><h3>Ticket médio</h3><strong><?= brl($ticket) ?></strong><p><?= $n ?> pedido(s) válidos</p></div>
    <div class="report-card"><h3>Unidades vendidas</h3><strong><?= $u ?></strong><p>Pedidos cancelados/não pagos ficam de fora</p></div>
</section>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2>Desempenho por categoria</h2>
            <p>Clique no título da coluna para ordenar</p>
        </div>
    </div>
    <div class="table-container">
        <table id="tbl-cat" data-table>
            <thead>
                <tr>
                    <th data-sort="text">Categoria</th>
                    <th data-sort="num">Unidades</th>
                    <th data-sort="num">Pedidos</th>
                    <th data-sort="num">Faturamento</th>
                    <th>Participação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cats as $x):
                    $pc = $fat > 0 ? round($x['faturamento'] / $fat * 100) : 0; ?>
                    <tr>
                        <td><?= e($x['nome']) ?></td>
                        <td data-value="<?= (int) $x['unidades'] ?>"><?= (int) $x['unidades'] ?></td>
                        <td data-value="<?= (int) $x['pedidos'] ?>"><?= (int) $x['pedidos'] ?></td>
                        <td data-value="<?= e($x['faturamento']) ?>"><?= brl($x['faturamento']) ?></td>
                        <td data-value="<?= $pc ?>" style="min-width:140px">
                            <div style="background:#f0f0f0;border-radius:4px;height:8px"><div style="width:<?= max(0, min(100, round($x['faturamento'] / $maxFat * 100))) ?>%;background:#ee4d2d;height:8px;border-radius:4px"></div></div>
                            <span class="muted"><?= $pc ?>%</span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$cats): ?>
                    <tr class="empty-row"><td class="empty" colspan="5">Sem dados no período.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php layout_foot();
