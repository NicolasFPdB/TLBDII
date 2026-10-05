// Declaração de formulários
const fgtBtn = document.getElementById("fgt-password");
const loggedBtn = document.getElementById("already-log");
const notCadBtn = document.getElementById("not-cad");
const cancelBtn = document.getElementById("password-cancel");

let formsLog = document.getElementById("forms_log");
let formsCad = document.getElementById("forms_cad");
let formsPass = document.getElementById("forms_pass");
let formsToken = document.getElementById("forms_token");

// Funções
function ShowForm(forms) {
	formsLog.style.display = "none";
	formsCad.style.display = "none";
	formsPass.style.display = "none";
	formsToken.style.display = "none";

	if (forms) {
		forms.style.display = "block";
	}
}

// EventListeners
fgtBtn.addEventListener("click", () => ShowForm(formsPass));
loggedBtn.addEventListener("click", () => ShowForm(formsLog));
notCadBtn.addEventListener("click", () => ShowForm(formsCad));
cancelBtn.addEventListener("click", () => ShowForm(formsLog));