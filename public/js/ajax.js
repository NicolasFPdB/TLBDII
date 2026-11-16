// PHP - Processamento dos formulários
$(document).ready(function () {
  $("#forms_log, #forms_cad, #forms_pass, #forms_token").on("submit", function (e) {
    e.preventDefault();

    let $form = $(this);
    let dados = $form.serialize();
    let $resp = $form.find(".resp"); // Pega o .resp específico do formulário atual

    // Limpa a resposta anterior
    $resp.html("");

    $.ajax({
      url: "../app/controllers/process.php", // Certifique-se de que o caminho do process.php está correto para a sua página
      type: "POST",
      data: dados,
      dataType: "json",
      success: function (response) {
        console.log(response);

        let statusCheck = response.status.check || response.status.general;
        let messageText = response.message.check || response.message.general;

        if (statusCheck === "ok") {
          $resp.html(
            "<p style='color:green; display:block'>" + messageText + "</p>"
          );

          // SE FOR O FORMULÁRIO DE LOGIN, REDIRECIONA PARA A HOME
          if ($form.attr("id") === "forms_log") {
            setTimeout(function () {
              window.location.href = "../public/home.html"; // Altere o caminho se a home estiver em outra pasta (ex: "../home.html")
            }, 1000); // 1000ms = 1 segundo de espera
          }

        } else {
          $resp.html(
            "<p style='color:red; display:block'>" + messageText + "</p>"
          );
        }
      },
      error: function (xhr, status, error) {
        console.error(error);
        $resp.html(
          "<p style='color:red; display:block'>Erro na requisição!!</p>"
        );
      },
    });
  });
});