// PHP - Processamento dos formulários
$(document).ready(function () {
  $("#forms_log, #forms_cad, #forms_pass").on("submit", function (e) {
    e.preventDefault();

    let $form = $(this);
    let dados = $form.serialize();
    let $resp = $("#resp");

    $.ajax({
      url: "processData.php",
      type: "POST",
      data: dados,
      dataType: "json",
      success: function (response) {
        console.log(response);
        if (response.status === "ok") {
          $resp.html(
            "<p style='color:green; display:block'>" +
              response.mensagem +
              "</p>",
          );
        } else {
          $resp.html(
            "<p style='color:red; display:block'>" + response.mensagem + "</p>",
          );
        }
      },
      error: function () {
        $resp.html(
          "<p style='color:red; display:block'>Erro na requisição!!</p>",
        );
      },
    });
  });
});