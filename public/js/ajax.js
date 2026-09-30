// AJAX - Processamento dos formulários
$(document).ready(function () {
  $("#forms_log, #forms_cad, #forms_pass, #forms_token").on("submit", function (e) {
    e.preventDefault();

    let $form = $(this);
    let dados = $form.serialize();
    let $resp = $form.find(".resp");

    $.ajax({
      url: "process.php",
      type: "POST",
      data: dados,
      dataType: "json",
      success: function (response) {
        console.log(response);
        if (response.status.check === "ok" || response.status.general === "ok") {
          $resp.html("<p style='color:green; display:block'>" + response.message.check + "</p>");
        } else {
          let erroMsg = response.message.check || response.message.general || "Erro ao processar.";
          $resp.html("<p style='color:red; display:block'>" + erroMsg + "</p>");
        }
      }
    });
  });
});
