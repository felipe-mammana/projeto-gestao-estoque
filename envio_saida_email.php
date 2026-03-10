<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/vendor/autoload.php';

$config = require $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/config/email.php';

$mail = new PHPMailer(true);

try {
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

	// Destinatários (exemplo estático; caller deve preencher dinamicamente se necessário)
	$mail->addAddress('ricardo.tecnologia@masterhealth.com.br');
	$mail->addAddress('marcelo.helpdesk@masterhealth.com.br');
	$mail->addAddress('felipe.helpdesk@masterhealth.com.br');

	// Conteúdo
	$mail->isHTML(true);
	$mail->Subject = '📦 Saída de item do estoque';

	// `$emailDados` deve ser fornecido pelo chamador. Se não existir, usar valores vazios.
	$emailDados = $emailDados ?? [
		'item' => '', 'categoria' => '', 'quantidade' => '', 'usuario' => '', 'observacao' => '', 'data' => ''
	];

	$mail->Body = "
		<h3>Saída de Item do Estoque</h3>
		<table border='1' cellpadding='6' cellspacing='0'>
			<tr><td><b>Item</b></td><td>{$emailDados['item']}</td></tr>
			<tr><td><b>Categoria</b></td><td>{$emailDados['categoria']}</td></tr>
			<tr><td><b>Quantidade</b></td><td>{$emailDados['quantidade']}</td></tr>
			<tr><td><b>Usuário</b></td><td>{$emailDados['usuario']}</td></tr>
			<tr><td><b>Observação</b></td><td>{$emailDados['observacao']}</td></tr>
			<tr><td><b>Data</b></td><td>{$emailDados['data']}</td></tr>
		</table>
	";

	$mail->send();

} catch (Exception $e) {
	error_log("Erro ao enviar email: {$mail->ErrorInfo}");
}

