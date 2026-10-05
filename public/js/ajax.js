// AJAX - Processamento dos formulários
$(document).ready(function () {
  $("#forms_log, #forms_cad, #forms_pass, #forms_token").on("submit", function (e) {
    e.preventDefault();

    let $form = $(this);
    let dados = $form.serialize();
    let $resp = $form.find(".resp");

    if ($form.attr("id") === "forms_pass") {
      let pass = $form.find("#pass-pass").val();
      let confirmPass = $form.find("#pass-confirm").val();
    }

    $.ajax({
      url: "process.php",
      type: "POST",
      data: dados,
      dataType: "json",
      success: function (response) {
        console.log(response);
        let $msg = response.message.password || response.message.check || response.message.token || response.message.email || response.message.general || "Processado com sucesso!";
        if (response.status.check === "ok" || response.status.general === "ok" || response.status.token === "ok" || response.status.password === "ok") {
          $resp.html("<p style='color:green; display:block'>" + $msg + "</p>");
          if ($form.attr("id") === "forms_token") {
            setTimeout(function () {
              if (typeof ShowForm === "function") {
                ShowForm(document.getElementById("forms_pass"));
              } else {
                $("#forms_token").hide();
                $("#forms_pass").show();
              }
              $("#pass-email").val($("#token-email").val());
            }, 1500);
          }
          if ($form.attr("id") === "forms_log") {
            setTimeout(function () {
              window.location.href = "index.html";
            }, 1500);
          }
        } else {
          $resp.html("<p style='color:red; display:block'>" + $msg + "</p>");
        }
      },
      erro: function () {
        $resp.html("<p style='color: red; display: block'>Erro na comunicação com o servidor.</p>");
      }
    });
  });
});
