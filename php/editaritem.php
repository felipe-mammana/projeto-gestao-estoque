<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/helpers.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/locations_crud.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/image_handler.php';
$id_user = ensureLoggedInUser();

include $_SERVER['DOCUMENT_ROOT'] . "/estoquemh/cone.php";

/* ===== DADOS ===== */
$id            = (int)($_POST['item-id'] ?? 0);
$nome          = $_POST['item-nome'] ?? '';
$categoria     = (int)($_POST['item-categoria'] ?? 0);
$localizacao   = $_POST['item-localizacao'] ?? ''; // Campo legado (texto)
$observacao    = $_POST['item-observacao'] ?? null;
$quantidade    = (int)($_POST['item-quantidade'] ?? 0);

// Capturar location_id do cascading select
$location_filho = $_POST['editar-item-location-filho'] ?? null;
$location_pai = $_POST['editar-item-location-pai'] ?? null;
$localizacao_id = null; // Este é o ID que vamos salvar

// Se houver local selecionado, usar a hierarquia
if (!empty($location_filho)) {
    $localizacao_id = (int)$location_filho;
    $childLocation = getLocationById($cone, $location_filho);
    $parentLocation = getLocationById($cone, $location_pai);
    
    if ($childLocation && $parentLocation) {
        // Formato: "Local Pai > Local Filho"
        $localizacao = $parentLocation['nome'] . ' > ' . $childLocation['nome'];
    } elseif ($childLocation) {
        $localizacao = $childLocation['nome'];
    }
} elseif (!empty($location_pai)) {
    $localizacao_id = (int)$location_pai;
    $parentLocation = getLocationById($cone, $location_pai);
    if ($parentLocation) {
        $localizacao = $parentLocation['nome'];
    }
}

/* ===== FOTO ===== */
$foto_loc_path = null; // Armazenar novo caminho se houver upload
$updatePhoto = false; // Flag para atualizar foto
$erroFoto = null; // Capturar erro de foto

// Buscar imagem atual do banco
$sqlCurrentPhoto = "SELECT foto_loc FROM itens WHERE id = ?";
$stmtCurrentPhoto = $cone->prepare($sqlCurrentPhoto);
$stmtCurrentPhoto->bind_param("i", $id);
$stmtCurrentPhoto->execute();
$resultCurrentPhoto = $stmtCurrentPhoto->get_result();
$currentPhotoRow = $resultCurrentPhoto->fetch_assoc();
$currentPhotoPath = $currentPhotoRow['foto_loc'] ?? null;
$stmtCurrentPhoto->close();

// Nota: não deletamos a foto imediatamente; só se houver um upload novo com sucesso.
// (antes o código removia sempre, causando perda ao editar qualquer campo sem enviar nova imagem)


if (isset($_FILES['foto_loc'])) {
    // Verificar erro de upload
    if ($_FILES['foto_loc']['error'] !== UPLOAD_ERR_OK) {
        $erroUpload = [
            UPLOAD_ERR_INI_SIZE => 'Arquivo excede upload_max_filesize (servidor)',
            UPLOAD_ERR_FORM_SIZE => 'Arquivo excede MAX_FILE_SIZE (formulário)',
            UPLOAD_ERR_PARTIAL => 'Upload incompleto',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo selecionado',
            UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária não encontrada',
            UPLOAD_ERR_CANT_WRITE => 'Erro ao escrever arquivo',
            UPLOAD_ERR_EXTENSION => 'Extensão de arquivo não permitida',
        ];
        $erroFoto = $erroUpload[$_FILES['foto_loc']['error']] ?? 'Erro desconhecido no upload';
        error_log("ERRO UPLOAD FOTO: " . $erroFoto . " (código: " . $_FILES['foto_loc']['error'] . ")");
    } 
    elseif (!isValidImage($_FILES['foto_loc'])) {
        $erroFoto = 'Arquivo não é uma imagem válida (JPEG, PNG, GIF, WebP) ou excede 5MB';
        error_log("ERRO VALIDAÇÃO FOTO: " . $erroFoto);
    } 
    elseif (!empty($_FILES['foto_loc']['tmp_name'])) {
        // Tentar salvar imagem
        $foto_loc_path = saveItemImage($_FILES['foto_loc'], $id);
        
        if (!$foto_loc_path) {
            $erroFoto = 'Erro ao processar imagem no servidor. Tente novamente.';
            error_log("ERRO SAVEITEMIMAGE FOTO - Item ID: $id");
        } else {
            $updatePhoto = true;
            // apagando foto antiga somente depois que a nova foi salva com sucesso
            if ($currentPhotoPath && strpos($currentPhotoPath, '/uploads/imagens/') === 0) {
                deleteItemImage($currentPhotoPath);
            }
        }
    }
}

/* ===== LOG ===== */
$detalhe_item = "ITEM '$nome' EDITADO NO SISTEMA";
// Se houve erro de foto, adicionar ao detalhe
if ($erroFoto) {
    $detalhe_item .= " (ERRO FOTO: $erroFoto)";
}

/* ===== UPDATE ===== */
if ($updatePhoto) {
    // Atualizar incluindo o caminho da nova foto
    $sql = "UPDATE itens SET
        nome = ?,
        categoria_id = ?,
        localizacao = ?,
        localizacao_id = ?,
        observacao = ?,
        quantidade = ?,
        foto_loc = ?
        WHERE id = ?";

    $stmt = $cone->prepare($sql);
    $stmt->bind_param(
        "sisisisi",
        $nome,
        $categoria,
        $localizacao,
        $localizacao_id,
        $observacao,
        $quantidade,
        $foto_loc_path,
        $id
    );
} else {
    // Atualizar sem alterar a foto
    $sql = "UPDATE itens SET
        nome = ?,
        categoria_id = ?,
        localizacao = ?,
        localizacao_id = ?,
        observacao = ?,
        quantidade = ?
        WHERE id = ?";

    $stmt = $cone->prepare($sql);

    $stmt->bind_param(
        "sisisii",
        $nome,
        $categoria,
        $localizacao,
        $localizacao_id,
        $observacao,
        $quantidade,
        $id
    );
}

/* ===== EXECUTA ===== */
// Se tentou fazer upload e deu erro, não prosseguir com silêncio
if ($erroFoto && isset($_FILES['foto_loc']['tmp_name']) && !empty($_FILES['foto_loc']['tmp_name'])) {
    // Houver erro na foto e usuário tentou fazer upload
    $_SESSION['aviso'] = 'Item atualizado, mas foto não foi processada: ' . $erroFoto;
}

if ($stmt->execute()) {
    $stmtLog = $cone->prepare(
        "INSERT INTO logs (usuario_id, acao, detalhes)
         VALUES (?, 'ITEM EDITADO NO SISTEMA', ?)"
    );
    $stmtLog->bind_param("is", $id_user, $detalhe_item);
    $stmtLog->execute();

    if (isset($stmtLog)) $stmtLog->close();
    if (isset($stmt)) $stmt->close();

    // Se houve aviso de foto, manter a mensagem
    if (!isset($_SESSION['aviso'])) {
        $_SESSION['sucesso'] = "Item '$nome' atualizado com sucesso!";
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
}

echo "Erro: " . $stmt->error;
?>