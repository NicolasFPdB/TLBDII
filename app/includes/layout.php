<?php require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/security_headers.php';

function layout_head(string $title, string $active, string $subtitle = '', string $actions = ''): void
{
    $u = me();
    $db = db();
    $pend = (int) $db->query("SELECT COUNT(*) FROM pedidos WHERE status IN('aguardando_pagamento','processando')")->fetchColumn();
    $low = $db->query('SELECT nome,estoque FROM produtos WHERE estoque<=5 ORDER BY estoque,nome LIMIT 5')->fetchAll();
    $lowTotal = (int) $db->query('SELECT COUNT(*) FROM produtos WHERE estoque<=5')->fetchColumn();
    $items = [
        'home' => ['⌂', 'Início', 'index.html'],
        'products' => ['▣', 'Produtos', 'produtos.html'],
        'orders' => ['🛒', 'Pedidos', 'pedidos.html'],
        'customers' => ['♙', 'Clientes', 'clientes.html'],
        'categories' => ['▤', 'Categorias', 'categorias.html'],
        'reports' => ['▥', 'Relatórios', 'relatorios.html'],
        'history' => ['◷', 'Histórico', 'historico.html'],
        'settings' => ['⚙', 'Configurações', 'configuracoes.html'],
    ];
    $inicial = e(strtoupper(substr($u['nome'] ?? 'A', 0, 1)));
    $nome = e($u['nome'] ?? 'Administrador');
    $totalAlertas = $pend + $lowTotal;
    ?>
    <!doctype html>
    <html lang="pt-BR">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="csrf" content="<?= e(csrf_token()) ?>">
        <title>LRC1970 - <?= e($title) ?></title>
        <link rel="stylesheet" href="../public/css/admin.css">
        <link rel="stylesheet" href="../public/css/admin-extra.css">
    </head>

    <body>
        <header class="topbar">
            <div class="topbar-left">
                <button class="icon-btn menu-toggle" type="button" aria-label="Abrir menu" data-menu-toggle>☰</button>
                <a class="logo" href="index.html">LRC<span>1970</span><small>AUTOPARTS</small></a>
            </div>
            <div class="topbar-actions">
                <div class="dropdown" data-dropdown>
                    <button class="icon-btn bell" type="button" aria-label="Notificações" data-dropdown-toggle>🔔<?php if ($totalAlertas): ?><span
                                class="bell-dot"><?= $totalAlertas ?></span><?php endif; ?></button>
                    <div class="dropdown-menu" hidden>
                        <p class="dropdown-title">Notificações</p>
                        <?php if ($pend): ?>
                            <a href="pedidos.html?status=pendentes" class="dropdown-item">🛒 <?= $pend ?> pedido(s) aguardando ação</a>
                        <?php endif; ?>
                        <?php foreach ($low as $l): ?>
                            <a href="produtos.html?baixo=1" class="dropdown-item">📦 <?= e($l['nome']) ?> — <b><?= (int) $l['estoque'] ?>
                                    un.</b></a>
                        <?php endforeach; ?>
                        <?php if (!$pend && !$low): ?>
                            <p class="dropdown-empty">Tudo em dia. Nenhuma pendência.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="dropdown" data-dropdown>
                    <button class="admin-user as-btn" type="button" data-dropdown-toggle>
                        <div class="avatar"><?= $inicial ?></div>
                        <div><strong><?= $nome ?></strong><span>Administrador</span></div>
                    </button>
                    <div class="dropdown-menu" hidden>
                        <a class="dropdown-item" href="configuracoes.html">⚙ Configurações</a>
                        <a class="dropdown-item" href="logout.html">↪ Sair</a>
                    </div>
                </div>
            </div>
        </header>
        <div class="admin-container">
            <div class="sidebar-backdrop" data-menu-toggle></div>
            <aside class="sidebar" id="sidebar">
                <nav>
                    <p class="menu-title">MENU PRINCIPAL</p>
                    <?php foreach ($items as $k => $i): ?>
                        <a class="menu-item<?= $active === $k ? ' active' : '' ?>" href="<?= $i[2] ?>"><span><?= $i[0] ?></span><?= $i[1] ?>
                            <?php if ($k === 'orders' && $pend): ?><span class="notification"><?= $pend ?></span><?php endif; ?></a>
                    <?php endforeach; ?>
                    <p class="menu-title">CONTA</p>
                    <a class="menu-item logout" href="logout.html"><span>↪</span>Sair</a>
                </nav>
            </aside>
            <main class="main-content">
                <div class="page-header">
                    <div>
                        <h1><?= e($title) ?></h1>
                        <p><?= e($subtitle) ?></p>
                    </div>
                    <?php if ($actions) echo '<div class="page-actions">' . $actions . '</div>'; ?>
                </div>
                <?php show_flash();
}

function layout_foot(): void
{
    ?>
            </main>
        </div>
        <div id="toasts" class="toasts" aria-live="polite"></div>
        <div class="modal-backdrop" id="confirm-modal" hidden>
            <div class="modal small" role="dialog" aria-modal="true">
                <h3 id="confirm-title">Confirmar</h3>
                <p id="confirm-text"></p>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" data-modal-close>Cancelar</button>
                    <button type="button" class="danger-btn" id="confirm-ok">Confirmar</button>
                </div>
            </div>
        </div>
        <script src="../public/js/admin.js"></script>
    </body>

    </html>
    <?php
}
