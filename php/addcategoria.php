<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/helpers.php';
$id_user = ensureLoggedInUser();

include $_SERVER['DOCUMENT_ROOT'] . "/estoquemh/cone.php"; 

// 1. Coleta os dados do formulário
$nome_categoria = $_POST['categoria-nome'];
$parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;

// 2. Coleta a quantidade mínima (se não preenchido, vira 0)
$quantidade_minima = !empty($_POST['quantidade_minima']) ? intval($_POST['quantidade_minima']) : 0;

$detalhe_categoria = "CATEGORIA '$nome_categoria' (Mín: $quantidade_minima) ADICIONADA AO SISTEMA";

// 3. Query atualizada com a nova coluna
$sql = "INSERT INTO categorias (nome, parent_id, quantidade_minima) VALUES (?, ?, ?)";
$stmt = $cone->prepare($sql);

// "sii" significa: string (nome), integer (parent_id), integer (quantidade_minima)
$stmt->bind_param("sii", $nome_categoria, $parent_id, $quantidade_minima);

if ($stmt->execute()) {
    // Registro de Logs
    $sql_logs = "INSERT INTO logs (usuario_id, acao, detalhes)
                 VALUES (?, 'CATEGORIA ADICIONADA AO SISTEMA', ?)";
    $stmtLog = $cone->prepare($sql_logs);
    $stmtLog->bind_param("is", $id_user, $detalhe_categoria);
    $stmtLog->execute();

    header("Location: /estoquemh/pages/sistema.php");
    exit;
} else {
    echo "Erro ao inserir categoria: " . $stmt->error;
}