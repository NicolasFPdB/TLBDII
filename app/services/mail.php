<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Envia e-mail somente quando PHPMailer e as variáveis SMTP estiverem
 * configurados no ambiente. Nenhuma credencial é armazenada no repositório.
 */
function TokenReset(string $destinatario, string $token): bool
{
    $autoload = __DIR__ . '/../../vendor/autoload.php';
    $host = getenv('SMTP_HOST') ?: '';
    $username = getenv('SMTP_USERNAME') ?: '';
    $password = getenv('SMTP_PASSWORD') ?: '';
    $from = getenv('SMTP_FROM') ?: $username;

    if (!is_file($autoload) || !$host || !$username || !$password || !$from) {
        error_log('Envio de e-mail indisponível: dependência ou configuração SMTP ausente.');
        return false;
    }
    require_once $autoload;
    if (!class_exists(PHPMailer::class)) return false;

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int)(getenv('SMTP_PORT') ?: 587);
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($from, getenv('SMTP_FROM_NAME') ?: 'LRC1970 AutoPeças');
        $mail->addAddress($destinatario);
        $mail->isHTML(true);
        $mail->Subject = 'Código de redefinição de senha';
        $safeToken = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
        $mail->Body = '<p>Seu código de redefinição:</p><h2>' . $safeToken . '</h2>';
        $mail->AltBody = 'Código de redefinição: ' . $token;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Falha no envio de e-mail: ' . $mail->ErrorInfo);
        return false;
    }
}
