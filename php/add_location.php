<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/helpers.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/locations_crud.php';

$id_user = ensureLoggedInUser();
include $_SERVER['DOCUMENT_ROOT'] . "/estoquemh/cone.php";
$isAdmin = ($_SESSION['usuario']['tipo'] ?? '') === 'admin';

// Apenas admins podem gerenciar locais
if (!$isAdmin) {
    header("Location: /estoquemh/pages/sistema.php");
    exit;
}

// Determinar operação
$operacao = $_POST['operacao'] ?? 'add';
$location_id = $_POST['location_id'] ?? null;

if ($operacao === 'add') {
    // Criar novo local
    $nome = $_POST['location_nome'] ?? '';
    $parent_id = !empty($_POST['location_parent_id']) ? $_POST['location_parent_id'] : null;
    $descricao = $_POST['location_descricao'] ?? '';
    
    $result = createLocation($cone, $nome, $parent_id, $descricao);
    
    if ($result['success']) {
        // Log
        $detail = "LOCAL '$nome' ADICIONADO AO SISTEMA";
        if ($parent_id) {
            $parent = getLocationById($cone, $parent_id);
            $detail .= " (Pai: {$parent['nome']})";
        }
        
        $sql_log = "INSERT INTO logs (usuario_id, acao, detalhes) 
                    VALUES (?, 'LOCAL ADICIONADO', ?)";
        $stmt = $cone->prepare($sql_log);
        $stmt->bind_param("is", $id_user, $detail);
        $stmt->execute();
        
        header("Location: /estoquemh/pages/sistema.php?tab=locations");
        exit;
    } else {
        echo "Erro: " . htmlspecialchars($result['error']);
        exit;
    }
    
} elseif ($operacao === 'edit') {
    // Atualizar local
    if (!$location_id) {
        echo "ID do local não fornecido";
        exit;
    }
    
    $nome = $_POST['location_nome'] ?? '';
    $parent_id = !empty($_POST['location_parent_id']) ? $_POST['location_parent_id'] : null;
    $descricao = $_POST['location_descricao'] ?? '';
    
    $result = updateLocation($cone, $location_id, $nome, $parent_id, $descricao);
    
    if ($result['success']) {
        // Log
        $detail = "LOCAL '$nome' ATUALIZADO";
        
        $sql_log = "INSERT INTO logs (usuario_id, acao, detalhes) 
                    VALUES (?, 'LOCAL EDITADO', ?)";
        $stmt = $cone->prepare($sql_log);
        $stmt->bind_param("is", $id_user, $detail);
        $stmt->execute();
        
        header("Location: /estoquemh/pages/sistema.php?tab=locations");
        exit;
    } else {
        echo "Erro: " . htmlspecialchars($result['error']);
        exit;
    }
    
} elseif ($operacao === 'delete') {
    // Desativar local
    if (!$location_id) {
        echo "ID do local não fornecido";
        exit;
    }
    
    $location = getLocationById($cone, $location_id);
    $result = deleteLocation($cone, $location_id);
    
    if ($result['success']) {
        // Log
        $detail = "LOCAL '{$location['nome']}' DELETADO";
        
        $sql_log = "INSERT INTO logs (usuario_id, acao, detalhes) 
                    VALUES (?, 'LOCAL DELETADO', ?)";
        $stmt = $cone->prepare($sql_log);
        $stmt->bind_param("is", $id_user, $detail);
        $stmt->execute();
        
        header("Location: /estoquemh/pages/sistema.php?tab=locations");
        exit;
    } else {
        echo "Erro: " . htmlspecialchars($result['error']);
        exit;
    }
}

mysqli_close($cone);
?>
