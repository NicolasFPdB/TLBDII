// Declaração de formulários
let formsLog = document.getElementById("forms_log");
let formsCad = document.getElementById("forms_cad");
let formsPass = document.getElementById("forms_pass");
let formsToken = document.getElementById("forms_token");

// Funções
const fgtBtn = document.getElementById("fgt-password");
function fgtPassword() {
	formsLog.style.display = "none";
	formsCad.style.display = "none";
	formsPass.style.display = "block";
	formsToken.style.display = "none";
}

const loggedBtn = document.getElementById("already-log");
function alreadyLogged() {
	formsLog.style.display = "block";
	formsCad.style.display = "none";
	formsPass.style.display = "none";
	formsToken.style.display = "none";
}

const notCadBtn = document.getElementById("not-cad");
function notCad() {
	formsLog.style.display = "none";
	formsCad.style.display = "block";
	formsPass.style.display = "none";
	formsToken.style.display = "none";
}

const passwordBtn = document.getElementById("password-btn");
function newPassword() {
	formsLog.style.display = "none";
	formsCad.style.display = "none";
	formsPass.style.display = "none";
	formsToken.style.display = "block";
}

const cancelBtn = document.getElementById("password-cancel");
function passwordCancel() {
	formsLog.style.display = "block";
	formsCad.style.display = "none";
	formsPass.style.display = "none";
	formsToken.style.display = "none";
}

// EventListeners
fgtBtn.addEventListener("click", fgtPassword);
loggedBtn.addEventListener("click", alreadyLogged);
notCadBtn.addEventListener("click", notCad);
passwordBtn.addEventListener("click", newPassword);
cancelBtn.addEventListener("click", passwordCancel);