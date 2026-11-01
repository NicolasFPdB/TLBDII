<?php require_once __DIR__ . '/../includes/layout.php';
admin_only();
$p = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (($_POST['op'] ?? '') === 'save') {
            $id = (int) $_POST['id'];
            $n = trim($_POST['nome']);
            if ($n === '')
                throw new Exception('Informe o nome da categoria.');
            if ($id) {
                $s = $p->prepare('UPDATE categorias SET nome=? WHERE id_categoria=?');
                $s->execute([$n, $id]);
            } else {
                $s = $p->prepare('INSERT INTO categorias(nome)VALUES(?)');
                $s->execute([$n]);
            }
            flash('sucesso', 'Categoria salva.');
        } elseif (($_POST['op'] ?? '') === 'delete') {
            $s = $p->prepare('DELETE FROM categorias WHERE id_categoria=?');
            $s->execute([(int) $_POST['id']]);
            flash('sucesso', 'Categoria excluída.');
        }
    } catch (Throwable $e) {
        flash('erro', $e instanceof PDOException ? 'Não foi possível salvar/excluir. Verifique se o nome já existe ou se a categoria está em uso.' : $e->getMessage());
    }
    header('Location:categorias.html');
    exit;
}

$rows = $p->query('SELECT c.*,COUNT(pr.id_produto) total FROM categorias c LEFT JOIN produtos pr ON pr.id_categoria=c.id_categoria GROUP BY c.id_categoria ORDER BY c.nome')->fetchAll();
layout_head('Categorias', 'categories', 'Organize os produtos da LRC1970 por tipo de peça.', '<button type="button" class="primary-btn" data-form-modal="cat-modal">+ Nova categoria</button>'); ?>

<div class="toolbar">
    <input class="search-input" id="cat-search" placeholder="Buscar categoria…  (atalho: /)">
</div>

<div class="category-grid" id="cat-grid">
    <?php foreach ($rows as $x): ?>
        <div class="category-card" data-name="<?= e($x['nome']) ?>">
            <h3><?= e($x['nome']) ?></h3>
            <p><?= (int) $x['total'] ?> produto(s) cadastrado(s)</p>
            <div class="category-actions">
                <button type="button" class="btn-small" data-form-modal="cat-modal" data-item="<?= e(json_encode(['id' => $x['id_categoria'], 'nome' => $x['nome']], JSON_UNESCAPED_UNICODE)) ?>">Editar</button>
                <a class="btn-small" href="produtos.html">Produtos</a>
                <form class="inline-form" method="post" data-confirm="Excluir a categoria “<?= e($x['nome']) ?>”?<?= $x['total'] ? ' Ela possui produtos vinculados e a exclusão pode ser bloqueada.' : '' ?>" data-confirm-title="Excluir categoria">
                    <?= csrf_field() ?><input type="hidden" name="op" value="delete"><input type="hidden" name="id" value="<?= (int) $x['id_categoria'] ?>">
                    <button class="btn-small danger">Excluir</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<p class="empty" id="cat-empty" <?= $rows ? 'hidden' : '' ?>>Nenhuma categoria encontrada.</p>

<div class="modal-backdrop" id="cat-modal" hidden>
    <div class="modal small" role="dialog" aria-modal="true">
        <div class="modal-head">
            <h3 data-modal-title data-new="Nova categoria" data-edit="Editar categoria">Nova categoria</h3>
            <button type="button" class="modal-x" data-modal-close aria-label="Fechar">×</button>
        </div>
        <form method="post">
            <?= csrf_field() ?><input type="hidden" name="op" value="save"><input type="hidden" name="id" value="0">
            <div class="form-group"><label>Nome *</label><input name="nome" required maxlength="80"></div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" data-modal-close>Cancelar</button>
                <button class="primary-btn">Salvar</button>
            </div>
        </form>
    </div>
</div>
<script>
    (() => {
        const input = document.getElementById('cat-search'), cards = [...document.querySelectorAll('#cat-grid .category-card')], empty = document.getElementById('cat-empty');
        const norm = t => t.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
        input.addEventListener('input', () => {
            const q = norm(input.value.trim());
            let n = 0;
            cards.forEach(c => { const ok = !q || norm(c.dataset.name).includes(q); c.hidden = !ok; if (ok) n++; });
            empty.hidden = n !== 0;
        });
    })();
</script>
<?php layout_foot();
