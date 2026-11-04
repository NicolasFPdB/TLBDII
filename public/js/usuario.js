$(document).ready(function () {
    // 1. Lógica de Alternância de Telas/Abas
    $(".nav-link").on("click", function (e) {
        e.preventDefault();
        const target = $(this).attr("href");

        $(".nav-link").removeClass("active");
        $(this).addClass("active");

        $(".content-section").hide();
        $(target).show();
    });

    // 2. Carregar Perfil do Usuário
    function loadUserData() {
        $.ajax({
            url: "../app/controller/usuario.php?action=get_profile",
            type: "GET",
            dataType: "json",
            success: function (res) {
                if (res.user) {
                    const nameParts = (res.user.nome || "").split(" ");
                    $("#user-nome").val(nameParts[0] || "");
                    $("#user-sobrenome").val(nameParts.slice(1).join(" ") || "");
                    $("#user-email").val(res.user.email || "");
                    $("#user-telefone").val(res.user.telefone || "");

                    $("#user-display-name").text(res.user.nome || "Minha Conta");
                    $("#user-display-email").text(res.user.email || "");

                    // Se o usuário for administrador, exibe a aba de Admin
                    if (res.user.tipo === "administrador" || res.user.is_admin) {
                        $("#nav-admin-li").show();
                        $("#user-role-badge").text("Administrador").addClass("badge-admin");
                        loadAdminProducts();
                    }
                }
            }
        });
    }

    loadUserData();

    // 3. Carregar Produtos para o Painel Admin
    function loadAdminProducts() {
        $.ajax({
            url: "../app/controller/usuario.php?action=list_products",
            type: "GET",
            dataType: "json",
            success: function (res) {
                let html = "";
                if (res.products && res.products.length > 0) {
                    res.products.forEach(p => {
                        html += `
                            <tr>
                                <td>${p.codigo || p.Código}</td>
                                <td>${p.nome || p['Descrição do Produto']}</td>
                                <td>R$ ${parseFloat(p.preco || 45.9).toFixed(2)}</td>
                                <td>${p.estoque || 10}</td>
                                <td>
                                    <button class="btn-primary btn-edit-prod" data-id="${p.id || 0}" style="padding: 4px 8px; font-size:12px;">Editar</button>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    html = `<tr><td colspan="5" style="text-align:center;">Nenhum produto cadastrado.</td></tr>`;
                }
                $("#admin-products-list").html(html);
            }
        });
    }

    // 4. Sincronizar produtos com o JSON
    $("#btn-sync-json").on("click", function () {
        $("#resp_admin").html("<p style='color:blue;'>Sincronizando com JSON...</p>");
        $.ajax({
            url: "../app/controller/usuario.php?action=sync_json",
            type: "POST",
            dataType: "json",
            success: function (res) {
                $("#resp_admin").html(`<p style="color:green;">${res.message}</p>`);
                loadAdminProducts();
            }
        });
    });

    // Toggle formulário de produto
    $("#btn-open-prod-modal").on("click", function () {
        $("#admin-product-form-box").toggle();
    });
    $("#btn-cancel-prod").on("click", function () {
        $("#admin-product-form-box").hide();
    });

    // Submit formulário de Perfil
    $("#forms_user_info").on("submit", function (e) {
        e.preventDefault();
        $.ajax({
            url: "../app/controller/usuario.php?action=update_info",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function (res) {
                $("#resp_info").html(`<p style="color:green;">${res.message}</p>`);
                loadUserData();
            }
        });
    });
});