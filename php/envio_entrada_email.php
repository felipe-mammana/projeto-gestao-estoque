<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require $_SERVER['DOCUMENT_ROOT'] .'/estoquemh/vendor/autoload.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/helpers.php';

$config = require $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/config/email.php';

$mail = new PHPMailer(true);

try {
    // Obter destinatários do email dinamicamente
    $destinatarios = obterDestinatariosEmail();
    
    // Se não houver destinatários, registra e encerra silenciosamente
    if (empty($destinatarios)) {
        error_log("Aviso: Nenhum destinatário configurado para envio de email de entrada");
        return;
    }

    // SMTP
    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['user'];
    $mail->Password = $config['pass'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
    $mail->Port = 465;
    $mail->CharSet = 'UTF-8';

    // Remetente
    $mail->setFrom($config['user'], 'Estoque TI');

    // Destinatários dinâmicos
    foreach ($destinatarios as $email) {
        $mail->addAddress($email);
    }

    // Conteúdo
    $mail->isHTML(true);
    $mail->Subject = '📦 Entrada de item do estoque';

    $mail->Body = "
        <h3>Entrada de Item do Estoque</h3>
        <table border='1' cellpadding='6' cellspacing='0' style='border-collapse: collapse;'>
            <tr style='background-color: #f0f0f0;'>
                <td><b>Item</b></td>
                <td>{$emailDados['item']}</td>
            </tr>
            <tr>
                <td><b>Categoria</b></td>
                <td>{$emailDados['categoria']}</td>
            </tr>
            <tr style='background-color: #f0f0f0;'>
                <td><b>Quantidade</b></td>
                <td>{$emailDados['quantidade']}</td>
            </tr>
            <tr>
                <td><b>Usuário</b></td>
                <td>{$emailDados['usuario']}</td>
            </tr>
            <tr style='background-color: #f0f0f0;'>
                <td><b>Observação</b></td>
                <td>{$emailDados['observacao']}</td>
            </tr>
            <tr>
                <td><b>Data</b></td>
                <td>{$emailDados['data']}</td>
            </tr>
        </table>
        <br>
        <small style='color: #666;'>Este é um email automático do sistema de estoque.</small>
    ";

    $mail->send();

} catch (Exception $e) {
    error_log("Erro ao enviar email de entrada: {$mail->ErrorInfo}");
}
