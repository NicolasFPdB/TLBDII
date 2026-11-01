<?php require_once __DIR__ . '/../includes/layout.php';
admin_only();
$p = db();

/* ativar/desativar (AJAX) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['op'] ?? '') === 'toggle') {
    $id = (int) $_POST['id'];
    $s = $p->prepare("UPDATE users SET ativo=1-ativo WHERE id=? AND tipo='cliente'");
    $s->execute([$id]);
    $a = $p->prepare('SELECT ativo FROM users WHERE id=?');
    $a->execute([$id]);
    json_out(['ok' => true, 'ativo' => (int) $a->fetchColumn()]);
}

/* detalhes (AJAX) */
if (isset($_GET['detalhe'])) {
    $id = (int) $_GET['detalhe'];
    $s = $p->prepare("SELECT id,CONCAT(nome,' ',sobrenome) nome,email,telefone,criado_em FROM users WHERE id=? AND tipo='cliente'");
    $s->execute([$id]);
    $c = $s->fetch();
    if (!$c)
        json_out(['ok' => false, 'msg' => 'Cliente não encontrado.'], 404);
    $c['criado_em'] = $c['criado_em'] ? date('d/m/Y', strtotime($c['criado_em'])) : null;
    $o = $p->prepare('SELECT o.id_pedido,o.data_pedido,o.status,COALESCE(SUM(i.quantidade*i.preco_unitario),0) total FROM pedidos o LEFT JOIN itens_pedido i ON i.id_pedido=o.id_pedido WHERE o.id_usuario=? GROUP BY o.id_pedido ORDER BY o.data_pedido DESC LIMIT 20');
    $o->execute([$id]);
    $pedidos = $o->fetchAll();
    $total = 0;
    foreach ($pedidos as &$x) {
        if (!in_array($x['status'], ['cancelado', 'aguardando_pagamento'], true))
            $total += $x['total'];
        $x['data'] = date('d/m/Y', strtotime($x['data_pedido']));
    }
    json_out(['ok' => true, 'cliente' => $c, 'pedidos' => $pedidos, 'total' => $total]);
}

$rows = $p->query("SELECT u.id,CONCAT(u.nome,' ',u.sobrenome) nome,u.email,u.telefone,u.ativo,
        COUNT(DISTINCT o.id_pedido) pedidos,
        COALESCE(SUM(CASE WHEN o.status NOT IN('cancelado','aguardando_pagamento') THEN i.quantidade*i.preco_unitario END),0) gasto
    FROM users u LEFT JOIN pedidos o ON o.id_usuario=u.id LEFT JOIN itens_pedido i ON i.id_pedido=o.id_pedido
    WHERE u.tipo='cliente' GROUP BY u.id ORDER BY u.nome")->fetchAll();

layout_head('Clientes', 'customers', 'Gerencie os usuários cadastrados na loja.',
    '<button type="button" class="btn-secondary" data-export-csv="#tbl-clientes" data-filename="clientes">⬇ Exportar CSV</button>'); ?>

<div class="toolbar">
    <input class="search-input" data-table-search="#tbl-clientes" placeholder="Buscar cliente por nome, e-mail ou telefone…  (atalho: /)">
    <select class="select-input" data-table-filter="#tbl-clientes" data-col="5">
        <option value="">Todos os clientes</option>
        <option>Ativo</option>
        <option>Inativo</option>
    </select>
</div>
<div class="result-count" data-count-for="#tbl-clientes"></div>

<div class="panel">
    <div class="table-container">
        <table id="tbl-clientes" data-table>
            <thead>
                <tr>
                    <th data-sort="text">Cliente</th>
                    <th data-sort="text">E-mail</th>
                    <th>Telefone</th>
                    <th data-sort="num">Pedidos</th>
                    <th data-sort="num">Total gasto</th>
                    <th data-sort="text">Status</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $x): ?>
                    <tr>
                        <td><strong><?= e($x['nome']) ?></strong></td>
                        <td><?= e($x['email']) ?></td>
                        <td><?= e($x['telefone'] ?? '—') ?></td>
                        <td data-value="<?= (int) $x['pedidos'] ?>"><?= (int) $x['pedidos'] ?></td>
                        <td data-value="<?= e($x['gasto']) ?>"><?= brl($x['gasto']) ?></td>
                        <td data-value="<?= $x['ativo'] ? 'Ativo' : 'Inativo' ?>"><span class="status <?= $x['ativo'] ? 'approved' : 'inactive' ?>"><?= $x['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
                        <td class="row-actions">
                            <button type="button" class="btn-small" data-customer-detail="<?= (int) $x['id'] ?>">Visualizar</button>
                            <button type="button" class="btn-small" data-toggle-customer="<?= (int) $x['id'] ?>" data-ativo="<?= (int) $x['ativo'] ?>" data-status-col="5"><?= $x['ativo'] ? 'Desativar' : 'Ativar' ?></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr class="empty-row"><td class="empty" colspan="7">Nenhum cliente cadastrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-backdrop" id="detail-modal" hidden>
    <div class="modal" role="dialog" aria-modal="true">
        <div class="modal-head">
            <h3 data-modal-title>Cliente</h3>
            <button type="button" class="modal-x" data-modal-close aria-label="Fechar">×</button>
        </div>
        <div id="detail-body"></div>
        <div class="form-actions"><button type="button" class="btn-secondary" data-modal-close>Fechar</button></div>
    </div>
</div>
<?php layout_foot();
