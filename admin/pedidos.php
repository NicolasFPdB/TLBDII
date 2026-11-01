<?php require_once __DIR__ . '/../includes/layout.php';
admin_only();
$p = db();

/* Transições permitidas. Ajuste ao fluxo real da loja (ex.: pagamento na entrega pode
   exigir 'aguardando_pagamento' => ['processando', ...]). 'entregue' e 'cancelado' são finais. */
const TRANSICOES = [
    'aguardando_pagamento' => ['pago', 'cancelado'],
    'pago'                 => ['processando', 'cancelado'],
    'processando'          => ['enviado', 'cancelado'],
    'enviado'              => ['entregue'],
    'entregue'             => [],
    'cancelado'            => [],
];

/* alterar status (AJAX) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['op'] ?? '') === 'status') {
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $novo = (string) ($_POST['status'] ?? '');
    if (!$id || !isset(STATUS_PEDIDO[$novo]))
        json_out(['ok' => false, 'msg' => 'Dados inválidos.'], 422);

    try {
        $p->beginTransaction();
        $s = $p->prepare('SELECT status FROM pedidos WHERE id_pedido=? FOR UPDATE');   // trava a linha até o commit
        $s->execute([$id]);
        $atual = $s->fetchColumn();
        if ($atual === false) {
            $p->rollBack();
            json_out(['ok' => false, 'msg' => 'Pedido não encontrado.'], 404);
        }
        if ($atual === $novo) {                 // idempotente: repetir o clique não é erro
            $p->commit();
            json_out(['ok' => true, 'status' => $novo]);
        }
        if (!in_array($novo, TRANSICOES[$atual] ?? [], true)) {
            $p->rollBack();
            json_out(['ok' => false, 'msg' => 'Transição não permitida: ' . STATUS_PEDIDO[$atual][0] . ' → ' . STATUS_PEDIDO[$novo][0] . '.'], 409);
        }
        $p->prepare('UPDATE pedidos SET status=? WHERE id_pedido=?')->execute([$novo, $id]);

        /* Devolve o estoque ao cancelar. SÓ MANTENHA se a loja pública baixa o estoque na criação do
           pedido E não devolve sozinha no cancelamento — senão o estoque seria devolvido duas vezes.
           Como 'cancelado' é estado final, não há risco de devolver duas vezes por aqui. */
        if ($novo === 'cancelado') {
            $it = $p->prepare('SELECT id_produto,quantidade FROM itens_pedido WHERE id_pedido=?');
            $it->execute([$id]);
            $up = $p->prepare('UPDATE produtos SET estoque=estoque+? WHERE id_produto=?');
            $mv = $p->prepare('INSERT INTO movimentacoes_estoque(id_produto,id_usuario,tipo,quantidade,observacao) VALUES(?,?,?,?,?)');
            foreach ($it->fetchAll() as $x) {
                $up->execute([$x['quantidade'], $x['id_produto']]);
                $mv->execute([$x['id_produto'], $_SESSION['uid'], 'entrada', $x['quantidade'], "Cancelamento do pedido #$id."]);
            }
        }
        $p->commit();
        json_out(['ok' => true, 'status' => $novo]);
    } catch (Throwable $e) {
        if ($p->inTransaction())
            $p->rollBack();
        error_log('pedidos.status: ' . $e->getMessage());
        json_out(['ok' => false, 'msg' => 'Não foi possível atualizar o pedido.'], 500);
    }
}

/* detalhes do pedido (AJAX) */
if (isset($_GET['detalhe'])) {
    $id = (int) $_GET['detalhe'];
    $s = $p->prepare("SELECT o.id_pedido,o.data_pedido,o.status,o.pagamento,CONCAT(u.nome,' ',u.sobrenome) cliente,u.email FROM pedidos o JOIN users u ON u.id=o.id_usuario WHERE o.id_pedido=?");
    $s->execute([$id]);
    $o = $s->fetch();
    if (!$o)
        json_out(['ok' => false, 'msg' => 'Pedido não encontrado.'], 404);
    $i = $p->prepare('SELECT i.quantidade,i.preco_unitario,pr.nome,pr.codigo FROM itens_pedido i JOIN produtos pr ON pr.id_produto=i.id_produto WHERE i.id_pedido=?');
    $i->execute([$id]);
    $itens = $i->fetchAll();
    $total = array_sum(array_map(fn($x) => $x['quantidade'] * $x['preco_unitario'], $itens));
    $o['data'] = date('d/m/Y H:i', strtotime($o['data_pedido']));
    json_out(['ok' => true, 'pedido' => $o, 'itens' => $itens, 'total' => $total]);
}

$pendentes = ($_GET['status'] ?? '') === 'pendentes';
$sql = "SELECT o.id_pedido,o.data_pedido,o.status,o.pagamento,CONCAT(u.nome,' ',u.sobrenome) cliente,COALESCE(SUM(i.quantidade),0) qtd,COALESCE(SUM(i.quantidade*i.preco_unitario),0) total
        FROM pedidos o JOIN users u ON u.id=o.id_usuario LEFT JOIN itens_pedido i ON i.id_pedido=o.id_pedido"
    . ($pendentes ? " WHERE o.status IN('aguardando_pagamento','processando')" : '')
    . ' GROUP BY o.id_pedido ORDER BY o.data_pedido DESC LIMIT 500';
$rows = $p->query($sql)->fetchAll();

layout_head('Pedidos', 'orders', 'Acompanhe e atualize os pedidos realizados pelos clientes.'); ?>

<div class="chips">
    <a class="chip<?= $pendentes ? '' : ' on' ?>" href="pedidos.html">Todos</a>
    <a class="chip<?= $pendentes ? ' on' : '' ?>" href="pedidos.html?status=pendentes">Pendentes</a>
</div>

<div class="toolbar">
    <input class="search-input" data-table-search="#tbl-pedidos" placeholder="Buscar por número do pedido, cliente ou pagamento…  (atalho: /)">
    <select class="select-input" data-table-filter="#tbl-pedidos" data-col="6">
        <option value="">Todos os status</option>
        <?php foreach (STATUS_PEDIDO as $v): ?>
            <option><?= e($v[0]) ?></option><?php endforeach; ?>
    </select>
</div>
<div class="result-count" data-count-for="#tbl-pedidos"></div>

<div class="panel">
    <div class="table-container">
        <table id="tbl-pedidos" data-table>
            <thead>
                <tr>
                    <th data-sort="num">Pedido</th>
                    <th data-sort="text">Cliente</th>
                    <th data-sort="num">Data</th>
                    <th data-sort="num">Itens</th>
                    <th data-sort="num">Total</th>
                    <th data-sort="text">Pagamento</th>
                    <th data-sort="text">Status</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $x):
                    $st = STATUS_PEDIDO[$x['status']] ?? [$x['status'], 'pending']; ?>
                    <tr>
                        <td data-value="<?= (int) $x['id_pedido'] ?>"><strong>#<?= (int) $x['id_pedido'] ?></strong></td>
                        <td><?= e($x['cliente']) ?></td>
                        <td data-value="<?= strtotime($x['data_pedido']) ?>"><?= date('d/m/Y H:i', strtotime($x['data_pedido'])) ?></td>
                        <td data-value="<?= (int) $x['qtd'] ?>"><?= (int) $x['qtd'] ?> <?= $x['qtd'] == 1 ? 'item' : 'itens' ?></td>
                        <td data-value="<?= e($x['total']) ?>"><?= brl($x['total']) ?></td>
                        <td><?= e($x['pagamento'] ?? '—') ?></td>
                        <td data-value="<?= e($st[0]) ?>"><span class="status <?= e($st[1]) ?>"><?= e($st[0]) ?></span></td>
                        <td class="row-actions">
                            <select class="status-select" data-order-status data-id="<?= (int) $x['id_pedido'] ?>" data-current="<?= e($x['status']) ?>" data-status-col="6" aria-label="Alterar status do pedido <?= (int) $x['id_pedido'] ?>">
                                <?php foreach (STATUS_PEDIDO as $k => $v): ?>
                                    <option value="<?= e($k) ?>" <?= $k === $x['status'] ? 'selected' : '' ?>><?= e($v[0]) ?></option><?php endforeach; ?>
                            </select>
                            <button type="button" class="btn-small" data-order-detail="<?= (int) $x['id_pedido'] ?>">Detalhes</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr class="empty-row"><td class="empty" colspan="8">Nenhum pedido encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-backdrop" id="detail-modal" hidden>
    <div class="modal" role="dialog" aria-modal="true">
        <div class="modal-head">
            <h3 data-modal-title>Pedido</h3>
            <button type="button" class="modal-x" data-modal-close aria-label="Fechar">×</button>
        </div>
        <div id="detail-body"></div>
        <div class="form-actions"><button type="button" class="btn-secondary" data-modal-close>Fechar</button></div>
    </div>
</div>
<?php layout_foot();
