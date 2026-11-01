(() => {
    "use strict";

    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => [...r.querySelectorAll(s)];
    const csrf = () => $('meta[name="csrf"]')?.content || "";
    const norm = (t) => (t || "").toString().toLowerCase().normalize("NFD").replace(/[̀-ͯ]/g, "");

    /* ---------- toasts ---------- */
    function toast(msg, type = "sucesso") {
        const box = $("#toasts");
        if (!box) return;
        const t = document.createElement("div");
        t.className = "toast " + (type === "erro" ? "erro" : "");
        t.textContent = msg;
        box.appendChild(t);
        setTimeout(() => {
            t.classList.add("out");
            setTimeout(() => t.remove(), 300);
        }, 3800);
    }
    window.lrcToast = toast;

    /* ---------- chamadas ao servidor (api/*.php) ---------- */
    async function api(url, data) {
        const opt = { headers: { "X-Requested-With": "fetch", "X-CSRF-Token": csrf() }, credentials: "same-origin", cache: "no-store" };
        if (data) {
            const fd = data instanceof FormData ? data : new URLSearchParams(data);
            opt.method = "POST";
            opt.body = fd;
        }
        const r = await fetch(url, opt);
        if (r.status === 401) {
            location.replace("login.html");
            throw new Error("Sessão expirada. Entre novamente.");
        }
        let j = {};
        try { j = await r.json(); } catch (_) { /* resposta não-JSON */ }
        if (!r.ok || j.ok === false) throw new Error(j.msg || "Não foi possível concluir a ação.");
        return j;
    }

    /* ---------- modais ---------- */
    function openModal(m) { m.hidden = false; document.body.style.overflow = "hidden"; m.querySelector("input:not([type=hidden]),select,textarea,button")?.focus(); }
    function closeModal(m) { m.hidden = true; document.body.style.overflow = ""; }
    document.addEventListener("click", (e) => {
        const close = e.target.closest("[data-modal-close]");
        if (close) { closeModal(close.closest(".modal-backdrop")); return; }
        if (e.target.classList?.contains("modal-backdrop")) closeModal(e.target);
    });
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") $$(".modal-backdrop:not([hidden])").forEach(closeModal);
        if (e.key === "/" && !/input|textarea|select/i.test(document.activeElement.tagName)) {
            const s = $(".search-input");
            if (s) { e.preventDefault(); s.focus(); }
        }
    });

    /* confirmação reutilizável (substitui confirm()) */
    function confirmAction(title, text, okLabel = "Confirmar") {
        return new Promise((resolve) => {
            const m = $("#confirm-modal");
            $("#confirm-title").textContent = title;
            $("#confirm-text").textContent = text;
            const ok = $("#confirm-ok");
            ok.textContent = okLabel;
            const done = (v) => { closeModal(m); ok.onclick = null; m.removeEventListener("click", cancel); resolve(v); };
            const cancel = (e) => { if (e.target.closest("[data-modal-close]") || e.target === m) done(false); };
            m.addEventListener("click", cancel);
            ok.onclick = () => done(true);
            openModal(m);
        });
    }

    /* ---------- formulários enviados por fetch (form[data-api]) ----------
       Substitui o POST + redirect + flash do PHP: envia, mostra toast e avisa a página (lrc:saved). */
    document.addEventListener("submit", async (e) => {
        const f = e.target.closest("form[data-api]");
        if (!f || e.defaultPrevented) return; // e.defaultPrevented: validação da página (ex.: senha) já barrou
        e.preventDefault();
        if (f.dataset.confirm && !(await confirmAction(f.dataset.confirmTitle || "Confirmar ação", f.dataset.confirm, f.dataset.confirmOk || "Excluir")))
            return;
        const btn = e.submitter || f.querySelector("button:not([type=button])");
        if (btn) btn.disabled = true; // evita envio duplo
        try {
            const j = await api(f.dataset.api, new FormData(f));
            toast(j.msg || "Salvo.");
            const modal = f.closest(".modal-backdrop");
            if (modal) closeModal(modal);
            document.dispatchEvent(new CustomEvent("lrc:saved", { detail: { form: f, res: j } }));
        } catch (err) {
            toast(err.message, "erro");
        } finally {
            if (btn) btn.disabled = false;
        }
    });

    /* modal de formulário (novo / editar) preenchido por data-item */
    document.addEventListener("click", (e) => {
        const b = e.target.closest("[data-form-modal]");
        if (!b) return;
        const m = document.getElementById(b.dataset.formModal);
        const form = m.querySelector("form");
        form.reset();
        const item = b.dataset.item ? JSON.parse(b.dataset.item) : null;
        $$("[name]", form).forEach((el) => {
            if (el.name === "csrf" || el.name === "op") return;
            if (el.name === "id") { el.value = item ? item.id : 0; return; }
            el.value = item && item[el.name] != null ? item[el.name] : (el.defaultValue ?? "");
        });
        const t = $("[data-modal-title]", m);
        if (t) t.textContent = item ? t.dataset.edit : t.dataset.new;
        openModal(m);
    });

    /* ---------- menu, dropdowns ---------- */
    document.addEventListener("click", (e) => {
        if (e.target.closest("[data-menu-toggle]")) document.body.classList.toggle("menu-open");
        const tog = e.target.closest("[data-dropdown-toggle]");
        $$("[data-dropdown]").forEach((d) => {
            const menu = $(".dropdown-menu", d);
            if (tog && d.contains(tog)) menu.hidden = !menu.hidden;
            else menu.hidden = true;
        });
    });

    /* ---------- tabelas: busca, filtros, ordenação ---------- */
    function setupTable(table) {
        const body = table.tBodies[0];
        const rows = () => [...body.rows].filter((r) => !r.classList.contains("empty-row"));
        const search = $(`[data-table-search="#${table.id}"]`);
        const filters = $$(`[data-table-filter="#${table.id}"]`);
        const counter = $(`[data-count-for="#${table.id}"]`);

        let empty = body.querySelector(".empty-row");
        if (!empty) {
            empty = document.createElement("tr");
            empty.className = "empty-row";
            empty.hidden = true;
            empty.innerHTML = `<td class="empty" colspan="${table.tHead.rows[0].cells.length}">Nada encontrado com esses filtros.</td>`;
            body.appendChild(empty);
        }

        function apply() {
            const q = norm(search?.value.trim());
            let shown = 0;
            rows().forEach((r) => {
                let ok = !q || norm(r.textContent).includes(q);
                const tipo = $("[data-history-filter].on")?.dataset.historyFilter;
                if (ok && r.dataset.tipo && tipo && tipo !== "todos" && r.dataset.tipo !== tipo) ok = false;
                filters.forEach((f) => {
                    if (!ok || !f.value) return;
                    const cell = r.cells[+f.dataset.col];
                    const v = cell?.dataset.value ?? cell?.textContent;
                    ok = norm(v).trim() === norm(f.value);
                });
                r.hidden = !ok;
                if (ok) shown++;
            });
            empty.hidden = shown !== 0;
            if (counter) counter.textContent = shown + (shown === 1 ? " resultado" : " resultados");
        }
        search?.addEventListener("input", apply);
        filters.forEach((f) => f.addEventListener("change", apply));
        /* as páginas disparam "lrc:refresh" na tabela depois de carregar/recarregar as linhas */
        table.addEventListener("lrc:refresh", apply);

        $$("th[data-sort]", table).forEach((th, _i, all) => {
            th.addEventListener("click", () => {
                const idx = th.cellIndex;
                const dir = th.classList.contains("asc") ? "desc" : "asc";
                all.forEach((o) => o.classList.remove("asc", "desc"));
                th.classList.add(dir);
                const type = th.dataset.sort;
                const val = (r) => {
                    const c = r.cells[idx];
                    const v = c.dataset.value ?? c.textContent.trim();
                    return type === "num" ? parseFloat(v) || 0 : norm(v);
                };
                const sorted = rows().sort((a, b) => {
                    const x = val(a), y = val(b);
                    return (x > y ? 1 : x < y ? -1 : 0) * (dir === "asc" ? 1 : -1);
                });
                sorted.forEach((r) => body.insertBefore(r, empty));
            });
        });
        apply();
    }

    /* ---------- exportar CSV ---------- */
    document.addEventListener("click", (e) => {
        const b = e.target.closest("[data-export-csv]");
        if (!b) return;
        const table = $(b.dataset.exportCsv);
        const lines = [...table.rows].filter((r) => !r.hidden).map((r) =>
            [...r.cells].map((c) => '"' + c.textContent.trim().replace(/\s+/g, " ").replace(/"/g, '""') + '"').join(";"));
        const blob = new Blob(["﻿" + lines.join("\n")], { type: "text/csv;charset=utf-8" });
        const a = document.createElement("a");
        a.href = URL.createObjectURL(blob);
        a.download = (b.dataset.filename || "relatorio") + ".csv";
        a.click();
        URL.revokeObjectURL(a.href);
        toast("Arquivo CSV gerado.");
    });

    /* ---------- pedidos: status inline e detalhes ---------- */
    const STATUS = {
        aguardando_pagamento: ["Aguardando pagamento", "pending"], pago: ["Pago", "approved"], processando: ["Processando", "pending"],
        enviado: ["Enviado", "shipped"], entregue: ["Entregue", "approved"], cancelado: ["Cancelado", "cancelled"],
    };
    document.addEventListener("change", async (e) => {
        const s = e.target.closest("[data-order-status]");
        if (!s) return;
        const prev = s.dataset.current;
        if (s.value === "cancelado" && !(await confirmAction("Cancelar pedido", `Marcar o pedido #${s.dataset.id} como cancelado?`, "Cancelar pedido"))) {
            s.value = prev;
            return;
        }
        s.disabled = true;
        try {
            await api("api/pedidos.php", { op: "status", id: s.dataset.id, status: s.value });
            s.dataset.current = s.value;
            const cell = s.closest("tr").cells[+s.dataset.statusCol];
            cell.dataset.value = STATUS[s.value][0];
            cell.querySelector(".status").className = "status " + STATUS[s.value][1];
            cell.querySelector(".status").textContent = STATUS[s.value][0];
            toast(`Pedido #${s.dataset.id} atualizado.`);
            window.lrc?.refreshShell?.(); // atualiza o contador de pendentes do menu e do sino
        } catch (err) {
            s.value = prev;
            toast(err.message, "erro");
        } finally {
            s.disabled = false;
        }
    });

    function el(tag, text, cls) {
        const n = document.createElement(tag);
        if (text != null) n.textContent = text;
        if (cls) n.className = cls;
        return n;
    }
    const money = (v) => "R$ " + Number(v).toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    document.addEventListener("click", async (e) => {
        const b = e.target.closest("[data-order-detail]");
        if (!b) return;
        const m = $("#detail-modal");
        const box = $("#detail-body", m);
        box.replaceChildren(el("p", "Carregando..."));
        openModal(m);
        try {
            const j = await api("api/pedidos.php?detalhe=" + encodeURIComponent(b.dataset.orderDetail));
            $("[data-modal-title]", m).textContent = "Pedido #" + j.pedido.id_pedido;
            const meta = el("div", null, "detail-meta");
            [["Cliente", j.pedido.cliente], ["E-mail", j.pedido.email], ["Data", j.pedido.data], ["Pagamento", j.pedido.pagamento || "—"], ["Status", (STATUS[j.pedido.status] || [j.pedido.status])[0]]].forEach(([k, v]) => {
                const d = document.createElement("div");
                d.append(el("span", k), document.createTextNode(v));
                meta.appendChild(d);
            });
            const ul = el("ul", null, "detail-list");
            j.itens.forEach((i) => {
                const li = document.createElement("li");
                li.append(el("span", `${i.quantidade}× ${i.nome} (${i.codigo})`), el("strong", money(i.quantidade * i.preco_unitario)));
                ul.appendChild(li);
            });
            const tot = el("div", null, "detail-total");
            tot.append(el("span", "Total"), el("span", money(j.total)));
            box.replaceChildren(meta, ul, tot);
        } catch (err) {
            box.replaceChildren(el("p", err.message));
        }
    });

    /* ---------- clientes: ativar/desativar e detalhes ---------- */
    document.addEventListener("click", async (e) => {
        const b = e.target.closest("[data-toggle-customer]");
        if (b) {
            const tr = b.closest("tr");
            const ativo = b.dataset.ativo === "1";
            if (ativo && !(await confirmAction("Desativar cliente", "O cliente continuará no histórico, mas ficará marcado como inativo.", "Desativar"))) return;
            b.disabled = true;
            try {
                const j = await api("api/clientes.php", { op: "toggle", id: b.dataset.toggleCustomer });
                const on = j.ativo === 1;
                b.dataset.ativo = on ? "1" : "0";
                b.textContent = on ? "Desativar" : "Ativar";
                const cell = tr.cells[+b.dataset.statusCol];
                cell.dataset.value = on ? "Ativo" : "Inativo";
                const st = cell.querySelector(".status");
                st.className = "status " + (on ? "approved" : "inactive");
                st.textContent = on ? "Ativo" : "Inativo";
                toast(on ? "Cliente ativado." : "Cliente desativado.");
            } catch (err) {
                toast(err.message, "erro");
            } finally {
                b.disabled = false;
            }
            return;
        }
        const d = e.target.closest("[data-customer-detail]");
        if (!d) return;
        const m = $("#detail-modal");
        const box = $("#detail-body", m);
        box.replaceChildren(el("p", "Carregando..."));
        openModal(m);
        try {
            const j = await api("api/clientes.php?detalhe=" + encodeURIComponent(d.dataset.customerDetail));
            $("[data-modal-title]", m).textContent = j.cliente.nome;
            const meta = el("div", null, "detail-meta");
            [["E-mail", j.cliente.email], ["Telefone", j.cliente.telefone || "—"], ["Cadastro", j.cliente.criado_em || "—"], ["Total gasto", money(j.total)]].forEach(([k, v]) => {
                const x = document.createElement("div");
                x.append(el("span", k), document.createTextNode(v));
                meta.appendChild(x);
            });
            const ul = el("ul", null, "detail-list");
            j.pedidos.forEach((p) => {
                const li = document.createElement("li");
                li.append(el("span", `#${p.id_pedido} · ${p.data} · ${(STATUS[p.status] || [p.status])[0]}`), el("strong", money(p.total)));
                ul.appendChild(li);
            });
            if (!j.pedidos.length) ul.appendChild(el("li", "Nenhum pedido ainda."));
            box.replaceChildren(meta, el("strong", "Pedidos"), ul);
        } catch (err) {
            box.replaceChildren(el("p", err.message));
        }
    });

    /* ---------- senha: mostrar/ocultar ---------- */
    document.addEventListener("click", (e) => {
        const b = e.target.closest("[data-toggle-pass]");
        if (!b) return;
        const i = b.previousElementSibling;
        i.type = i.type === "password" ? "text" : "password";
        b.textContent = i.type === "password" ? "Mostrar" : "Ocultar";
    });

    /* ---------- inicialização que depende do conteúdo da página ----------
       O conteúdo só existe depois que admin-layout.js monta a página ("lrc:ready"). */
    function initPage() {
        $$("table[data-table]").forEach(setupTable);

        /* histórico: filtro por tipo */
        $$("[data-history-filter]").forEach((chip) => {
            chip.addEventListener("click", () => {
                $$("[data-history-filter]").forEach((c) => c.classList.toggle("on", c === chip));
                $("[data-table-search]")?.dispatchEvent(new Event("input"));
            });
        });

        /* senha: validação ao vivo */
        const pf = $("form[data-password-form]");
        if (pf) {
            const nova = pf.elements.nova, conf = pf.elements.confirmacao;
            const hn = $("[data-hint-new]", pf), hc = $("[data-hint-confirm]", pf);
            const check = () => {
                const okLen = nova.value.length >= 6;
                hn.textContent = nova.value ? (okLen ? "Tamanho ok." : "Use pelo menos 6 caracteres.") : "Mínimo de 6 caracteres.";
                hn.className = "hint " + (nova.value ? (okLen ? "good" : "bad") : "");
                const same = conf.value && conf.value === nova.value;
                hc.textContent = conf.value ? (same ? "As senhas conferem." : "As senhas não conferem.") : "";
                hc.className = "hint " + (conf.value ? (same ? "good" : "bad") : "");
                return okLen && same;
            };
            nova.addEventListener("input", check);
            conf.addEventListener("input", check);
            pf.addEventListener("submit", (e) => { if (!check()) { e.preventDefault(); toast("Confira a nova senha.", "erro"); } });
        }
    }
    document.addEventListener("lrc:ready", initPage);

    /* utilitários compartilhados com admin-pages.js */
    window.lrc = Object.assign(window.lrc || {}, { api, toast, openModal, closeModal, confirmAction, el, money, norm, STATUS });
})();
