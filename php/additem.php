<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/helpers.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/locations_crud.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/image_handler.php';
$id_user = ensureLoggedInUser();

include $_SERVER['DOCUMENT_ROOT'] . "/estoquemh/cone.php";

/* ===== DADOS DO FORMULÁRIO ===== */
$nome_item        = $_POST['item-nome'] ?? '';
$categoria_id     = (int)($_POST['item-categoria'] ?? 0);
$localizacao_item = '';
$localizacao_id   = null;

$location_filho = $_POST['item-location-filho'] ?? null;
$location_pai = $_POST['item-location-pai'] ?? null;

if (!empty($location_filho)) {
    $localizacao_id = (int)$location_filho;
    $childLocation = getLocationById($cone, $location_filho);
    $parentLocation = getLocationById($cone, $location_pai);
    if ($childLocation && $parentLocation) {
        $localizacao_item = $parentLocation['nome'] . ' > ' . $childLocation['nome'];
    } elseif ($childLocation) {
        $localizacao_item = $childLocation['nome'];
    }
} elseif (!empty($location_pai)) {
    $localizacao_id = (int)$location_pai;
    $parentLocation = getLocationById($cone, $location_pai);
    if ($parentLocation) {
        $localizacao_item = $parentLocation['nome'];
    }
}

$quantidade_item  = (int)($_POST['item-qtde'] ?? 0);
$observacao       = $_POST['item-observacao'] ?? null;

/* ===== FOTO ===== */
$foto_loc_path = null;
if (!empty($_FILES['foto_loc']['tmp_name']) && isValidImage($_FILES['foto_loc'])) {
    // Será atribuído após inserir o item (para usar o ID gerado)
    $pendingImage = $_FILES['foto_loc'];
} else {
    $pendingImage = null;
}

$detalhe_item = "ITEM '{$nome_item}' ADICIONADO AO SISTEMA";

/* ===== INSERT DO ITEM ===== */
$sql = "INSERT INTO itens (nome, categoria_id, localizacao, localizacao_id, quantidade, observacao, foto_loc) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $cone->prepare($sql);
if (!$stmt) { echo "Erro na preparação: " . $cone->error; exit; }

$stmt->bind_param("sisiiss", $nome_item, $categoria_id, $localizacao_item, $localizacao_id, $quantidade_item, $observacao, $foto_loc_path);

if ($stmt->execute()) {
    $itemId = $stmt->insert_id; // Obter ID do item inserido
    
    // Processar imagem se houver
    if ($pendingImage !== null) {
        $imagePath = saveItemImage($pendingImage, $itemId);
        if ($imagePath) {
            // Atualizar o registro com o caminho da imagem
            $updateSql = "UPDATE itens SET foto_loc = ? WHERE id = ?";
            $updateStmt = $cone->prepare($updateSql);
            if ($updateStmt) {
                $updateStmt->bind_param("si", $imagePath, $itemId);
                $updateStmt->execute();
                $updateStmt->close();
            }
        }
    }
    
    // Registrar log
    $sql_logs = "INSERT INTO logs (usuario_id, acao, detalhes) VALUES (?, 'ITEM ADICIONADO AO SISTEMA', ?)";
    $stmtLog = $cone->prepare($sql_logs);
    if ($stmtLog) {
        $detalhe_item = "ITEM '{$nome_item}' ADICIONADO AO SISTEMA";
        $stmtLog->bind_param("is", $id_user, $detalhe_item);
        $stmtLog->execute();
        $stmtLog->close();
    }

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
    echo "Erro ao inserir item: " . $stmt->error;
}
?>