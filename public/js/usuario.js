$(function () {
    const endpoint = "../app/controllers/usuario.php";
    const esc = (value) => String(value ?? "").replace(/[&<>"']/g, ch => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
    }[ch]));

    $(".nav-link").on("click", function (e) {
        e.preventDefault();
        const target = $(this).attr("href");
        if (!target || !$(target).length) return;
        $(".nav-link").removeClass("active");
        $(this).addClass("active");
        $(".content-section").hide();
        $(target).show();
    });

    function loadAddresses() {
        $.getJSON(endpoint, { action: "get_addresses" }).done(res => {
            const $list = $("#address-list").empty();
            if (!res.addresses || !res.addresses.length) {
                $list.text("Nenhum endereço cadastrado.");
                return;
            }
            res.addresses.forEach(a => {
                $("<p>").text(`${a.logradouro}, ${a.numero}${a.complemento ? " - " + a.complemento : ""} — ${a.bairro}, ${a.cidade}/${a.estado} — CEP ${a.cep}`).appendTo($list);
            });
        });
    }

    function loadAdminProducts() {
        $.getJSON(endpoint, { action: "list_products" }).done(res => {
            const $list = $("#admin-products-list").empty();
            if (!res.products || !res.products.length) {
                $list.append($("<tr>").append($("<td colspan='5'>").text("Nenhum produto cadastrado.")));
                return;
            }
            res.products.forEach(p => {
                const $tr = $("<tr>");
                [$("<td>").text(p.codigo), $("<td>").text(p.nome),
                 $("<td>").text(Number(p.preco).toLocaleString("pt-BR", {style:"currency",currency:"BRL"})),
                 $("<td>").text(p.estoque)].forEach($td => $tr.append($td));
                const $edit = $("<button type='button' class='btn-primary btn-edit-prod'>").text("Editar")
                    .attr("data-id", p.id).attr("data-codigo", p.codigo)
                    .attr("data-nome", p.nome).attr("data-preco", p.preco).attr("data-estoque", p.estoque);
                $tr.append($("<td>").append($edit));
                $list.append($tr);
            });
        }).fail(() => $("#admin-products-list").text("Não foi possível carregar os produtos."));
    }

    function loadUserData() {
        $.getJSON(endpoint, { action: "get_profile" }).done(res => {
            if (!res.user) return;
            const u = res.user;
            $("#user-nome").val(u.nome || "");
            $("#user-sobrenome").val(u.sobrenome || "");
            $("#user-email").val(u.email || "");
            $("#user-telefone").val(u.telefone || "");
            $("#user-display-name").text(u.nome_completo || "Minha Conta");
            $("#user-display-email").text(u.email || "");
            if (u.tipo === "administrador") {
                $("#nav-admin-li").show();
                $("#user-role-badge").text("Administrador").addClass("badge-admin");
                loadAdminProducts();
            }
        }).fail(xhr => {
            if (xhr.status === 401) window.location.href = "forms.html";
        });
    }

    loadUserData();
    loadAddresses();

    $("#btn-sync-json").on("click", function () {
        $("#resp_admin").text("Sincronizando catálogo...");
        $.ajax({url:endpoint+"?action=sync_json",type:"POST",dataType:"json"})
            .done(res => { $("#resp_admin").text(res.message || "Concluído."); loadAdminProducts(); })
            .fail(xhr => $("#resp_admin").text(xhr.responseJSON?.message || "Falha ao sincronizar."));
    });

    $("#btn-open-prod-modal").on("click", () => {
        $("#forms_admin_product")[0]?.reset();
        $("#prod-id").val("0");
        $("#prod-form-title").text("Cadastrar Produto");
        $("#admin-product-form-box").show();
    });
    $("#btn-cancel-prod").on("click", () => $("#admin-product-form-box").hide());

    $(document).on("click", ".btn-edit-prod", function () {
        const b = $(this);
        $("#prod-id").val(b.attr("data-id"));
        $("#prod-codigo").val(b.attr("data-codigo"));
        $("#prod-descricao").val(b.attr("data-nome"));
        $("#prod-preco").val(b.attr("data-preco"));
        $("#prod-estoque").val(b.attr("data-estoque"));
        $("#prod-form-title").text("Editar Produto");
        $("#admin-product-form-box").show();
    });

    $("#forms_pass").on("submit", function (e) {
        e.preventDefault();
        const $form = $(this);
        $.ajax({url:"../app/controllers/pass.php",type:"POST",data:$form.serialize(),dataType:"json"})
            .done(res => $("#resp").text(res.message || "Concluído."))
            .fail(xhr => $("#resp").text(xhr.responseJSON?.message || "Não foi possível alterar a senha."));
    });

    $("#forms_user_info, #forms_user_address, #forms_admin_product").on("submit", function (e) {
        e.preventDefault();
        const $form = $(this);
        const action = this.id === "forms_user_info" ? "update_info" :
            this.id === "forms_user_address" ? "save_address" : "save_product";
        const target = this.id === "forms_user_info" ? "#resp_info" :
            this.id === "forms_user_address" ? "#resp_address" : "#resp_admin";
        $.ajax({url:endpoint+"?action="+action,type:"POST",data:$form.serialize(),dataType:"json"})
            .done(res => {
                $(target).text(res.message || "Concluído.");
                if (res.status === "ok") {
                    if (action === "update_info") loadUserData();
                    if (action === "save_address") { $form[0].reset(); loadAddresses(); }
                    if (action === "save_product") { $form[0].reset(); $("#prod-id").val("0"); loadAdminProducts(); }
                }
            })
            .fail(xhr => $(target).text(xhr.responseJSON?.message || "Não foi possível salvar."));
    });
});
