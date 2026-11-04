// AJAX - Processamento dos formulários
$(document).ready(function () {
  $("#forms_log, #forms_cad, #forms_pass, #forms_token, #forms_user_info, #forms_user_address, #forms_admin_product").on(
    "submit",
    function (e) {
      e.preventDefault();

      let $form = $(this);
      let dados = $form.serialize();
      let $resp = $form.find(".resp, #resp_info, #resp_address, #resp_admin");
      let formId = $form.attr("id");
      let url = "";

      // Validação específica para o formulário de password, se necessário
      if (formId === "forms_pass") {
        let pass = $form.find("#pass-pass").val();
        let confirmPass = $form.find("#pass-confirm").val();
      }

      // Define o ficheiro PHP correspondente a cada formulário
      switch (formId) {
        case "forms_log":
          url = "../app/controller/login.php";
          break;
        case "forms_cad":
          url = "../app/controller/cad.php";
          break;
        case "forms_token":
          url = "../app/controller/token.php";
          break;
        case "forms_pass":
          url = "../app/controller/pass.php";
          break;
        case "forms_user_info":
          url = "../app/controller/usuario.php?action=update_info";
          break;
        case "forms_user_address":
          url = "../app/controller/usuario.php?action=save_address";
          break;
        case "forms_admin_product":
          url = "../app/controller/usuario.php?action=save_product";
          break;
        default:
          break;
      }

      $.ajax({
        url: url,
        type: "POST",
        data: dados,
        dataType: "json",
        success: function (response) {
          console.log(response);
          let $msg =
            response.message?.password ||
            response.message?.check ||
            response.message?.email ||
            response.message?.token ||
            response.message?.general ||
            response.message ||
            "Processado com sucesso!";

          if (
            response.status?.check === "ok" ||
            response.status?.general === "ok" ||
            response.status?.password === "ok" ||
            response.status?.token === "ok" ||
            response.status === "ok"
          ) {
            $resp.html(
              "<p style='color:green; display:block'>" + $msg + "</p>",
            );

            switch (formId) {
              case "forms_token":
                setTimeout(function () {
                  if (typeof ShowForm === "function") {
                    ShowForm(document.getElementById("forms_pass"));
                  } else {
                    $("#forms_token").hide();
                    $("#forms_pass").show();
                  }
                  $("#pass-email").val($("#token-email").val());
                }, 1500);
                break;
              case "forms_log":
                setTimeout(function () {
                  window.location.href = "home.html";
                }, 1500);
                break;
              case "forms_user_info":
              case "forms_user_address":
              case "forms_admin_product":
                break;
              default:
                setTimeout(function () {
                  window.location.href = "index.html";
                }, 1500);
                break;
            }
          } else {
            $resp.html(
              "<p style='color:yellow; display:block'>" + $msg + "</p>",
            );
          }
        },
        error: function () {
          $resp.html(
            "<p style='color: red; display: block'>Erro na comunicação com o servidor.</p>",
          );
        },
      });
    },
  );
});