/* Carrega os dados de cada página pela API e desenha a tela.
   Usa só DOM seguro (textContent / append): nenhum dado do banco entra via innerHTML. */
(() => {
    "use strict";

    const L = () => window.lrc;
    const page = document.body.dataset.page;
    const qs = new URLSearchParams(location.search);

    /* criador de elementos: h("tag", {class, text, dataset, attr...}, ...filhos) */
    const h = (tag, props, ...kids) => {
        const n = document.createElement(tag);
        if (props) {
            for (const [k, v] of Object.entries(props)) {
                if (v == null || v === false) continue;
                if (k === "class") n.className = v;
                else if (k === "text") n.textContent = v;
                else if (k === "dataset") Object.assign(n.dataset, v);
                else n.setAttribute(k, v === true ? "" : v);
            }
        }
        n.append(...kids.flat().filter((x) => x != null && x !== false));
        return n;
    };
    const $ = (s, r = document) => r.querySelector(s);
    const hidden = (name, value) => h("input", { type: "hidden", name, value: String(value) });

    function setRows(table, trs, emptyMsg) {
        const body = table.tBodies[0];
        const empty = body.querySelector(".empty-row");
        [...body.rows].forEach((r) => { if (r !== empty) r.remove(); });
        trs.forEach((tr) => body.insertBefore(tr, empty));
        empty.querySelector("td").textContent = trs.length ? "Nada encontrado com esses filtros." : emptyMsg;
        table.dispatchEvent(new Event("lrc:refresh"));
    }

    function failTable(table, err) {
        setRows(table, [], "Não foi possível carregar os dados.");
        L().toast(err.message, "erro");
    }

    function setChip(name) {
        document.querySelectorAll("[data-chip]").forEach((c) => c.classList.toggle("on", c.dataset.chip === name));
    }

    const st = (key) => L().STATUS[key] || [key, "pending"];
    const statusBadge = (key) => h("span", { class: "status " + st(key)[1], text: st(key)[0] });

    /* ---------------- Início ---------------- */
    async function loadHome() {
        try {
            const j = await L().api("api/dashboard.php");
            $("#st-vendas").textContent = L().money(j.vendas);
            $("#st-pedidos").textContent = j.pedidos;
            $("#st-produtos").textContent = j.produtos;
            $("#st-clientes").textContent = j.clientes;

            const atual = j.serie.find((s) => s.mes === j.mes_atual);
            $("#chart-mes").textContent = L().money(atual ? atual.total : 0);
            const max = Math.max(1, ...j.serie.map((s) => s.total));
            $("#chart-bars").replaceChildren(...j.serie.map((s) => {
                const pct = Math.max(3, Math.round(s.total / max * 100));
                const c = h("div", { class: "bar-container" },
                    h("span", { class: "tip", text: L().money(s.total) }),
                    h("div", { class: "bar" + (s.mes === j.mes_atual ? " highlight" : "") }),
                    h("span", { text: s.label }));
                c.style.setProperty("--h", pct + "%");
                c.querySelector(".bar").style.height = pct + "%";
                return c;
            }));

            const list = $("#stock-list");
            if (!j.estoque.length) list.replaceChildren(h("div", { class: "empty", text: "Nenhum produto com estoque baixo." }));
            else list.replaceChildren(...j.estoque.map((x) => {
                const a = h("a", { class: "stock-item", href: "produtos.html?baixo=1", style: "text-decoration:none;color:inherit" },
                    h("div", { class: "product-placeholder", text: "📦" }),
                    h("div", { class: "stock-info" }, h("strong", { text: x.nome }), h("span", { text: x.codigo })),
                    h("strong", { class: "low-stock", text: x.estoque + " un." }));
                return a;
            }));

            const body = $("#recent-body");
            if (!j.recentes.length) body.replaceChildren(h("tr", null, h("td", { class: "empty", colspan: "5", text: "Nenhum pedido ainda." })));
            else body.replaceChildren(...j.recentes.map((x) => h("tr", null,
                h("td", null, h("a", { href: "pedidos.html" }, h("strong", { text: "#" + x.id_pedido }))),
                h("td", { text: x.cliente }),
                h("td", { text: x.data }),
                h("td", { text: L().money(x.total) }),
                h("td", null, statusBadge(x.status)))));
        } catch (err) {
            L().toast(err.message, "erro");
            $("#stock-list").replaceChildren(h("div", { class: "empty", text: "Não foi possível carregar os dados." }));
            $("#recent-body").replaceChildren(h("tr", null, h("td", { class: "empty", colspan: "5", text: "Não foi possível carregar os dados." })));
        }
    }

    /* ---------------- Produtos ---------------- */
    async function loadProducts() {
        const baixo = qs.has("baixo");
        setChip(baixo ? "baixo" : "todos");
        const table = $("#tbl-produtos");
        try {
            const j = await L().api("api/produtos.php" + (baixo ? "?baixo=1" : ""));

            /* categorias no filtro e no modal (preserva a seleção atual) */
            const fil = $("#filtro-categoria"), sel = $("#select-categoria");
            const fv = fil.value;
            fil.replaceChildren(h("option", { value: "", text: "Todas as categorias" }), ...j.categorias.map((c) => h("option", { text: c.nome })));
            fil.value = fv;
            sel.replaceChildren(h("option", { value: "", text: "Sem categoria" }), ...j.categorias.map((c) => h("option", { value: c.id_categoria, text: c.nome })));

            setRows(table, j.produtos.map((x) => {
                const item = { id: x.id_produto, codigo: x.codigo, nome: x.nome, descricao: x.descricao, preco: x.preco, estoque: x.estoque, cat: x.id_categoria };
                const del = h("form", {
                    class: "inline-form", "data-api": "api/produtos.php",
                    "data-confirm": `Excluir “${x.nome}”? Esta ação não pode ser desfeita.`, "data-confirm-title": "Excluir produto",
                }, hidden("op", "delete"), hidden("id", x.id_produto), h("button", { class: "btn-small danger", text: "Excluir" }));
                return h("tr", null,
                    h("td", { dataset: { value: x.nome } }, h("strong", { text: x.nome }), h("br"), h("span", { class: "muted", text: x.descricao || "" })),
                    h("td", { text: x.codigo }),
                    h("td", { dataset: { value: x.categoria || "" }, text: x.categoria || "—" }),
                    h("td", { dataset: { value: x.preco }, text: L().money(x.preco) }),
                    h("td", { class: x.estoque <= 5 ? "low-stock" : null, dataset: { value: x.estoque }, text: x.estoque }),
                    h("td", { class: "row-actions" },
                        h("button", { type: "button", class: "btn-small", text: "Editar", "data-form-modal": "product-modal", "data-item": JSON.stringify(item) }),
                        del));
            }), baixo ? "Nenhum produto com estoque baixo." : "Nenhum produto cadastrado.");
        } catch (err) { failTable(table, err); }
    }

    /* ---------------- Pedidos ---------------- */
    async function loadOrders() {
        const pend = qs.get("status") === "pendentes";
        setChip(pend ? "pendentes" : "todos");
        const table = $("#tbl-pedidos");
        const fil = $("#filtro-status");
        if (fil.options.length === 1)
            Object.values(L().STATUS).forEach((v) => fil.append(h("option", { text: v[0] })));
        try {
            const j = await L().api("api/pedidos.php" + (pend ? "?status=pendentes" : ""));
            setRows(table, j.pedidos.map((x) => {
                const select = h("select", {
                    class: "status-select", "aria-label": "Alterar status do pedido " + x.id_pedido,
                    "data-order-status": true, "data-id": x.id_pedido, "data-current": x.status, "data-status-col": "6",
                }, ...Object.entries(L().STATUS).map(([k, v]) => h("option", { value: k, text: v[0], selected: k === x.status })));
                return h("tr", null,
                    h("td", { dataset: { value: x.id_pedido } }, h("strong", { text: "#" + x.id_pedido })),
                    h("td", { text: x.cliente }),
                    h("td", { dataset: { value: x.ts }, text: x.data }),
                    h("td", { dataset: { value: x.qtd }, text: x.qtd + (x.qtd === 1 ? " item" : " itens") }),
                    h("td", { dataset: { value: x.total }, text: L().money(x.total) }),
                    h("td", { text: x.pagamento || "—" }),
                    h("td", { dataset: { value: st(x.status)[0] } }, statusBadge(x.status)),
                    h("td", { class: "row-actions" }, select,
                        h("button", { type: "button", class: "btn-small", text: "Detalhes", "data-order-detail": x.id_pedido })));
            }), "Nenhum pedido encontrado.");
        } catch (err) { failTable(table, err); }
    }

    /* ---------------- Clientes ---------------- */
    async function loadCustomers() {
        const table = $("#tbl-clientes");
        try {
            const j = await L().api("api/clientes.php");
            setRows(table, j.clientes.map((x) => {
                const on = x.ativo === 1;
                return h("tr", null,
                    h("td", null, h("strong", { text: x.nome })),
                    h("td", { text: x.email }),
                    h("td", { text: x.telefone || "—" }),
                    h("td", { dataset: { value: x.pedidos }, text: x.pedidos }),
                    h("td", { dataset: { value: x.gasto }, text: L().money(x.gasto) }),
                    h("td", { dataset: { value: on ? "Ativo" : "Inativo" } }, h("span", { class: "status " + (on ? "approved" : "inactive"), text: on ? "Ativo" : "Inativo" })),
                    h("td", { class: "row-actions" },
                        h("button", { type: "button", class: "btn-small", text: "Visualizar", "data-customer-detail": x.id }),
                        h("button", { type: "button", class: "btn-small", text: on ? "Desativar" : "Ativar", "data-toggle-customer": x.id, "data-ativo": x.ativo, "data-status-col": "5" })));
            }), "Nenhum cliente cadastrado.");
        } catch (err) { failTable(table, err); }
    }

    /* ---------------- Categorias ---------------- */
    function filterCategories() {
        const q = L().norm($("#cat-search").value.trim());
        let n = 0;
        document.querySelectorAll("#cat-grid .category-card").forEach((c) => {
            const ok = !q || L().norm(c.dataset.name).includes(q);
            c.hidden = !ok;
            if (ok) n++;
        });
        const empty = $("#cat-empty");
        empty.hidden = n !== 0;
        if (n === 0) empty.textContent = document.querySelectorAll("#cat-grid .category-card").length ? "Nenhuma categoria encontrada." : "Nenhuma categoria cadastrada.";
    }

    async function loadCategories() {
        try {
            const j = await L().api("api/categorias.php");
            $("#cat-grid").replaceChildren(...j.categorias.map((x) => {
                const del = h("form", {
                    class: "inline-form", "data-api": "api/categorias.php", "data-confirm-title": "Excluir categoria",
                    "data-confirm": `Excluir a categoria “${x.nome}”?` + (x.total ? " Ela possui produtos vinculados e a exclusão pode ser bloqueada." : ""),
                }, hidden("op", "delete"), hidden("id", x.id_categoria), h("button", { class: "btn-small danger", text: "Excluir" }));
                return h("div", { class: "category-card", dataset: { name: x.nome } },
                    h("h3", { text: x.nome }),
                    h("p", { text: x.total + " produto(s) cadastrado(s)" }),
                    h("div", { class: "category-actions" },
                        h("button", { type: "button", class: "btn-small", text: "Editar", "data-form-modal": "cat-modal", "data-item": JSON.stringify({ id: x.id_categoria, nome: x.nome }) }),
                        h("a", { class: "btn-small", href: "produtos.html", text: "Produtos" }),
                        del));
            }));
            filterCategories();
        } catch (err) {
            $("#cat-grid").replaceChildren();
            $("#cat-empty").hidden = false;
            $("#cat-empty").textContent = "Não foi possível carregar os dados.";
            L().toast(err.message, "erro");
        }
    }

    /* ---------------- Relatórios ---------------- */
    async function loadReports(mes) {
        const table = $("#tbl-cat");
        const sel = $("#rel-periodo");
        try {
            const j = await L().api("api/relatorios.php?mes=" + encodeURIComponent(mes || "todos"));
            sel.replaceChildren(h("option", { value: "todos", text: "Todo o período" }), ...j.meses.map((m) => h("option", { value: m.valor, text: m.label })));
            sel.value = j.sel;

            $("#rp-fat").textContent = L().money(j.faturamento);
            $("#rp-fat-sub").textContent = j.rotulo;
            $("#rp-ticket").textContent = L().money(j.ticket);
            $("#rp-ticket-sub").textContent = j.pedidos + " pedido(s) válidos";
            $("#rp-un").textContent = j.unidades;

            const maxFat = Math.max(1, ...j.categorias.map((c) => c.faturamento));
            setRows(table, j.categorias.map((x) => {
                const pc = j.faturamento > 0 ? Math.round(x.faturamento / j.faturamento * 100) : 0;
                const w = Math.max(0, Math.min(100, Math.round(x.faturamento / maxFat * 100)));
                const bar = h("div", { style: "background:#f0f0f0;border-radius:4px;height:8px" },
                    h("div", { style: `width:${w}%;background:#ee4d2d;height:8px;border-radius:4px` }));
                return h("tr", null,
                    h("td", { text: x.nome }),
                    h("td", { dataset: { value: x.unidades }, text: x.unidades }),
                    h("td", { dataset: { value: x.pedidos }, text: x.pedidos }),
                    h("td", { dataset: { value: x.faturamento }, text: L().money(x.faturamento) }),
                    h("td", { dataset: { value: pc }, style: "min-width:140px" }, bar, h("span", { class: "muted", text: pc + "%" })));
            }), "Sem dados no período.");
        } catch (err) { failTable(table, err); }
    }

    /* ---------------- Histórico ---------------- */
    async function loadHistory() {
        const table = $("#tbl-hist");
        try {
            const j = await L().api("api/historico.php");
            setRows(table, j.movimentacoes.map((x) => h("tr", { dataset: { tipo: x.tipo } },
                h("td", { dataset: { value: x.ts }, text: x.data }),
                h("td", { text: x.produto }),
                h("td", null, h("span", { class: "status " + (x.tipo === "entrada" ? "approved" : x.tipo === "saida" ? "cancelled" : "pending"), text: x.tipo.charAt(0).toUpperCase() + x.tipo.slice(1) })),
                h("td", { dataset: { value: x.quantidade }, text: (x.tipo === "entrada" ? "+" : x.tipo === "saida" ? "−" : "") + x.quantidade }),
                h("td", { text: x.usuario }),
                h("td", { text: x.observacao || "" }))), "Nenhuma movimentação.");
        } catch (err) { failTable(table, err); }
    }

    /* ---------------- Configurações ---------------- */
    async function loadSettings() {
        try {
            const j = await L().api("api/configuracoes.php");
            const f = $("#form-dados");
            ["nome", "sobrenome", "telefone", "email"].forEach((k) => { f.elements[k].value = j.user[k] || ""; });
        } catch (err) { L().toast(err.message, "erro"); }
    }

    /* ---------------- inicialização ---------------- */
    const loaders = {
        home: loadHome, products: loadProducts, orders: loadOrders, customers: loadCustomers,
        categories: loadCategories, reports: () => loadReports(qs.get("mes")), history: loadHistory, settings: loadSettings,
    };

    document.addEventListener("lrc:ready", () => {
        if (page === "categories") $("#cat-search").addEventListener("input", filterCategories);
        if (page === "reports") {
            $("#rel-periodo").addEventListener("change", (e) => {
                const v = e.target.value;
                history.replaceState(null, "", v === "todos" ? "relatorios.html" : "relatorios.html?mes=" + encodeURIComponent(v));
                loadReports(v);
            });
        }
        loaders[page]?.();
    });

    /* depois de salvar/excluir pelos formulários, recarrega a tela */
    document.addEventListener("lrc:saved", (e) => {
        const f = e.detail.form;
        if (page === "settings") {
            if (f.matches("[data-password-form]")) {
                f.reset();
                f.querySelectorAll("input").forEach((i) => i.dispatchEvent(new Event("input")));
            } else {
                L().refreshShell(); // nome do usuário no topo
            }
            return;
        }
        loaders[page]?.();
        if (page === "products" || page === "categories") L().refreshShell(); // alertas de estoque
    });
})();
