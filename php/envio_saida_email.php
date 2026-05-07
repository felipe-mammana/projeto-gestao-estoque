<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/vendor/autoload.php';
require_once  $_SERVER['DOCUMENT_ROOT'] .'/estoquemh/helpers.php';

$config = require $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/config/email.php';
$destinos_email = [];

foreach ($setores_ids as $i => $setor_id) {
    $q = (int) ($quantidades[$i] ?? 0);

    if ($setor_id && $q > 0) {
        $sql_setor = $cone->prepare("
            SELECT nome, descricao FROM setores WHERE id = ?
        ");
        $sql_setor->bind_param("i", $setor_id);
        $sql_setor->execute();
        $setor = $sql_setor->get_result()->fetch_assoc();

        if ($setor) {
            $destinos_email[] = $setor['nome'] . ' - ' . $setor['descricao'] . " ({$q})";
        }
    }
}

$emailDados['destinos'] = $destinos_email;

$mail = new PHPMailer(true);

try {
    if (empty($config['user']) || empty($config['pass'])) {
        error_log('SMTP nao configurado. Defina SMTP_USER e SMTP_PASS no ambiente.');
        return;
    }

    // Obter destinatários do email dinamicamente
    $destinatarios = obterDestinatariosEmail();
    
    // Se não houver destinatários, registra e encerra silenciosamente
    if (empty($destinatarios)) {
        error_log("Aviso: Nenhum destinatário configurado para envio de email de saída");
        return;
    }

    // SMTP
    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['user'];
    $mail->Password = $config['pass'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
    $mail->Port = $config['port'];
    $mail->CharSet = 'UTF-8';

    // Remetente
    $mail->setFrom($config['user'], $config['from_name']);

    // Destinatários dinâmicos
    foreach ($destinatarios as $email) {
        $mail->addAddress($email);
    }

    // Conteúdo
    $mail->isHTML(true);
    $mail->Subject = '📦 Saída de item do estoque';

    $mail->Body = "
    <h3>📦 Saída de Item do Estoque</h3>

    <table border='1' cellpadding='6' cellspacing='0' style='border-collapse: collapse; width: 100%;'>
        <tr style='background-color: #f0f0f0;'>
            <td><b>Item</b></td>
            <td>{$emailDados['item']}</td>
        </tr>
        <tr>
            <td><b>Categoria</b></td>
            <td>{$emailDados['categoria']}</td>
        </tr>
        <tr style='background-color: #f0f0f0;'>
            <td><b>Quantidade total</b></td>
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
";

if (!empty($emailDados['destinos'])) {

    $mail->Body .= "
        <br>
        <h4>📍 Destinos da Saída</h4>
        <ul>
    ";

    foreach ($emailDados['destinos'] as $destino) {
        $mail->Body .= "<li>{$destino}</li>";
    }

    $mail->Body .= "
        </ul>
    ";
}

$mail->Body .= "
    <br>
    <small style='color: #666;'>
        Este é um email automático do sistema de estoque.
    </small>
";
    $mail->send();

} catch (Exception $e) {
    error_log("Erro ao enviar email de saída: {$mail->ErrorInfo}");
}
