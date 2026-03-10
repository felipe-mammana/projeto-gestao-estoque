<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/helpers.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/image_handler.php';
$id_user = ensureLoggedInUser();

include $_SERVER['DOCUMENT_ROOT'] . "/estoquemh/cone.php";

/* ===== DADOS DO FORMULÁRIO ===== */
$em_uso_id      = (int)($_POST['item-id'] ?? 0);
$nome_item      = $_POST['item-nome'] ?? '';
$categoria_id   = (int)($_POST['item-categoria'] ?? 0);
$setor_id       = (int)($_POST['setor_id'] ?? 0);
$patrimonio     = $_POST['patrimonio'] ?? '';
$observacao     = $_POST['item-observacao'] ?? '';

/* ===== VALIDAÇÕES ===== */
if ($em_uso_id <= 0 || empty($nome_item) || $categoria_id <= 0 || $setor_id <= 0) {
    $_SESSION['erro'] = 'Dados obrigatórios faltando.';
    header('Location: /estoquemh/pages/em-uso.php');
    exit;
}

/* ===== FOTO ===== */
$foto_loc_path = null;
$updatePhoto = false;

// Buscar imagem atual
$sqlCurrentPhoto = "SELECT foto_loc FROM itens_em_uso WHERE id = ?";
$stmtCurrentPhoto = $cone->prepare($sqlCurrentPhoto);
$stmtCurrentPhoto->bind_param("i", $em_uso_id);
$stmtCurrentPhoto->execute();
$resultCurrentPhoto = $stmtCurrentPhoto->get_result();
$currentPhotoRow = $resultCurrentPhoto->fetch_assoc();
$currentPhotoPath = $currentPhotoRow['foto_loc'] ?? null;
$stmtCurrentPhoto->close();

if (isset($_FILES['foto_loc']) && $_FILES['foto_loc']['error'] === UPLOAD_ERR_OK && isValidImage($_FILES['foto_loc'])) {
    // Salvar nova imagem
    $foto_loc_path = saveItemImage($_FILES['foto_loc'], $em_uso_id);
    
    if ($foto_loc_path) {
        // Deletar antiga se existir e for um path válido (não BLOB legado)
        if ($currentPhotoPath && strpos($currentPhotoPath, '/uploads/imagens/') === 0) {
            deleteItemImage($currentPhotoPath);
        }
        $updatePhoto = true;
    }
}

/* ===== ATUALIZAR DIRETAMENTE EM itens_em_uso ===== */
if ($updatePhoto) {
    $sqlUpdate = "
        UPDATE itens_em_uso 
        SET patrimonio = ?, nome = ?, categoria_id = ?, setor_id = ?, observacao = ?, foto_loc = ?
        WHERE id = ?
    ";
    $stmt = $cone->prepare($sqlUpdate);
    $stmt->bind_param("ssiiisi", $patrimonio, $nome_item, $categoria_id, $setor_id, $observacao, $foto_loc_path, $em_uso_id);
} else {
    $sqlUpdate = "
        UPDATE itens_em_uso 
        SET patrimonio = ?, nome = ?, categoria_id = ?, setor_id = ?, observacao = ?
        WHERE id = ?
    ";
    $stmt = $cone->prepare($sqlUpdate);
    $stmt->bind_param("ssiisi", $patrimonio, $nome_item, $categoria_id, $setor_id, $observacao, $em_uso_id);
}

if (!$stmt->execute()) {
    $_SESSION['erro'] = 'Erro ao atualizar item: ' . $stmt->error;
    header('Location: /estoquemh/pages/em-uso.php');
    exit;
}

$stmt->close();

$_SESSION['sucesso'] = "Item '{$nome_item}' atualizado com sucesso!";
header('Location: /estoquemh/pages/em-uso.php');
exit;
?>
