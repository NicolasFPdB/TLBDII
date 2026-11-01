<?php require_once __DIR__ . '/../includes/layout.php';
admin_only();
$p = db();

/* Validação estrita: rejeita em vez de "consertar" em silêncio. Preço fica como STRING decimal
   (nunca passa por float) e vai direto para a coluna DECIMAL(10,2). */
function parse_preco($v): string
{
    $v = is_string($v) ? str_replace(',', '.', trim($v)) : '';
    if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $v))
        throw new InvalidArgumentException('Preço inválido. Use um valor entre 0 e 99999999,99.');
    return $v;
}
function parse_estoque($v): int
{
    $i = filter_var($v, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1000000]]);
    if ($i === false)
        throw new InvalidArgumentException('Estoque inválido. Use um número inteiro entre 0 e 1.000.000.');
    return $i;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $op = $_POST['op'] ?? '';
        if ($op === 'save') {
            $id = (int) ($_POST['id'] ?? 0);
            $codigo = trim((string) ($_POST['codigo'] ?? ''));
            $nome = trim((string) ($_POST['nome'] ?? ''));
            if ($codigo === '' || $nome === '')
                throw new InvalidArgumentException('Código e nome são obrigatórios.');
            if (mb_strlen($codigo) > 40 || mb_strlen($nome) > 150)
                throw new InvalidArgumentException('Código (máx. 40) ou nome (máx. 150) muito longo.');
            $preco = parse_preco($_POST['preco'] ?? null);
            $estoque = parse_estoque($_POST['estoque'] ?? null);
            $cat = ($_POST['cat'] ?? '') === '' ? null : (int) $_POST['cat'];
            $d = [$codigo, $nome, trim((string) ($_POST['descricao'] ?? '')), $preco, $estoque, $cat];

            $p->beginTransaction();
            if ($id) {
                $old = $p->prepare('SELECT estoque FROM produtos WHERE id_produto=? FOR UPDATE');
                $old->execute([$id]);
                $before = $old->fetchColumn();
                if ($before === false)
                    throw new InvalidArgumentException('Produto não encontrado.');
                $before = (int) $before;

                /* Anti "lost update": o formulário leva o estoque que a tela mostrava (estoque_original).
                   Se uma venda mudou o estoque nesse meio-tempo, e o admin alterou o campo, recusa e pede para
                   reabrir — em vez de sobrescrever a venda e registrar uma "entrada" falsa. */
                $orig = parse_estoque($_POST['estoque_original'] ?? null);
                if ($estoque !== $orig && $before !== $orig)
                    throw new InvalidArgumentException("O estoque mudou enquanto você editava (agora é $before). Reabra o produto e tente de novo.");
                // se o admin NÃO mexeu no campo estoque, preserva o valor atual do banco
                if ($estoque === $orig)
                    $d[4] = $estoque = $before;

                $s = $p->prepare('UPDATE produtos SET codigo=?,nome=?,descricao=?,preco=?,estoque=?,id_categoria=? WHERE id_produto=?');
                $s->execute([...$d, $id]);
                $diff = $estoque - $before;
                if ($diff) {
                    $m = $p->prepare('INSERT INTO movimentacoes_estoque(id_produto,id_usuario,tipo,quantidade,observacao)VALUES(?,?,?,?,?)');
                    $m->execute([$id, $_SESSION['uid'], $diff > 0 ? 'entrada' : 'saida', abs($diff), 'Alteração de estoque pelo administrador.']);
                }
                flash('sucesso', 'Produto atualizado.');
            } else {
                $s = $p->prepare('INSERT INTO produtos(codigo,nome,descricao,preco,estoque,id_categoria)VALUES(?,?,?,?,?,?)');
                $s->execute($d);
                $id = (int) $p->lastInsertId();
                if ($estoque) {
                    $m = $p->prepare('INSERT INTO movimentacoes_estoque(id_produto,id_usuario,tipo,quantidade,observacao)VALUES(?,?,?,?,?)');
                    $m->execute([$id, $_SESSION['uid'], 'entrada', $estoque, 'Estoque inicial.']);
                }
                flash('sucesso', 'Produto cadastrado.');
            }
            $p->commit();
        } elseif ($op === 'delete') {
            $s = $p->prepare('DELETE FROM produtos WHERE id_produto=?');
            $s->execute([(int) ($_POST['id'] ?? 0)]);
            flash('sucesso', 'Produto excluído.');
        }
    } catch (Throwable $e) {
        if ($p->inTransaction())
            $p->rollBack();
        if ($e instanceof PDOException) {
            error_log('produtos: ' . $e->getMessage());
            $msg = $e->getCode() === '23000' ? 'Operação bloqueada: código já existe, categoria inválida ou o produto tem pedidos/histórico vinculados.' : 'Não foi possível concluir a operação.';
        } else {
            $msg = $e instanceof InvalidArgumentException ? $e->getMessage() : 'Erro inesperado.';
            if (!$e instanceof InvalidArgumentException)
                error_log('produtos: ' . $e->getMessage());
        }
        flash('erro', $msg);
    }
    header('Location:produtos.html');
    exit;
}

$baixo = isset($_GET['baixo']);
$sql = 'SELECT p.*,c.nome categoria FROM produtos p LEFT JOIN categorias c ON c.id_categoria=p.id_categoria' . ($baixo ? ' WHERE p.estoque<=5' : '') . ' ORDER BY p.nome';
$rows = $p->query($sql)->fetchAll();
$cats = $p->query('SELECT * FROM categorias ORDER BY nome')->fetchAll();

layout_head('Produtos', 'products', 'Cadastre, edite e acompanhe o estoque das peças.', '<button type="button" class="primary-btn" data-form-modal="product-modal">+ Novo produto</button>'); ?>

<div class="chips">
    <a class="chip<?= $baixo ? '' : ' on' ?>" href="produtos.html">Todos</a>
    <a class="chip<?= $baixo ? ' on' : '' ?>" href="produtos.html?baixo=1">Estoque baixo (≤ 5)</a>
</div>

<div class="toolbar">
    <input class="search-input" data-table-search="#tbl-produtos" placeholder="Buscar por nome, código ou descrição…  (atalho: /)">
    <select class="select-input" data-table-filter="#tbl-produtos" data-col="2">
        <option value="">Todas as categorias</option>
        <?php foreach ($cats as $c): ?>
            <option><?= e($c['nome']) ?></option><?php endforeach; ?>
    </select>
</div>
<div class="result-count" data-count-for="#tbl-produtos"></div>

<div class="panel">
    <div class="table-container">
        <table id="tbl-produtos" data-table>
            <thead>
                <tr>
                    <th data-sort="text">Produto</th>
                    <th data-sort="text">Código</th>
                    <th data-sort="text">Categoria</th>
                    <th data-sort="num">Preço</th>
                    <th data-sort="num">Estoque</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $x):
                    $item = ['id' => $x['id_produto'], 'codigo' => $x['codigo'], 'nome' => $x['nome'], 'descricao' => $x['descricao'], 'preco' => $x['preco'], 'estoque' => $x['estoque'], 'estoque_original' => $x['estoque'], 'cat' => $x['id_categoria']]; ?>
                    <tr>
                        <td data-value="<?= e($x['nome']) ?>"><strong><?= e($x['nome']) ?></strong><br><span class="muted"><?= e($x['descricao']) ?></span></td>
                        <td><?= e($x['codigo']) ?></td>
                        <td data-value="<?= e($x['categoria'] ?? '') ?>"><?= e($x['categoria'] ?? '—') ?></td>
                        <td data-value="<?= e($x['preco']) ?>"><?= brl($x['preco']) ?></td>
                        <td data-value="<?= (int) $x['estoque'] ?>" class="<?= $x['estoque'] <= 5 ? 'low-stock' : '' ?>"><?= (int) $x['estoque'] ?></td>
                        <td class="row-actions">
                            <button type="button" class="btn-small" data-form-modal="product-modal" data-item="<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>">Editar</button>
                            <form class="inline-form" method="post" data-confirm="Excluir “<?= e($x['nome']) ?>”? Esta ação não pode ser desfeita." data-confirm-title="Excluir produto">
                                <?= csrf_field() ?><input type="hidden" name="op" value="delete"><input type="hidden" name="id" value="<?= (int) $x['id_produto'] ?>">
                                <button class="btn-small danger">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr class="empty-row"><td class="empty" colspan="6">Nenhum produto cadastrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-backdrop" id="product-modal" hidden>
    <div class="modal" role="dialog" aria-modal="true">
        <div class="modal-head">
            <h3 data-modal-title data-new="Novo produto" data-edit="Editar produto">Novo produto</h3>
            <button type="button" class="modal-x" data-modal-close aria-label="Fechar">×</button>
        </div>
        <form method="post">
            <?= csrf_field() ?><input type="hidden" name="op" value="save"><input type="hidden" name="id" value="0"><input type="hidden" name="estoque_original" value="0">
            <div class="form-grid">
                <div class="form-group"><label>Código *</label><input name="codigo" required maxlength="40"></div>
                <div class="form-group"><label>Nome *</label><input name="nome" required maxlength="150"></div>
                <div class="form-group"><label>Preço (R$) *</label><input name="preco" type="number" step="0.01" min="0" required></div>
                <div class="form-group"><label>Estoque *</label><input name="estoque" type="number" min="0" required value="0"></div>
                <div class="form-group full"><label>Categoria</label>
                    <select name="cat">
                        <option value="">Sem categoria</option>
                        <?php foreach ($cats as $c): ?>
                            <option value="<?= (int) $c['id_categoria'] ?>"><?= e($c['nome']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group full"><label>Descrição</label><textarea name="descricao"></textarea></div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" data-modal-close>Cancelar</button>
                <button class="primary-btn">Salvar</button>
            </div>
        </form>
    </div>
</div>
<?php layout_foot();
