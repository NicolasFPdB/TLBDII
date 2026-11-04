$(function () {
  const routes = {
    forms_log: "../app/controllers/log.php",
    forms_cad: "../app/controllers/cad.php",
    forms_pass: "../app/controllers/pass.php",
    forms_token: "../app/controllers/token.php",
  };

  $("#forms_log, #forms_cad, #forms_pass, #forms_token").on(
    "submit",
    function (e) {
      e.preventDefault();
      const $form = $(this);
      const formId = this.id;
      const $resp = $form.find(".resp").first();
      const url = routes[formId];
      if (!url) return;

      $.ajax({ url, type: "POST", data: $form.serialize(), dataType: "json" })
        .done((response) => {
          const status =
            response.status?.general ||
            response.status?.check ||
            response.status;
          const message =
            response.message?.general ||
            response.message?.check ||
            response.message ||
            "Solicitação concluída.";
          $resp.text(message);
          if (status !== "ok") return;

          if (formId === "forms_log") {
            setTimeout(() => (window.location.href = "home.html"), 700);
          } else if (formId === "forms_cad") {
            setTimeout(() => (window.location.href = "forms.html"), 1000);
          }
        })
        .fail((xhr) =>
          $resp.text(
            xhr.responseJSON?.message?.general ||
              xhr.responseJSON?.message ||
              "Erro na comunicação com o servidor.",
          ),
        );
    },
  );
});
