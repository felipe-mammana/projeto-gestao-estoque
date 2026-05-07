<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/vendor/autoload.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/helpers.php';

$config = require $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/config/email.php';

$mail = new PHPMailer(true);

try {
    if (empty($config['user']) || empty($config['pass'])) {
        error_log('SMTP nao configurado. Defina SMTP_USER e SMTP_PASS no ambiente.');
        return;
    }

    $destinatarios = obterDestinatariosEmail();
    if (empty($destinatarios)) {
        error_log('Aviso: nenhum destinatario configurado para envio de email.');
        return;
    }

    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['user'];
    $mail->Password = $config['pass'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = $config['port'];
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($config['user'], $config['from_name']);

    foreach ($destinatarios as $email) {
        $mail->addAddress($email);
    }

    $emailDados = $emailDados ?? [
        'item' => '',
        'categoria' => '',
        'quantidade' => '',
        'usuario' => '',
        'observacao' => '',
        'data' => '',
    ];

    $mail->isHTML(true);
    $mail->Subject = 'Movimentacao de item do estoque';
    $mail->Body = "
        <h3>Movimentacao de Item do Estoque</h3>
        <table border='1' cellpadding='6' cellspacing='0'>
            <tr><td><b>Item</b></td><td>{$emailDados['item']}</td></tr>
            <tr><td><b>Categoria</b></td><td>{$emailDados['categoria']}</td></tr>
            <tr><td><b>Quantidade</b></td><td>{$emailDados['quantidade']}</td></tr>
            <tr><td><b>Usuario</b></td><td>{$emailDados['usuario']}</td></tr>
            <tr><td><b>Observacao</b></td><td>{$emailDados['observacao']}</td></tr>
            <tr><td><b>Data</b></td><td>{$emailDados['data']}</td></tr>
        </table>
    ";

    $mail->send();
} catch (Exception $e) {
    error_log("Erro ao enviar email: {$mail->ErrorInfo}");
}
?>
