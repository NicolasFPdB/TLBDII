<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function TokenReset(string $destinatario, string $token): bool
{
    $mail = new PHPMailer(true);

    try {
        // Configurações do servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.seudominio.com';   // Servidor SMTP (ex: smtp.gmail.com)
        $mail->SMTPAuth = true;
        $mail->Username = 'seu-email@dominio.com'; // Usuário SMTP
        $mail->Password = 'sua-senha-ou-app-token';// Senha do e-mail ou senha de app
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Ou PHPMailer::ENCRYPTION_SMTPS
        $mail->Port = 587;                     // Porta (587 para STARTTLS, 465 para SMTPS)
        $mail->CharSet = 'UTF-8';

        // Remetente e Destinatário
        $mail->setFrom('no-reply@seudominio.com', 'Suporte - Nome do Seu App');
        $mail->addAddress($destinatario);

        // Conteúdo do e-mail
        $mail->isHTML(true);
        $mail->Subject = 'Código de Redefinição de Senha';
        $mail->Body = "
            <p>Olá,</p>
            <p>Você solicitou a redefinição da sua senha. Use o código abaixo para prosseguir:</p>
            <h2 style='color: #2b6cb0; font-size: 24px;'>{$token}</h2>
            <p>Este código expira em 30 minutos.</p>
            <p>Se você não solicitou esta alteração, desconsidere esta mensagem.</p>
        ";
        $mail->AltBody = "Seu código de redefinição de senha é: {$token}. Ele expira em 30 minutos.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Registra o erro internamente para depuração sem expor detalhes na resposta
        error_log("Erro no envio do e-mail PHPMailer: " . $mail->ErrorInfo);
        return false;
    }
}
?>