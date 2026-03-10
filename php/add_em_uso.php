<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/helpers.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/image_handler.php';
$id_user = ensureLoggedInUser();

include $_SERVER['DOCUMENT_ROOT'] . "/estoquemh/cone.php";

/* ===== DADOS DO FORMULÁRIO ===== */
$nome_item      = $_POST['item-nome'] ?? '';
$categoria_id   = (int)($_POST['item-categoria'] ?? 0);
$setor_id       = (int)($_POST['setor_id'] ?? 0);
$patrimonio     = $_POST['patrimonio'] ?? '';
$observacao     = $_POST['item-observacao'] ?? '';

/* ===== VALIDAÇÕES ===== */
if (empty($nome_item) || $categoria_id <= 0 || $setor_id <= 0) {
    $_SESSION['erro'] = 'Nome, categoria e setor são obrigatórios.';
    header('Location: /estoquemh/pages/em-uso.php');
    exit;
}

/* ===== INSERIR DIRETAMENTE EM itens_em_uso ===== */
$usuario_id = $_SESSION['usuario']['id'] ?? 1;

$foto_loc_path = null;
$quantidade = 1;
$ativo = 1;
$erroFoto = null;

$sqlInsert = "
    INSERT INTO itens_em_uso (patrimonio, nome, categoria_id, setor_id, foto_loc, quantidade, observacao, ativo)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
";
$stmt = $cone->prepare($sqlInsert);

$stmt->bind_param(
    "ssiisisi",
    $patrimonio,
    $nome_item,
    $categoria_id,
    $setor_id,
    $foto_loc_path,
    $quantidade,
    $observacao,
    $ativo
);

if (!$stmt->execute()) {
    $_SESSION['erro'] = 'Erro ao adicionar item: ' . $stmt->error;
    header('Location: /estoquemh/pages/em-uso.php');
    exit;
}

$itemId = $stmt->insert_id;
$stmt->close();

// Processar imagem se houver
if (isset($_FILES['foto_loc'])) {
    if ($_FILES['foto_loc']['error'] !== UPLOAD_ERR_OK) {
        $erroUpload = [
            UPLOAD_ERR_INI_SIZE => 'Arquivo excede upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'Arquivo excede MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'Upload incompleto',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária não encontrada',
            UPLOAD_ERR_CANT_WRITE => 'Erro ao escrever',
            UPLOAD_ERR_EXTENSION => 'Extensão não permitida',
        ];
        $_SESSION['aviso'] = 'Item adicionado, mas foto falhou: ' . ($erroUpload[$_FILES['foto_loc']['error']] ?? 'Erro desconhecido');
    } 
    elseif (!isValidImage($_FILES['foto_loc'])) {
        $_SESSION['aviso'] = 'Item adicionado, mas foto não é válida (JPEG/PNG/GIF/WebP, max 5MB)';
    } 
    elseif (!empty($_FILES['foto_loc']['tmp_name'])) {
        $imagePath = saveItemImage($_FILES['foto_loc'], $itemId);
        if ($imagePath) {
            $updateSql = "UPDATE itens_em_uso SET foto_loc = ? WHERE id = ?";
            $updateStmt = $cone->prepare($updateSql);
            $updateStmt->bind_param("si", $imagePath, $itemId);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            $_SESSION['aviso'] = 'Item adicionado, mas erro ao processar foto';
            error_log("ERRO SAVEITEMIMAGE EM_USO - Item ID: $itemId");
        }
    }
}

if (!isset($_SESSION['aviso'])) {
    $_SESSION['sucesso'] = "Item '{$nome_item}' adicionado em uso com sucesso!";
}

header('Location: /estoquemh/pages/em-uso.php');

exit;
?>
