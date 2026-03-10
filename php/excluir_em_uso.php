<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/helpers.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/image_handler.php';
$id_user = ensureLoggedInUser();

include $_SERVER['DOCUMENT_ROOT'] . "/estoquemh/cone.php";

/* ===== VALIDAÇÃO ===== */
if (!isset($_POST['item_id']) || empty($_POST['item_id'])) {
    $_SESSION['erro'] = 'ID do item não fornecido.';

    header('Location: /estoquemh/pages/em-uso.php');
    exit;
}

$item_id = intval($_POST['item_id']);

/* ===== BUSCAR ITEM ===== */
$stmt = $cone->prepare("SELECT nome, foto_loc FROM itens_em_uso WHERE id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['erro'] = 'Item não encontrado.';
    header('Location: /estoquemh/pages/em-uso.php');
    exit;
}

$item = $result->fetch_assoc();
$nome_item = $item['nome'];
$foto_loc = $item['foto_loc'];
$stmt->close();

/* ===== EXCLUIR ITEM ===== */
$sqlUpdate = "DELETE FROM itens_em_uso WHERE id = ?";
$stmtUpdate = $cone->prepare($sqlUpdate);
$stmtUpdate->bind_param("i", $item_id);

if (!$stmtUpdate->execute()) {
    $_SESSION['erro'] = 'Erro ao excluir item: ' . $stmtUpdate->error;
    header('Location: /estoquemh/pages/em-uso.php');
    exit;
}

// Deletar arquivo de imagem se existir
if (!empty($foto_loc) && strpos($foto_loc, '/uploads/imagens/') === 0) {
    deleteItemImage($foto_loc);
}

$stmtUpdate->close();

$_SESSION['sucesso'] = "Item '{$nome_item}' removido de em uso com sucesso!";
header('Location: /estoquemh/pages/em-uso.php');
exit;
?>
