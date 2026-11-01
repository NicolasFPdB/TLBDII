<?php require_once __DIR__ . '/../includes/layout.php';
admin_only();
$p = db();
$rows = $p->query("SELECT m.*,pr.nome produto,CONCAT(COALESCE(u.nome,''),' ',COALESCE(u.sobrenome,'')) usuario FROM movimentacoes_estoque m JOIN produtos pr ON pr.id_produto=m.id_produto LEFT JOIN users u ON u.id=m.id_usuario ORDER BY m.data_movimentacao DESC LIMIT 200")->fetchAll();
layout_head('Histórico', 'history', 'Movimentações de estoque registradas.',
    '<button type="button" class="btn-secondary" data-export-csv="#tbl-hist" data-filename="historico-estoque">⬇ Exportar CSV</button>'); ?>

<div class="chips">
    <button type="button" class="chip on" data-history-filter="todos">Todas</button>
    <button type="button" class="chip" data-history-filter="entrada">Entradas</button>
    <button type="button" class="chip" data-history-filter="saida">Saídas</button>
</div>
<div class="toolbar">
    <input class="search-input" data-table-search="#tbl-hist" placeholder="Buscar por produto, usuário ou observação…  (atalho: /)">
</div>
<div class="result-count" data-count-for="#tbl-hist"></div>

<div class="panel">
    <div class="table-container">
        <table id="tbl-hist" data-table>
            <thead>
                <tr>
                    <th data-sort="num">Data</th>
                    <th data-sort="text">Produto</th>
                    <th data-sort="text">Tipo</th>
                    <th data-sort="num">Quantidade</th>
                    <th data-sort="text">Usuário</th>
                    <th>Observação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $x): ?>
                    <tr data-tipo="<?= e($x['tipo']) ?>">
                        <td data-value="<?= strtotime($x['data_movimentacao']) ?>"><?= date('d/m/Y H:i', strtotime($x['data_movimentacao'])) ?></td>
                        <td><?= e($x['produto']) ?></td>
                        <td><span class="status <?= $x['tipo'] === 'entrada' ? 'approved' : 'cancelled' ?>"><?= e(ucfirst($x['tipo'])) ?></span></td>
                        <td data-value="<?= (int) $x['quantidade'] ?>"><?= $x['tipo'] === 'entrada' ? '+' : '−' ?><?= (int) $x['quantidade'] ?></td>
                        <td><?= e(trim($x['usuario']) ?: 'Sistema') ?></td>
                        <td><?= e($x['observacao']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr class="empty-row"><td colspan="6" class="empty">Nenhuma movimentação.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php layout_foot();
