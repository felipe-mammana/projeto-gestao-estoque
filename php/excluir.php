<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/helpers.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/image_handler.php';
$id_user = ensureLoggedInUser();

/* ===== PERMISSÃO ===== */
if (!usuarioEhAdmin()) {
    http_response_code(403);
    die("Acesso negado. Apenas administradores podem excluir itens.");
}

include $_SERVER['DOCUMENT_ROOT'] . "/estoquemh/cone.php";

/* ===== VALIDAÇÃO ===== */
if (
    !isset($_POST['item_id']) || 
    empty($_POST['item_id']) || 
    !isset($_POST['motivo']) || 
    trim($_POST['motivo']) === ''
) {
    header("Location: /estoquemh/pages/sistema.php");
    exit;
}

$item_id = intval($_POST['item_id']);
$motivo  = trim($_POST['motivo']);

/* ===== BUSCAR ITEM ===== */
$stmtBusca = $cone->prepare(
    "SELECT nome, foto_loc FROM itens WHERE id = ?"
);
$stmtBusca->bind_param("i", $item_id);
$stmtBusca->execute();
$result = $stmtBusca->get_result();

if ($result->num_rows === 0) {
    die("Item não encontrado.");
}

$item = $result->fetch_assoc();
$nome_item = $item['nome'];
$foto_loc = $item['foto_loc'];
$stmtBusca->close();

/* ===== EXCLUIR ITEM ===== */
$stmt = $cone->prepare(
    "DELETE FROM itens WHERE id = ?"
);
$stmt->bind_param("i", $item_id);

if ($stmt->execute()) {
    // Deletar arquivo de imagem se existir
    if (!empty($foto_loc)) {
        deleteItemImage($foto_loc);
    }

    /* ===== LOG ===== */
    $detalhe = "ITEM '$nome_item' EXCLUÍDO. MOTIVO: $motivo";

    $sqlLog = "
        INSERT INTO logs (usuario_id, acao, detalhes)
        VALUES (?, 'ITEM EXCLUÍDO', ?)
    ";

    $stmtLog = $cone->prepare($sqlLog);
    $stmtLog->bind_param("is", $id_user, $detalhe);
    $stmtLog->execute();

    header("Location: /estoquemh/pages/sistema.php?" . http_build_query([
        'busca' => $_POST['redirect_busca'] ?? '',
        'status' => $_POST['redirect_status'] ?? '',
        'categoria_pai' => $_POST['redirect_categoria_pai'] ?? '',
        'subcategoria' => $_POST['redirect_subcategoria'] ?? '',
        'localizacao_pai' => $_POST['redirect_localizacao_pai'] ?? '',
        'pagina' => $_POST['redirect_pagina'] ?? 1,
        'limite' => $_POST['redirect_limite'] ?? 10
    ]));
    exit;

} else {
    echo "Erro ao excluir item: " . $stmt->error;
}
