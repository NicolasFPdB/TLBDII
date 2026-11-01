/* Layout compartilhado do painel (substitui layout_head()/layout_foot() do layout.php).
   Cada página .html declara <body data-page data-title data-subtitle>, o conteúdo em
   <template id="page-content"> e (opcional) os botões do cabeçalho em <template id="page-actions">. */
(() => {
    "use strict";

    const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

    const NAV = [
        ["home", "⌂", "Início", "index.html"],
        ["products", "▣", "Produtos", "produtos.html"],
        ["orders", "🛒", "Pedidos", "pedidos.html"],
        ["customers", "♙", "Clientes", "clientes.html"],
        ["categories", "▤", "Categorias", "categorias.html"],
        ["reports", "▥", "Relatórios", "relatorios.html"],
        ["history", "◷", "Histórico", "historico.html"],
        ["settings", "⚙", "Configurações", "configuracoes.html"],
    ];

    const body = document.body;
    const page = body.dataset.page || "";
    const title = body.dataset.title || "";
    const subtitle = body.dataset.subtitle || "";

    const state = { session: null };

    async function getSession() {
        const r = await fetch("api/session.php", { headers: { "X-Requested-With": "fetch" }, credentials: "same-origin", cache: "no-store" });
        if (r.status === 401) {
            location.replace("login.html");
            return null;
        }
        let j = null;
        try { j = await r.json(); } catch (_) { /* resposta não-JSON */ }
        if (!r.ok || !j || j.ok === false) throw new Error((j && j.msg) || "Não foi possível carregar a sessão do painel.");
        return j;
    }

    function topbarActionsHtml(s) {
        const pend = s.pendentes | 0;
        const total = pend + (s.estoque_baixo_total | 0);
        const nome = s.user.nome || "Administrador";
        const inicial = esc(nome.charAt(0).toUpperCase() || "A");
        const low = s.estoque_baixo || [];
        let notif = "";
        if (pend) notif += `<a href="pedidos.html?status=pendentes" class="dropdown-item">🛒 ${pend} pedido(s) aguardando ação</a>`;
        low.forEach((l) => { notif += `<a href="produtos.html?baixo=1" class="dropdown-item">📦 ${esc(l.nome)} — <b>${l.estoque | 0} un.</b></a>`; });
        if (!pend && !low.length) notif = '<p class="dropdown-empty">Tudo em dia. Nenhuma pendência.</p>';
        return `
            <div class="dropdown" data-dropdown>
                <button class="icon-btn bell" type="button" aria-label="Notificações" data-dropdown-toggle>🔔${total ? `<span class="bell-dot">${total}</span>` : ""}</button>
                <div class="dropdown-menu" hidden>
                    <p class="dropdown-title">Notificações</p>
                    ${notif}
                </div>
            </div>
            <div class="dropdown" data-dropdown>
                <button class="admin-user as-btn" type="button" data-dropdown-toggle>
                    <div class="avatar">${inicial}</div>
                    <div><strong>${esc(nome)}</strong><span>Administrador</span></div>
                </button>
                <div class="dropdown-menu" hidden>
                    <a class="dropdown-item" href="configuracoes.html">⚙ Configurações</a>
                    <a class="dropdown-item" href="login.html" data-logout>↪ Sair</a>
                </div>
            </div>`;
    }

    function navHtml(s) {
        const pend = s.pendentes | 0;
        const items = NAV.map(([k, ico, label, href]) =>
            `<a class="menu-item${page === k ? " active" : ""}" href="${href}"><span>${ico}</span>${label}${k === "orders" && pend ? `<span class="notification">${pend}</span>` : ""}</a>`).join("");
        return `<p class="menu-title">MENU PRINCIPAL</p>${items}<p class="menu-title">CONTA</p><a class="menu-item logout" href="login.html" data-logout><span>↪</span>Sair</a>`;
    }

    function renderChrome(s) {
        const ta = document.querySelector(".topbar-actions");
        const nav = document.querySelector("#sidebar nav");
        if (ta) ta.innerHTML = topbarActionsHtml(s);
        if (nav) nav.innerHTML = navHtml(s);
    }

    function buildShell(s) {
        const content = document.getElementById("page-content");
        const actions = document.getElementById("page-actions");

        document.title = "LRC1970 - " + title;
        let meta = document.querySelector('meta[name="csrf"]');
        if (!meta) {
            meta = document.createElement("meta");
            meta.name = "csrf";
            document.head.appendChild(meta);
        }
        meta.content = s.csrf;

        const tpl = document.createElement("template");
        tpl.innerHTML = `
            <header class="topbar">
                <div class="topbar-left">
                    <button class="icon-btn menu-toggle" type="button" aria-label="Abrir menu" data-menu-toggle>☰</button>
                    <a class="logo" href="index.html">LRC<span>1970</span><small>AUTOPARTS</small></a>
                </div>
                <div class="topbar-actions"></div>
            </header>
            <div class="admin-container">
                <div class="sidebar-backdrop" data-menu-toggle></div>
                <aside class="sidebar" id="sidebar"><nav></nav></aside>
                <main class="main-content">
                    <div class="page-header">
                        <div><h1></h1><p></p></div>
                    </div>
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
            </div>`;
        const frag = tpl.content;
        frag.querySelector(".page-header h1").textContent = title;
        frag.querySelector(".page-header p").textContent = subtitle;
        if (actions && actions.content.childNodes.length) {
            const box = document.createElement("div");
            box.className = "page-actions";
            box.appendChild(actions.content.cloneNode(true));
            frag.querySelector(".page-header").appendChild(box);
        }
        if (content) frag.querySelector("main.main-content").appendChild(content.content.cloneNode(true));
        body.prepend(frag);
        renderChrome(s);
    }

    async function refreshShell() {
        try {
            const s = await getSession();
            if (!s) return;
            state.session = s;
            const meta = document.querySelector('meta[name="csrf"]');
            if (meta) meta.content = s.csrf;
            renderChrome(s);
        } catch (_) { /* mantém o que já está na tela */ }
    }

    document.addEventListener("click", async (e) => {
        const out = e.target.closest("[data-logout]");
        if (!out) return;
        e.preventDefault();
        try {
            await fetch("api/logout.php", {
                method: "POST",
                headers: { "X-Requested-With": "fetch", "X-CSRF-Token": document.querySelector('meta[name="csrf"]')?.content || "" },
                credentials: "same-origin",
            });
        } catch (_) { /* sai mesmo assim */ }
        location.replace("login.html");
    });

    window.lrc = Object.assign(window.lrc || {}, { refreshShell, esc, get session() { return state.session; } });

    async function boot() {
        /* espera todos os scripts com defer registrarem seus ouvintes */
        if (document.readyState === "loading")
            await new Promise((res) => document.addEventListener("DOMContentLoaded", res, { once: true }));
        try {
            const s = await getSession();
            if (!s) return;
            state.session = s;
            buildShell(s);
            document.dispatchEvent(new CustomEvent("lrc:ready"));
        } catch (err) {
            const p = document.createElement("p");
            p.style.cssText = "padding:32px;text-align:center";
            p.textContent = err.message + " Verifique se o servidor PHP/MySQL está ativo e recarregue a página.";
            body.prepend(p);
        }
    }
    boot();
})();
