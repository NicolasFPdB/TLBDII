<?php require_once __DIR__ . '/../includes/layout.php';
admin_only();
$p = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $op = $_POST['op'] ?? '';
        if ($op === 'dados') {
            $email = trim($_POST['email']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL))
                throw new Exception('Informe um e-mail válido.');
            $s = $p->prepare('UPDATE users SET nome=?,sobrenome=?,telefone=?,email=? WHERE id=?');
            $s->execute([trim($_POST['nome']), trim($_POST['sobrenome']), trim($_POST['telefone']), $email, $_SESSION['uid']]);
            flash('sucesso', 'Dados atualizados.');
        }
        if ($op === 'senha') {
            $s = $p->prepare('SELECT senha FROM users WHERE id=?');
            $s->execute([$_SESSION['uid']]);
            $hash = $s->fetchColumn();
            if (!password_verify($_POST['atual'], $hash))
                throw new Exception('Senha atual incorreta.');
            if (strlen($_POST['nova']) < 6 || $_POST['nova'] !== $_POST['confirmacao'])
                throw new Exception('Nova senha inválida ou diferente da confirmação.');
            $s = $p->prepare('UPDATE users SET senha=? WHERE id=?');
            $s->execute([password_hash($_POST['nova'], PASSWORD_DEFAULT), $_SESSION['uid']]);
            session_regenerate_id(true);
            flash('sucesso', 'Senha alterada.');
        }
    } catch (Throwable $e) {
        flash('erro', $e instanceof PDOException ? 'Não foi possível salvar. Esse e-mail já pode estar em uso.' : $e->getMessage());
    }
    header('Location:configuracoes.html');
    exit;
}
$u = me();
layout_head('Configurações', 'settings', 'Dados do administrador e sistema.'); ?>
<div class="settings-grid">
    <div class="panel">
        <h2>Dados administrativos</h2><br>
        <form method="post">
            <?= csrf_field() ?><input type="hidden" name="op" value="dados">
            <div class="form-grid">
                <div class="form-group"><label>Nome</label><input name="nome" required value="<?= e($u['nome']) ?>"></div>
                <div class="form-group"><label>Sobrenome</label><input name="sobrenome" required value="<?= e($u['sobrenome']) ?>"></div>
                <div class="form-group"><label>Telefone</label><input name="telefone" value="<?= e($u['telefone']) ?>"></div>
                <div class="form-group"><label>E-mail</label><input name="email" type="email" required value="<?= e($u['email']) ?>"></div>
            </div>
            <div class="form-actions"><button class="primary-btn">Salvar</button></div>
        </form>
    </div>
    <div class="panel">
        <h2>Alterar senha</h2><br>
        <form method="post" data-password-form>
            <?= csrf_field() ?><input type="hidden" name="op" value="senha">
            <div class="form-group"><label>Senha atual</label>
                <div class="pass-wrap"><input name="atual" type="password" required autocomplete="current-password"><button type="button" data-toggle-pass>Mostrar</button></div>
            </div><br>
            <div class="form-group"><label>Nova senha</label>
                <div class="pass-wrap"><input name="nova" type="password" required autocomplete="new-password"><button type="button" data-toggle-pass>Mostrar</button></div>
                <span class="hint" data-hint-new>Mínimo de 6 caracteres.</span>
            </div><br>
            <div class="form-group"><label>Confirmar</label>
                <div class="pass-wrap"><input name="confirmacao" type="password" required autocomplete="new-password"><button type="button" data-toggle-pass>Mostrar</button></div>
                <span class="hint" data-hint-confirm></span>
            </div>
            <div class="form-actions"><button class="primary-btn">Alterar senha</button></div>
        </form>
    </div>
</div>
<?php layout_foot();
