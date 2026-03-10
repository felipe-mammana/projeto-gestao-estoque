<?php
include $_SERVER['DOCUMENT_ROOT'] . "/estoquemh/cone.php";

$id   = $_POST['id'];
$nome = $_POST['nome'];
$qtd  = $_POST['quantidade_minima'] ?? null;

if ($qtd !== null) {
    $sql = "UPDATE categorias SET nome = ?, quantidade_minima = ? WHERE id = ?";
    $stmt = $cone->prepare($sql);
    $stmt->bind_param("sii", $nome, $qtd, $id);
} else {
    $sql = "UPDATE categorias SET nome = ? WHERE id = ?";
    $stmt = $cone->prepare($sql);
    $stmt->bind_param("si", $nome, $id);
}

$stmt->execute();

header("Location: /estoquemh/pages/sistema.php");
exit;
