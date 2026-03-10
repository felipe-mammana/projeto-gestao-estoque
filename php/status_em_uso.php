<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/helpers.php';
ensureLoggedInUser();
include  $_SERVER['DOCUMENT_ROOT'] . "/estoquemh/cone.php";

$id    = (int)($_POST['item_id'] ?? 0);
$acao  = $_POST['acao'] ?? '';
$obs   = trim($_POST['observacao'] ?? '');

if ($id <= 0 || !in_array($acao, ['ativar','desativar'])) {

    header('Location: /estoquemh/pages/em-uso.php');
    exit;
}

if ($acao === 'desativar') {
    if (empty($obs)) {
        $_SESSION['erro'] = 'Observação obrigatória para desativar.';
        header('Location: /estoquemh/em-uso.php');
        exit;
    }

    $sql = "UPDATE itens_em_uso SET ativo = 0, observacao = ? WHERE id = ?";
    $stmt = $cone->prepare($sql);
    $stmt->bind_param("si", $obs, $id);
} else {
    $sql = "UPDATE itens_em_uso SET ativo = 1 WHERE id = ?";
    $stmt = $cone->prepare($sql);
    $stmt->bind_param("i", $id);
}

$stmt->execute();
$stmt->close();

$_SESSION['sucesso'] = 'Status do item atualizado.';
header('Location: /estoquemh/pages/em-uso.php');
exit;