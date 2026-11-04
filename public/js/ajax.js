$(function () {
  const routes = {
    forms_log: '../app/controllers/log.php',
    forms_cad: '../app/controllers/cad.php',
    forms_pass: '../app/controllers/pass.php',
    forms_token: '../app/controllers/token.php',
  };

  $('#forms_log, #forms_cad, #forms_pass, #forms_token').on('submit', function (e) {
    e.preventDefault();
    const $form = $(this);
    const formId = this.id;
    const $resp = $form.find('.resp').first();
    const url = routes[formId];
    if (!url) return;

    $.ajax({ url, type: 'POST', data: $form.serialize(), dataType: 'json' })
      .done((response) => {
        $resp.text(response.message || 'Solicitação concluída.');
        if (response.status !== 'ok') return;

        if (formId === 'forms_log') {
          window.location.href = 'home.html';
        } else if (formId === 'forms_cad') {
          window.location.href = 'forms.html';
        } else if (formId === 'forms_pass') {
          $('#token-email').val($('#pass-email').val().trim());
          $('#token-numbers').val('');
          $('#pass-pass, #pass-confirm').val('');
          if (typeof ShowForm === 'function') ShowForm(document.getElementById('forms_token'));
        } else if (formId === 'forms_token') {
          $form[0].reset();
          window.setTimeout(() => {
            if (typeof ShowForm === 'function') ShowForm(document.getElementById('forms_log'));
          }, 900);
        }
      })
      .fail((xhr) => {
        $resp.text(xhr.responseJSON?.message || 'Erro na comunicação com o servidor.');
      });
  });
});
