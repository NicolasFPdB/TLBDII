// Declaração de variáveis
let fgtBtn = document.getElementById("fgt-password");
let notCadBtn = document.getElementById("not-cad");
let loggedBtn = document.getElementById("already-log");
let passwordBtn = document.getElementById("password-btn");
let formsLog = document.getElementById("forms_log");
let formsCad = document.getElementById("forms_cad");
let formsPass = document.getElementById("forms_pass");

// Funções
function fgtPassword() {
	formsLog.style.display = "none";
	formsCad.style.display = "none";
	formsPass.style.display = "block";
}

function alreadyLogged() {
	formsLog.style.display = "block";
	formsCad.style.display = "none";
	formsPass.style.display = "none";
}

function notCad() {
	formsLog.style.display = "none";
	formsCad.style.display = "block";
	formsPass.style.display = "none";
}

function newPassword() {
	formsLog.style.display = "block";
	formsCad.style.display = "none";
	formsPass.style.display = "none";
}

// EventListeners
fgtBtn.addEventListener("click", fgtPassword);
loggedBtn.addEventListener("click", alreadyLogged);
notCadBtn.addEventListener("click", notCad);
passwordBtn.addEventListener("click", newPassword);
