const fgtBtn = document.getElementById('fgt-password');
const loggedBtn = document.getElementById('already-log');
const notCadBtn = document.getElementById('not-cad');
const cancelBtn = document.getElementById('password-cancel');
const formsLog = document.getElementById('forms_log');
const formsCad = document.getElementById('forms_cad');
const formsPass = document.getElementById('forms_pass');
const formsToken = document.getElementById('forms_token');

function ShowForm(form) {
  [formsLog, formsCad, formsPass, formsToken].forEach((item) => {
    if (item) item.style.display = 'none';
  });
  if (form) form.style.display = 'block';
}

fgtBtn?.addEventListener('click', () => ShowForm(formsPass));
loggedBtn?.addEventListener('click', () => ShowForm(formsLog));
notCadBtn?.addEventListener('click', () => ShowForm(formsCad));
cancelBtn?.addEventListener('click', () => ShowForm(formsLog));
